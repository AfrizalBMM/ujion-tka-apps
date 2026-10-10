<?php

namespace App\Support;

/**
 * Sanitizes untrusted HTML for safe output.
 *
 * Uses DOMDocument-based parsing instead of regex, which is immune
 * to regex-bypass XSS vectors (e.g. <img/src=x/onerror=...>).
 */
final class HtmlSanitizer
{
    /** Tags that are always removed (content + tag). */
    private const DANGEROUS_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form',
        'input', 'button', 'textarea', 'select', 'option',
        'link', 'meta', 'base', 'svg', 'math', 'applet',
        'audio', 'video', 'source', 'track', 'canvas',
    ];

    /** Tags that are allowed (kept as-is). */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre',
        'a', 'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'hr', 'del', 'sup', 'sub', 'dl', 'dt', 'dd', 'div', 'span',
    ];

    /** Attributes allowed on specific tags. */
    private const ALLOWED_ATTRS = [
        'a'    => ['href', 'title', 'target', 'rel'],
        'img'  => ['src', 'alt', 'title', 'width', 'height'],
        'td'   => ['colspan', 'rowspan'],
        'th'   => ['colspan', 'rowspan'],
        'ol'   => ['start'],
        '*'    => [],
    ];

    /** URL schemes that are considered safe. */
    private const SAFE_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    private const URL_ATTRS = ['href', 'src'];

    /**
     * Sanitize HTML to only contain safe tags and attributes.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Decode entities first so encoded payloads are caught.
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! class_exists(\DOMDocument::class)) {
            // Fallback: aggressive strip_tags if DOMDocument unavailable.
            return self::fallbackClean($html);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Suppress warnings from malformed HTML.
        $previous = libxml_use_internal_errors(true);
        @$dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        self::sanitizeNode($body);

        // Extract inner HTML of body.
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        // Process in reverse so removals don't break iteration.
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);

            // Recursively sanitize children first.
            self::sanitizeNode($child);

            if (in_array($tagName, self::DANGEROUS_TAGS, true)) {
                $toRemove[] = $child;
                continue;
            }

            if (! in_array($tagName, self::ALLOWED_TAGS, true)) {
                // Replace disallowed-but-not-dangerous tag with its children.
                // e.g. <article> → keep inner content.
                while ($child->firstChild) {
                    $child->parentNode->insertBefore($child->firstChild, $child);
                }
                $toRemove[] = $child;
                continue;
            }

            // Sanitize attributes.
            self::sanitizeAttributes($child, $tagName);
        }

        foreach ($toRemove as $nodeToRemove) {
            $nodeToRemove->parentNode->removeChild($nodeToRemove);
        }
    }

    private static function sanitizeAttributes(\DOMElement $element, string $tagName): void
    {
        $toRemove = [];
        $allowed = self::ALLOWED_ATTRS[$tagName] ?? [];
        $globalAllowed = self::ALLOWED_ATTRS['*'];

        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->name);

            // Remove all event handler attributes (on*).
            if (str_starts_with($attrName, 'on')) {
                $toRemove[] = $attr;
                continue;
            }

            // Remove style attributes (can contain expression() / url(javascript:)).
            if ($attrName === 'style') {
                $toRemove[] = $attr;
                continue;
            }

            // Check if attribute is in the allowlist for this tag.
            if (! in_array($attrName, $allowed, true) && ! in_array($attrName, $globalAllowed, true)) {
                $toRemove[] = $attr;
                continue;
            }

            // Validate URL-bearing attributes.
            if (in_array($attrName, self::URL_ATTRS, true)) {
                $value = trim($attr->value);
                if (! self::isSafeUrl($value)) {
                    $toRemove[] = $attr;
                    continue;
                }
            }
        }

        foreach ($toRemove as $attr) {
            $element->removeAttributeNode($attr);
        }

        // Force rel="noopener noreferrer" on <a target="_blank">.
        if ($tagName === 'a' && $element->hasAttribute('target')) {
            $target = strtolower(trim($element->getAttribute('target')));
            if ($target === '_blank' || $target === '_new') {
                $existingRel = $element->getAttribute('rel');
                $relParts = array_filter(explode(' ', $existingRel));
                if (! in_array('noopener', $relParts, true)) {
                    $relParts[] = 'noopener';
                }
                if (! in_array('noreferrer', $relParts, true)) {
                    $relParts[] = 'noreferrer';
                }
                $element->setAttribute('rel', implode(' ', $relParts));
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        // Allow relative URLs (/, ./, ../, #).
        if ($url === '' || $url[0] === '/' || $url[0] === '#') {
            return true;
        }

        if (str_starts_with($url, '?')) {
            return true;
        }

        // Parse scheme.
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/i', $url, $matches)) {
            $scheme = strtolower($matches[1]);
            return in_array($scheme, self::SAFE_URL_SCHEMES, true);
        }

        // No scheme — likely relative. Check for javascript: without scheme.
        if (preg_match('/javascript:/i', $url)) {
            return false;
        }

        if (preg_match('/data:/i', $url)) {
            return false;
        }

        // Assume safe if no dangerous scheme detected.
        return true;
    }

    private static function fallbackClean(string $html): string
    {
        // Strip dangerous tags completely.
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|math|form|input|link|meta|base)[^>]*>.*?</\1>#is', '', $html);

        // Remove all on* attributes.
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Remove style attributes.
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Remove javascript: and data: URLs.
        $html = preg_replace('/(href|src)\s*=\s*("[^"]*(?:javascript|data):[^"]*"|\'[^\']*(?:javascript|data):[^\']*\'|[^\s>]*(?:javascript|data):[^\s>]*)/i', '', $html);

        return strip_tags($html, '<' . implode('><', self::ALLOWED_TAGS) . '>');
    }
}

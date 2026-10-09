<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogXssSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_script_tags_are_stripped_from_blog_content(): void
    {
        $post = $this->createPublishedPost(
            "<script>alert('xss')</script>\n\n# Safe heading"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertStringContainsString('Safe heading', $html);
    }

    public function test_on_event_handler_attributes_are_stripped(): void
    {
        $post = $this->createPublishedPost(
            '<img src="x" onerror="alert(1)" alt="img">'."\n\nNormal text"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringContainsString('Normal text', $html);
    }

    public function test_javascript_urls_are_stripped_from_href(): void
    {
        $post = $this->createPublishedPost(
            '[Click me](javascript:alert(1))'
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringContainsString('Click me', $html);
    }

    public function test_javascript_urls_are_stripped_from_src(): void
    {
        $post = $this->createPublishedPost(
            '<img src="javascript:alert(1)" alt="evil">'
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_iframe_tags_are_stripped(): void
    {
        $post = $this->createPublishedPost(
            '<iframe src="https://evil.com"></iframe>'."\n\nSafe content"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('evil.com', $html);
        $this->assertStringContainsString('Safe content', $html);
    }

    public function test_style_tags_are_stripped(): void
    {
        $post = $this->createPublishedPost(
            '<style>body{background:url("javascript:alert(1)")}</style>'."\n\nText"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_object_and_embed_tags_are_stripped(): void
    {
        $post = $this->createPublishedPost(
            '<object data="https://evil.com/swf"></object>'."\n\n<embed src=\"https://evil.com\">"."\n\nText"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('<object', $html);
        $this->assertStringNotContainsString('<embed', $html);
    }

    public function test_legitimate_html_survives_sanitization(): void
    {
        $post = $this->createPublishedPost(
            "**bold text**\n\n*italic text*\n\n- list item\n\n[link](https://example.com)"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('<strong>bold text</strong>', $html);
        $this->assertStringContainsString('<em>italic text</em>', $html);
        $this->assertStringContainsString('https://example.com', $html);
        $this->assertStringContainsString('list item', $html);
    }

    public function test_single_quoted_event_handler_attributes_are_stripped(): void
    {
        $post = $this->createPublishedPost(
            "<img src='x' onclick='alert(1)' alt='img'>\n\nText"
        );

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    private function createPublishedPost(string $content): BlogPost
    {
        $unique = Str::random(12);

        return BlogPost::create([
            'title' => 'Test Article '.$unique,
            'slug' => 'test-article-'.$unique,
            'excerpt' => 'Test excerpt',
            'content' => $content,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}

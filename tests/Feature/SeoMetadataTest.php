<?php

namespace Tests\Feature;

use App\Models\LandingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_uses_default_meta_when_seo_fields_empty(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('og:title', false);
    }

    public function test_landing_uses_custom_seo_fields_from_landing_content(): void
    {
        LandingContent::create([
            'section' => 'hero',
            'title' => 'Judul Hero Biasa',
            'seo_title' => 'Platform Ujian TKA Online untuk Guru',
            'seo_description' => 'Deskripsi khusus untuk hasil pencarian Google.',
            'is_active' => true,
        ]);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('<title>Platform Ujian TKA Online untuk Guru</title>', false);
        $response->assertSee('content="Deskripsi khusus untuk hasil pencarian Google."', false);
    }

    public function test_private_layouts_send_noindex(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_register_guru_page_stays_indexable(): void
    {
        $response = $this->get(route('register.guru.form'));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="index,follow">', false);
        $response->assertSee('<link rel="canonical"', false);
    }

    public function test_robots_txt_disallows_private_paths_and_references_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /guru', $content);
        $this->assertStringContainsString('Disallow: /superadmin', $content);
        $this->assertStringContainsString('Disallow: /siswa', $content);
        $this->assertStringContainsString('Disallow: /payments', $content);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $content);
        $this->assertStringNotContainsString('Disallow: /register', $content);
    }

    public function test_sitemap_uses_realistic_lastmod_from_content_updates(): void
    {
        $content = LandingContent::create([
            'section' => 'hero',
            'title' => 'Hero',
            'is_active' => true,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString(route('landing'), $xml);
        $this->assertStringContainsString('<lastmod>'.$content->updated_at->toAtomString().'</lastmod>', $xml);
    }
}

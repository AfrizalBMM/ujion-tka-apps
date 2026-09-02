<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Jenjang;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    private function seedJenjangs(): void
    {
        Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'Sekolah Dasar', 'urutan' => 1]);
        Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]);
    }

    public function test_kisi_kisi_index_renders_and_is_indexable(): void
    {
        $this->seedJenjangs();

        $response = $this->get(route('kisi-kisi.index'));

        $response->assertOk();
        $response->assertSee('Kisi-Kisi TKA per Jenjang');
        $response->assertSee('BreadcrumbList', false);
        $response->assertDontSee('noindex', false);
        $response->assertSee(route('kisi-kisi.jenjang', ['jenjang' => 'sd']), false);
    }

    public function test_kisi_kisi_jenjang_lists_mapels(): void
    {
        $this->seedJenjangs();

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Bilangan',
            'unit' => 'Bilangan Bulat',
            'sub_unit' => 'Operasi Hitung',
            'link' => 'https://contoh.test/ref',
        ]);

        $response = $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'sd']));

        $response->assertOk();
        $response->assertSee('Matematika');
        $response->assertSee(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']), false);
    }

    public function test_kisi_kisi_jenjang_returns_404_for_unknown_jenjang(): void
    {
        $this->seedJenjangs();

        $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'kuliah']))->assertNotFound();
    }

    public function test_kisi_kisi_mapel_shows_topics_with_reference_link(): void
    {
        $this->seedJenjangs();

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Bilangan',
            'unit' => 'Bilangan Bulat',
            'sub_unit' => 'Operasi Hitung',
            'link' => 'https://contoh.test/ref',
        ]);

        $response = $this->get(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']));

        $response->assertOk();
        $response->assertSee('Operasi Hitung');
        $response->assertSee('https://contoh.test/ref', false);
        $response->assertSee('BreadcrumbList', false);
    }

    public function test_kisi_kisi_mapel_returns_404_for_unknown_mapel(): void
    {
        $this->seedJenjangs();

        $this->get(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'fisika']))->assertNotFound();
    }

    public function test_artikel_index_only_lists_published_posts(): void
    {
        BlogPost::create([
            'title' => 'Artikel Terbit',
            'slug' => 'artikel-terbit',
            'content' => 'Isi artikel terbit.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        BlogPost::create([
            'title' => 'Artikel Draf',
            'slug' => 'artikel-draf',
            'content' => 'Isi draf.',
            'is_published' => false,
        ]);

        $response = $this->get(route('artikel.index'));

        $response->assertOk();
        $response->assertSee('Artikel Terbit');
        $response->assertDontSee('Artikel Draf');
    }

    public function test_artikel_show_renders_published_post_with_article_schema(): void
    {
        $post = BlogPost::create([
            'title' => 'Tips Menghadapi TKA',
            'slug' => 'tips-menghadapi-tka',
            'content' => "## Pembuka\n\nIsi **artikel** lengkap.",
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('artikel.show', $post));

        $response->assertOk();
        $response->assertSee('Tips Menghadapi TKA');
        $response->assertSee('"@type":"Article"', false);
        $response->assertSee('<strong>artikel</strong>', false);
        $response->assertDontSee('noindex', false);
    }

    public function test_artikel_show_returns_404_for_draft(): void
    {
        $post = BlogPost::create([
            'title' => 'Draf Rahasia',
            'slug' => 'draf-rahasia',
            'content' => 'Belum terbit.',
            'is_published' => false,
        ]);

        $this->get(route('artikel.show', $post))->assertNotFound();
    }

    public function test_superadmin_can_create_published_post_with_auto_slug(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.blog.store'), [
            'title' => 'Panduan Lengkap TKA 2026!',
            'content' => 'Isi panduan.',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('superadmin.blog.index'));

        $post = BlogPost::where('slug', 'panduan-lengkap-tka-2026')->first();
        $this->assertNotNull($post);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_superadmin_can_toggle_post_publish_status(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $post = BlogPost::create([
            'title' => 'Artikel Draf',
            'slug' => 'artikel-draf-toggle',
            'content' => 'Isi.',
            'is_published' => false,
        ]);

        $this->actingAs($superadmin)->post(route('superadmin.blog.toggle', $post));
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);

        $this->actingAs($superadmin)->post(route('superadmin.blog.toggle', $post));
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_guest_cannot_access_blog_admin(): void
    {
        $this->get(route('superadmin.blog.index'))->assertRedirect();
    }

    public function test_sitemap_includes_kisi_kisi_and_artikel_urls(): void
    {
        $this->seedJenjangs();

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Bilangan',
            'unit' => 'Bilangan Bulat',
            'sub_unit' => 'Operasi Hitung',
        ]);

        BlogPost::create([
            'title' => 'Artikel Sitemap',
            'slug' => 'artikel-sitemap',
            'content' => 'Isi.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();

        $xml = $response->getContent();
        $this->assertStringContainsString(route('kisi-kisi.index'), $xml);
        $this->assertStringContainsString(route('kisi-kisi.jenjang', ['jenjang' => 'sd']), $xml);
        $this->assertStringContainsString(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']), $xml);
        $this->assertStringContainsString(route('artikel.index'), $xml);
        $this->assertStringContainsString(route('artikel.show', ['post' => 'artikel-sitemap']), $xml);
    }
}

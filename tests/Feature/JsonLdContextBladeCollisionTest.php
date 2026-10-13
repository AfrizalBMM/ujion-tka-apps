<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Jenjang;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade core mendaftarkan directive @context (CompilesContexts). Tanpa escape @@,
 * key '@context' di json_encode() ikut ter-compile jadi PHP mentah dan JSON-LD rusak.
 */
class JsonLdContextBladeCollisionTest extends TestCase
{
    use RefreshDatabase;

    private function seedKisiKisi(): void
    {
        Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'Sekolah Dasar', 'urutan' => 1]);

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Bilangan',
            'unit' => 'Bilangan Bulat',
            'sub_unit' => 'Operasi Hitung',
            'link' => 'https://contoh.test/ref',
        ]);
    }

    private function publishedPost(string $title = 'Tips TKA'): BlogPost
    {
        return BlogPost::create([
            'title' => $title,
            'slug' => 'artikel-probe',
            'content' => 'Isi artikel.',
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    private function assertJsonLdContextIntact(string $html, string $routeName): void
    {
        $this->assertStringNotContainsString('\u003C?php', $html, "raw PHP masuk output JSON-LD di {$routeName}");
        $this->assertStringNotContainsString('$__contextArgs', $html, "directive @context ter-compile di {$routeName}");

        preg_match_all('#<script type="application/ld\+json">\s*(\{.*?\})\s*</script>#s', $html, $m);
        $this->assertNotEmpty($m[1], "tidak ada blok JSON-LD di {$routeName}");

        foreach ($m[1] as $block) {
            $decoded = json_decode($block, true);
            $this->assertIsArray($decoded, "blok JSON-LD tidak valid JSON di {$routeName}: ".$block);
            $this->assertSame('https://schema.org', $decoded['@context'] ?? null, "key @context tidak utuh di {$routeName}");
        }
    }

    public function test_kisi_kisi_views_keep_context_key(): void
    {
        $this->seedKisiKisi();

        $this->assertJsonLdContextIntact($this->get(route('kisi-kisi.index'))->getContent(), 'kisi-kisi.index');
        $this->assertJsonLdContextIntact($this->get(route('kisi-kisi.jenjang', ['jenjang' => 'sd']))->getContent(), 'kisi-kisi.jenjang');
        $this->assertJsonLdContextIntact($this->get(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']))->getContent(), 'kisi-kisi.mapel');
    }

    public function test_artikel_views_keep_context_key(): void
    {
        $post = $this->publishedPost();

        $this->assertJsonLdContextIntact($this->get(route('artikel.index'))->getContent(), 'artikel.index');
        $this->assertJsonLdContextIntact($this->get(route('artikel.show', $post))->getContent(), 'artikel.show');
    }

    public function test_artikel_title_cannot_break_out_of_json_ld(): void
    {
        $payload = '</script><script>alert(document.cookie)</script>';
        $post = $this->publishedPost($payload);

        $html = $this->get(route('artikel.show', $post))->getContent();

        $this->assertStringNotContainsString($payload, $html);
        $this->assertJsonLdContextIntact($html, 'artikel.show (xss probe)');
    }
}

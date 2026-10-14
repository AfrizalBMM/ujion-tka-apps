<?php

namespace Tests\Feature;

use App\Models\Jenjang;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BektiProbeKisiKisiTest extends TestCase
{
    use RefreshDatabase;

    private function seedJenjangs(): void
    {
        Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'Sekolah Dasar', 'urutan' => 1]);
        Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]);
    }

    public function test_json_ld_does_not_break_out_with_html_chars(): void
    {
        Jenjang::create([
            'kode' => 'TST<script>',
            'nama' => 'Test "Jenjang" & <script>alert(1)</script>',
            'urutan' => 99,
        ]);

        $res = $this->get('/kisi-kisi/tst<script>');
        $res->assertOk();
        $body = $res->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $body);
        $this->assertStringContainsString('\u003Cscript\u003E', $body);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
    }

    public function test_sql_injection_probe_in_jenjang_route(): void
    {
        $this->seedJenjangs();

        $res = $this->get("/kisi-kisi/sd' OR '1'='1");
        $res->assertNotFound();
    }

    public function test_slug_empty_edge_case_does_not_break_jenjang_page(): void
    {
        $this->seedJenjangs();

        Material::create([
            'jenjang' => 'SMP',
            'mapel' => '???',
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Aljabar',
            'unit' => 'U1',
            'sub_unit' => 'S1',
        ]);

        // Guard slug kosong: mapel yang slug-nya kosong difilter, halaman tetap 200
        // (sebelum fix: UrlGenerationException -> 500).
        $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'smp']))->assertOk();
    }
}

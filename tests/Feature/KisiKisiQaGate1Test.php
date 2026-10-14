<?php

namespace Tests\Feature;

use App\Models\Jenjang;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KisiKisiQaGate1Test extends TestCase
{
    use RefreshDatabase;

    private function seedJenjangs(): void
    {
        Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'Sekolah Dasar', 'urutan' => 1]);
        Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]);
        Jenjang::firstOrCreate(['kode' => 'SMA'], ['nama' => 'Sekolah Menengah Atas', 'urutan' => 3]);
    }

    private function createMaterial(string $jenjang, ?string $mapel, string $unit = 'Unit Contoh'): Material
    {
        return Material::create([
            'jenjang' => $jenjang,
            'mapel' => $mapel,
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Aljabar',
            'unit' => $unit,
            'sub_unit' => 'Sub '.$unit,
            'link' => 'https://example.com/ref',
        ]);
    }

    public function test_regresi_utama_all_materials_have_empty_mapel_returns_200(): void
    {
        $this->seedJenjangs();

        for ($i = 1; $i <= 80; $i++) {
            $this->createMaterial('SD', '', 'Materi '.$i);
        }
        $this->createMaterial('SD', null, 'Materi Null');

        $response = $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'sd']));

        $response->assertOk();
        $response->assertSee('Materi untuk jenjang ini belum tersedia');
        $response->assertSee('Kisi-Kisi TKA SD');
        $this->assertCount(0, $response->viewData('mapels'));
    }

    public function test_empty_and_null_mapel_excluded_from_jenjang_cards(): void
    {
        $this->seedJenjangs();

        for ($i = 1; $i <= 5; $i++) {
            $this->createMaterial('SD', '', 'Materi Kosong '.$i);
            $this->createMaterial('SD', null, 'Materi Null '.$i);
        }
        $this->createMaterial('SD', 'Matematika', 'Aljabar Dasar');
        $this->createMaterial('SD', 'Bahasa Indonesia', 'Membaca Kritis');

        $response = $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'sd']));

        $response->assertOk();

        $mapels = $response->viewData('mapels');
        $this->assertCount(2, $mapels);

        $names = $mapels->pluck('name')->all();
        $this->assertContains('Matematika', $names);
        $this->assertContains('Bahasa Indonesia', $names);

        $response->assertSee(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']), false);
        $response->assertSee(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'bahasa-indonesia']), false);
    }

    public function test_normal_mapel_accessible_via_slug_returns_200(): void
    {
        $this->seedJenjangs();

        $this->createMaterial('SD', 'Matematika', 'Operasi Hitung Campuran');

        $response = $this->get(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']));

        $response->assertOk();
        $response->assertSee('Matematika');
        $response->assertSee('Operasi Hitung Campuran');
        $response->assertSee('https://example.com/ref', false);
    }

    public function test_index_page_mapel_count_ignores_empty_and_null_mapel(): void
    {
        $this->seedJenjangs();

        for ($i = 1; $i <= 10; $i++) {
            $this->createMaterial('SD', '', 'Materi Kosong '.$i);
        }
        $this->createMaterial('SD', null, 'Materi Null');

        $this->createMaterial('SMP', 'IPA', 'Fisika Dasar');
        $this->createMaterial('SMP', 'IPS', 'Geografi');

        $response = $this->get(route('kisi-kisi.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');
        $this->assertFalse($stats->has('SD'));
        $this->assertTrue($stats->has('SMP'));
        $this->assertSame(2, (int) $stats->get('SMP')->mapel_count);
        $this->assertSame(2, (int) $stats->get('SMP')->topic_count);
    }

    public function test_unknown_jenjang_returns_404_not_500(): void
    {
        $this->seedJenjangs();

        $this->get('/kisi-kisi/kuliah')->assertNotFound();
        $this->get('/kisi-kisi/tk')->assertNotFound();
        $this->get('/kisi-kisi/unknown-jenjang/matematika')->assertNotFound();
    }

    public function test_unknown_mapel_on_valid_jenjang_returns_404(): void
    {
        $this->seedJenjangs();

        $this->createMaterial('SD', 'Matematika', 'Topik A');

        $this->get(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'fisika']))->assertNotFound();
        $this->get(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'kimia']))->assertNotFound();
    }

    public function test_sitemap_does_not_contain_empty_mapel_routes(): void
    {
        $this->seedJenjangs();

        $this->createMaterial('SD', '', 'Materi Kosong');
        $this->createMaterial('SD', null, 'Materi Null');
        $this->createMaterial('SMA', 'Biologi', 'Sel Hewan');

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $xml = $response->getContent();

        $this->assertStringContainsString(route('kisi-kisi.mapel', ['jenjang' => 'sma', 'mapel' => 'biologi']), $xml);
        $this->assertSame(0, substr_count($xml, '/kisi-kisi/sd/</loc>'));
        $this->assertStringNotContainsString('/kisi-kisi/sd//', $xml);
    }
}

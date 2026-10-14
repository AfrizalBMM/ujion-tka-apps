<?php

namespace Tests\Feature;

use App\Models\Jenjang;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KisiKisiEmptyMapelTest extends TestCase
{
    use RefreshDatabase;

    private function seedJenjangs(): void
    {
        Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'Sekolah Dasar', 'urutan' => 1]);
        Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]);
    }

    private function material(string $jenjang, ?string $mapel, string $unit): void
    {
        Material::create([
            'jenjang' => $jenjang,
            'mapel' => $mapel,
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Bilangan',
            'unit' => $unit,
            'sub_unit' => 'Operasi'.$unit,
        ]);
    }

    public function test_jenjang_page_renders_when_all_materials_have_empty_mapel(): void
    {
        $this->seedJenjangs();

        $this->material('SD', '', 'Satu');
        $this->material('SD', '', 'Dua');
        $this->material('SD', null, 'Tiga');

        $response = $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'sd']));

        $response->assertOk();
        $response->assertSee('Materi untuk jenjang ini belum tersedia');
        $response->assertSee('Kisi-Kisi TKA SD');
    }

    public function test_empty_mapel_is_not_listed_as_mapel(): void
    {
        $this->seedJenjangs();

        $this->material('SD', '', 'Satu');
        $this->material('SD', null, 'Dua');
        $this->material('SD', 'Matematika', 'Tiga');

        $response = $this->get(route('kisi-kisi.jenjang', ['jenjang' => 'sd']));

        $response->assertOk();

        $mapels = $response->viewData('mapels');

        $this->assertCount(1, $mapels);
        $this->assertSame('Matematika', $mapels->first()['name']);
        $this->assertSame('matematika', $mapels->first()['slug']);
        $this->assertSame(1, (int) $mapels->first()['topic_count']);
        $response->assertSee(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']), false);
    }

    public function test_index_stats_ignore_empty_mapel(): void
    {
        $this->seedJenjangs();

        $this->material('SD', '', 'Satu');
        $this->material('SD', '', 'Dua');
        $this->material('SMP', 'Matematika', 'Tiga');
        $this->material('SMP', 'IPA', 'Empat');

        $response = $this->get(route('kisi-kisi.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertFalse($stats->has('SD'));
        $this->assertSame(2, (int) $stats->get('SMP')->mapel_count);
        $this->assertSame(2, (int) $stats->get('SMP')->topic_count);
        $response->assertSee('Materi segera hadir');
    }

    public function test_sitemap_skips_empty_mapel(): void
    {
        $this->seedJenjangs();

        $this->material('SD', '', 'Satu');
        $this->material('SD', 'Matematika', 'Dua');

        $response = $this->get(route('sitemap'));

        $response->assertOk();

        $xml = $response->getContent();

        $this->assertStringContainsString(route('kisi-kisi.mapel', ['jenjang' => 'sd', 'mapel' => 'matematika']), $xml);
        $this->assertSame(0, substr_count($xml, '/kisi-kisi/sd/</loc>'));
    }
}

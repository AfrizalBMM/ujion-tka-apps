<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use App\Support\SpreadsheetTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MaterialTemplateQaTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
    }

    private function templateBody(?string $jenjang = null): string
    {
        $url = route('superadmin.materials.template');
        if ($jenjang !== null) {
            $url .= '?jenjang='.$jenjang;
        }

        $response = $this->actingAs($this->superadmin())->get($url);
        $response->assertOk();

        return $response->streamedContent();
    }

    private function cells(string $body): array
    {
        preg_match_all('/<Data ss:Type="String">(.*?)<\/Data>/s', $body, $matches);

        return array_map(fn ($value) => html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1]);
    }

    private function rows(string $body): array
    {
        return array_slice($this->cells($body), 7);
    }

    private function writeTemplateToDisk(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xls';
        file_put_contents($path, $body);

        return $path;
    }

    private function seedSdMaterial(): Material
    {
        return Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Bulat Negatif',
            'sub_unit' => 'Penjumlahan & Pengurangan Bilangan Bulat',
            'link' => 'https://buku.kemdikbud.go.id/katalog/matematika-kelas-vi',
        ]);
    }

    private function seedSmpMaterial(): Material
    {
        return Material::create([
            'jenjang' => 'SMP',
            'mapel' => 'Ilmu Pengetahuan Alam',
            'curriculum' => 'Merdeka',
            'subelement' => 'Zat dan Perubahannya',
            'unit' => 'Suhu dan Kalor',
            'sub_unit' => 'Pemuaian Zat',
            'link' => null,
        ]);
    }

    public function test_scenario_1_header_is_exactly_seven_columns_in_order(): void
    {
        $cells = $this->cells($this->templateBody());

        $this->assertSame(
            ['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit', 'link'],
            array_slice($cells, 0, 7)
        );
    }

    public function test_scenario_2_example_values_come_from_database(): void
    {
        $this->seedSdMaterial();

        $rows = $this->rows($this->templateBody('SD'));

        $this->assertContains('SD', $rows);
        $this->assertContains('Matematika', $rows);
        $this->assertContains('K-13', $rows);
        $this->assertContains('Bilangan', $rows);
        $this->assertContains('Bulat Negatif', $rows);
        $this->assertContains('Penjumlahan & Pengurangan Bilangan Bulat', $rows);
        $this->assertContains('https://buku.kemdikbud.go.id/katalog/matematika-kelas-vi', $rows);

        $this->assertNotContains('Numerasi', $rows);
        $this->assertNotContains('Literasi', $rows);
        $this->assertStringNotContainsString('contoh-materi.test', implode(' ', $rows));
    }

    public function test_scenario_2_example_values_never_invent_rows_when_database_has_other_subject(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Seni Budaya',
            'curriculum' => 'Merdeka',
            'subelement' => 'Seni Rupa',
            'unit' => 'Menggambar Bentuk',
            'sub_unit' => 'Garis dan Bidang',
            'link' => null,
        ]);

        $rows = $this->rows($this->templateBody('SD'));

        $this->assertContains('Seni Budaya', $rows);
        $this->assertContains('Seni Rupa', $rows);
        $this->assertNotContains('Matematika', $rows);
        $this->assertNotContains('Numerasi', $rows);
        $this->assertNotContains('Literasi', $rows);
        $this->assertNotContains('Teks Narasi', $rows);
    }

    public function test_scenario_3_jenjang_filter_returns_only_requested_level(): void
    {
        $this->seedSdMaterial();
        $this->seedSmpMaterial();

        $sdRows = $this->rows($this->templateBody('SD'));
        $this->assertContains('SD', $sdRows);
        $this->assertNotContains('SMP', $sdRows);
        $this->assertContains('Matematika', $sdRows);
        $this->assertNotContains('Ilmu Pengetahuan Alam', $sdRows);

        $smpRows = $this->rows($this->templateBody('SMP'));
        $this->assertContains('SMP', $smpRows);
        $this->assertNotContains('SD', $smpRows);
        $this->assertContains('Ilmu Pengetahuan Alam', $smpRows);
        $this->assertNotContains('Matematika', $smpRows);
    }

    public function test_scenario_3_jenjang_filter_is_case_insensitive(): void
    {
        $this->seedSdMaterial();
        $this->seedSmpMaterial();

        $lower = $this->rows($this->templateBody('sd'));

        $this->assertContains('SD', $lower);
        $this->assertNotContains('SMP', $lower);
    }

    public function test_scenario_3_jenjang_filter_with_no_database_rows_does_not_leak_other_levels(): void
    {
        $this->seedSdMaterial();

        $smaRows = $this->rows($this->templateBody('SMA'));

        for ($index = 0; $index + 6 < count($smaRows); $index += 7) {
            $this->assertSame('SMA', $smaRows[$index], 'Baris contoh jenjang SMA bocor dari jenjang lain.');
        }
    }

    public function test_scenario_3_jenjang_filter_with_no_database_rows_has_no_other_jenjang_cells(): void
    {
        $this->seedSdMaterial();

        $smaRows = $this->rows($this->templateBody('SMA'));

        $this->assertNotContains('SD', $smaRows);
        $this->assertNotContains('SMP', $smaRows);
    }

    public function test_scenario_4_fallback_when_database_empty_still_produces_rows(): void
    {
        $this->assertSame(0, Material::count());

        $body = $this->templateBody('SD');
        $cells = $this->cells($body);

        $this->assertCount(7, array_slice($cells, 0, 7));
        $this->assertGreaterThan(7, count($cells));

        $rows = array_slice($cells, 7);
        $this->assertNotSame('', implode('', $rows));
    }

    public function test_scenario_4_fallback_respects_jenjang_filter(): void
    {
        $sdRows = $this->rows($this->templateBody('SD'));
        $this->assertContains('SD', $sdRows);
        $this->assertNotContains('SMP', $sdRows);
        $this->assertNotContains('SMA', $sdRows);

        $smpRows = $this->rows($this->templateBody('SMP'));
        $this->assertContains('SMP', $smpRows);
        $this->assertNotContains('SD', $smpRows);
    }

    public function test_scenario_5_generated_file_parses_back_into_import_keys(): void
    {
        $this->seedSdMaterial();

        $path = $this->writeTemplateToDisk($this->templateBody('SD'));
        $rows = SpreadsheetTable::rowsFromUpload(new UploadedFile($path, 'template.xls', null, null, true));

        $this->assertNotEmpty($rows);
        $this->assertSame(
            ['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit', 'link'],
            array_keys($rows[0])
        );
    }

    public function test_scenario_5_generated_file_imports_without_error(): void
    {
        $this->seedSdMaterial();

        $path = $this->writeTemplateToDisk($this->templateBody('SD'));
        $before = Material::count();

        $response = $this->actingAs($this->superadmin())->post(route('superadmin.materials.import'), [
            'file' => new UploadedFile($path, 'template.xls', null, null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertGreaterThanOrEqual($before, Material::count());
    }

    public function test_scenario_5_round_trip_row_values_match_database(): void
    {
        $created = $this->seedSdMaterial();

        $path = $this->writeTemplateToDisk($this->templateBody('SD'));
        $rows = SpreadsheetTable::rowsFromUpload(new UploadedFile($path, 'template.xls', null, null, true));

        $match = collect($rows)->firstWhere('sub_unit', 'Penjumlahan & Pengurangan Bilangan Bulat');

        $this->assertNotNull($match);
        $this->assertSame('SD', $match['jenjang']);
        $this->assertSame('Matematika', $match['mapel']);
        $this->assertSame('K-13', $match['curriculum']);
        $this->assertSame('Bilangan', $match['subelement']);
        $this->assertSame('Bulat Negatif', $match['unit']);
        $this->assertSame($created->link, $match['link']);
    }

    public function test_scenario_5_round_trip_on_empty_database_imports_without_error(): void
    {
        $this->assertSame(0, Material::count());

        $path = $this->writeTemplateToDisk($this->templateBody('SD'));
        $rows = SpreadsheetTable::rowsFromUpload(new UploadedFile($path, 'template.xls', null, null, true));

        $this->assertNotEmpty($rows);
        $this->assertSame(
            ['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit', 'link'],
            array_keys($rows[0])
        );

        $response = $this->actingAs($this->superadmin())->post(route('superadmin.materials.import'), [
            'file' => new UploadedFile($path, 'template.xls', null, null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_scenario_2_template_without_jenjang_uses_database_rows_across_levels(): void
    {
        $this->seedSdMaterial();
        $this->seedSmpMaterial();

        $rows = $this->rows($this->templateBody());

        $this->assertContains('Matematika', $rows);
        $this->assertContains('Ilmu Pengetahuan Alam', $rows);
        $this->assertNotContains('Numerasi', $rows);
        $this->assertNotContains('Literasi', $rows);
    }

    public function test_scenario_2_rows_with_missing_hierarchy_are_excluded_from_examples(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Informatika',
            'curriculum' => 'Merdeka',
            'subelement' => 'Berpikir Komputasional',
            'unit' => 'Algoritma',
            'sub_unit' => 'Urutan Langkah',
            'link' => null,
        ]);

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Pendidikan Pancasila',
            'curriculum' => 'Merdeka',
            'subelement' => '',
            'unit' => '',
            'sub_unit' => '',
            'link' => null,
        ]);

        $rows = $this->rows($this->templateBody('SD'));

        $this->assertContains('Informatika', $rows);
        $this->assertNotContains('Pendidikan Pancasila', $rows);
    }
}

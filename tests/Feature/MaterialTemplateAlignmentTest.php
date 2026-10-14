<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialTemplateAlignmentTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
    }

    private function templateBody(string $jenjang = ''): string
    {
        $url = route('superadmin.materials.template');
        if ($jenjang !== '') {
            $url .= '?jenjang='.$jenjang;
        }

        $response = $this->actingAs($this->superadmin())->get($url);
        $response->assertOk();

        return $response->streamedContent();
    }

    private function cells(string $body): array
    {
        preg_match_all('/<Data ss:Type="String">(.*?)<\/Data>/s', $body, $matches);

        return array_map(fn ($v) => html_entity_decode($v, ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1]);
    }

    public function test_template_headers_match_database_columns(): void
    {
        $cells = $this->cells($this->templateBody());
        $headers = array_slice($cells, 0, 7);

        $this->assertSame(
            ['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit', 'link'],
            $headers
        );
    }

    public function test_template_examples_use_real_material_hierarchy(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Bulat Negatif',
            'sub_unit' => 'Penjumlahan & Pengurangan Bilangan Bulat',
            'link' => 'https://buku.kemdikbud.go.id/katalog/matematika-kelas-vi',
        ]);

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Bahasa Indonesia',
            'curriculum' => 'Merdeka',
            'subelement' => 'Membaca',
            'unit' => 'Teks Narasi',
            'sub_unit' => 'Menemukan Pesan Tersirat',
            'link' => null,
        ]);

        $cells = $this->cells($this->templateBody('SD'));
        $rows = array_slice($cells, 7);

        $this->assertContains('Matematika', $rows);
        $this->assertContains('Bilangan', $rows);
        $this->assertContains('Bulat Negatif', $rows);
        $this->assertContains('Penjumlahan & Pengurangan Bilangan Bulat', $rows);
        $this->assertContains('Bahasa Indonesia', $rows);
        $this->assertContains('Menemukan Pesan Tersirat', $rows);

        $this->assertNotContains('Numerasi', $rows);
        $this->assertNotContains('Literasi', $rows);
    }

    public function test_template_never_invents_subject_not_in_database(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Ilmu Pengetahuan Alam',
            'curriculum' => 'Merdeka',
            'subelement' => 'Makhluk Hidup',
            'unit' => 'Bagian Tumbuhan',
            'sub_unit' => 'Akar dan Batang',
            'link' => null,
        ]);

        $cells = $this->cells($this->templateBody('SD'));
        $rows = array_slice($cells, 7);

        $this->assertContains('Ilmu Pengetahuan Alam', $rows);
        $this->assertContains('Makhluk Hidup', $rows);
        $this->assertNotContains('Numerasi', $rows);
        $this->assertNotContains('Matematika', $rows);
    }

    public function test_template_rows_parse_back_into_import_keys(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Bulat Negatif',
            'sub_unit' => 'Penjumlahan & Pengurangan Bilangan Bulat',
            'link' => null,
        ]);

        $body = $this->templateBody('SD');
        $cells = $this->cells($body);
        $rows = array_slice($cells, 7);

        $this->assertGreaterThanOrEqual(7, count($rows));
        $this->assertSame('SD', $rows[0]);
        $this->assertSame('Matematika', $rows[1]);
        $this->assertSame('K-13', $rows[2]);
        $this->assertSame('Bilangan', $rows[3]);
        $this->assertSame('Bulat Negatif', $rows[4]);
        $this->assertSame('Penjumlahan & Pengurangan Bilangan Bulat', $rows[5]);
    }

    public function test_template_falls_back_when_database_is_empty(): void
    {
        $cells = $this->cells($this->templateBody('SD'));

        $this->assertCount(7, array_slice($cells, 0, 7));
        $this->assertGreaterThan(7, count($cells));
    }

    public function test_requested_jenjang_without_rows_never_shows_other_jenjang(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Bulat Negatif',
            'sub_unit' => 'Penjumlahan & Pengurangan Bilangan Bulat',
            'link' => null,
        ]);

        $cells = $this->cells($this->templateBody('SMA'));
        $rows = array_slice($cells, 7);

        $this->assertSame([], $rows);
    }

    public function test_fallback_not_used_when_database_already_has_materials(): void
    {
        Material::create([
            'jenjang' => 'SMP',
            'mapel' => 'Ilmu Pengetahuan Alam',
            'curriculum' => 'K-13',
            'subelement' => 'Materi',
            'unit' => 'Satuan',
            'sub_unit' => 'Besaran dan Satuan',
            'link' => null,
        ]);

        $cells = $this->cells($this->templateBody('SMA'));
        $rows = array_slice($cells, 7);

        $this->assertNotContains('Matematika', $rows);
        $this->assertNotContains('Fungsi kuadrat', $rows);
    }

    public function test_fallback_rows_never_use_invented_subelement(): void
    {
        $cells = $this->cells($this->templateBody());
        $rows = array_slice($cells, 7);

        $this->assertNotContains('Numerasi', $rows);
        $this->assertNotContains('Literasi', $rows);
    }
}

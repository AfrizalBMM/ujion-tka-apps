<?php

namespace Tests\Feature;

use App\Models\Jenjang;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportMaterialAndGlobalQuestionValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private Jenjang $jenjang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->jenjang = Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'SMP', 'urutan' => 2]);
    }

    public function test_import_soal_resolve_material_id_hanya_dari_atribut_nama(): void
    {
        $material = Material::create([
            'jenjang' => 'SMP',
            'mapel' => 'Bahasa Indonesia',
            'curriculum' => 'Merdeka',
            'subelement' => 'Literasi',
            'unit' => 'Teks',
            'sub_unit' => 'Ide Pokok',
        ]);

        $file = UploadedFile::fake()->createWithContent('soal.csv', implode("\n", [
            'question_type,question_text,material_mapel,material_curriculum,material_subelement,material_unit,material_sub_unit,option_a,option_b,answer_key',
            'multiple_choice,Pertanyaan tanpa material_id,bahasa_indonesia,merdeka,literasi,teks,ide_pokok,Jakarta,Bandung,Jakarta',
        ]));

        $response = $this->actingAs($this->superadmin)->post(route('superadmin.global-questions.import'), [
            'jenjang_id' => $this->jenjang->id,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('global_questions', [
            'question_text' => 'Pertanyaan tanpa material_id',
            'material_id' => $material->id,
        ]);
        $this->assertStringNotContainsString('gagal diresolve', $this->flashMessage());
    }

    public function test_import_soal_tanpa_material_cocok_dihitung_di_diagnostik(): void
    {
        Material::create([
            'jenjang' => 'SMP',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Numerasi',
            'unit' => 'Aljabar',
            'sub_unit' => 'Persamaan linear',
        ]);

        $file = UploadedFile::fake()->createWithContent('soal.csv', implode("\n", [
            'question_type,question_text,material_mapel,material_curriculum,material_subelement,material_unit,material_sub_unit,option_a,option_b,answer_key',
            'multiple_choice,Soal yatim,Fisika,Merdeka,Sains,Mekanika,Newton,Jakarta,Bandung,Jakarta',
        ]));

        $response = $this->actingAs($this->superadmin)->post(route('superadmin.global-questions.import'), [
            'jenjang_id' => $this->jenjang->id,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('global_questions', ['question_text' => 'Soal yatim', 'material_id' => null]);
        $this->assertStringContainsString('material_id gagal diresolve: 1', $this->flashMessage());
    }

    public function test_import_materi_tanpa_mapel_dilewati_bukan_lolos(): void
    {
        $file = UploadedFile::fake()->createWithContent('materi.csv', implode("\n", [
            'jenjang,mapel,curriculum,subelement,unit,sub_unit,link',
            'SMP,,Merdeka,Literasi,Teks,Ide Pokok,',
            'SMP,Matematika,Merdeka,Numerasi,Aljabar,Persamaan linear,',
        ]));

        $response = $this->actingAs($this->superadmin)->post(route('superadmin.materials.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('materials', 1);
        $this->assertDatabaseHas('materials', ['mapel' => 'Matematika', 'sub_unit' => 'Persamaan linear']);
        $this->assertDatabaseMissing('materials', ['sub_unit' => 'Ide Pokok']);

        $message = $this->flashMessage();
        $this->assertStringContainsString('dilewati: 1', $message);
        $this->assertStringContainsString('kolom mapel kosong: 1', $message);
    }

    private function flashMessage(): string
    {
        return (string) session('flash.message');
    }
}

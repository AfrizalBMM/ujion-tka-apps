<?php

namespace Tests\Feature;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PasanganMenjodohkan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportUjionMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_matching_question_creates_pairs_with_text(): void
    {
        [$guru, $paket, $mapel] = $this->createGuruWithMapel();

        $globalQuestion = GlobalQuestion::create([
            'question_type' => 'menjodohkan',
            'question_text' => 'Jodohkan ibu kota berikut',
            'options' => [
                ['left' => 'Jawa Barat', 'right' => 'Bandung'],
                ['left' => 'Jawa Timur', 'right' => 'Surabaya'],
            ],
            'answer_key' => null,
            'is_active' => true,
            'jenjang_id' => Jenjang::where('kode', 'SMP')->value('id'),
        ]);

        $response = $this->actingAs($guru)
            ->post(route('guru.soal.import-ujion', [$paket, $mapel]), [
                'global_question_ids' => [$globalQuestion->id],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('soals', [
            'mapel_paket_id' => $mapel->id,
            'tipe_soal' => 'menjodohkan',
        ]);

        $pairs = PasanganMenjodohkan::query()->get();

        $this->assertCount(2, $pairs, 'Kedua pasangan harus tersimpan.');
        $this->assertTrue($pairs->contains(fn ($pair) => $pair->teks_kiri === 'Jawa Barat' && $pair->teks_kanan === 'Bandung'), 'Pasangan 1 harus berisi teks.');
        $this->assertTrue($pairs->contains(fn ($pair) => $pair->teks_kiri === 'Jawa Timur' && $pair->teks_kanan === 'Surabaya'), 'Pasangan 2 harus berisi teks.');
    }

    private function createGuruWithMapel(): array
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);

        $paket = PaketSoal::create([
            'jenjang_id' => Jenjang::where('kode', 'SMP')->firstOrFail()->id,
            'nama' => 'Paket Import',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $guru->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        return [$guru, $paket, $mapel];
    }
}

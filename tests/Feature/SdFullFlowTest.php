<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Tests\TestCase;

/**
 * Probe alur penuh superadmin: soal global → paket soal → bank builder → import → ujian.
 * Khusus jenjang SD. Dibuat oleh Rokimah (UX/UI audit).
 */
class SdFullFlowTest extends TestCase
{
    use RefreshDatabase;

    private function sa(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);
    }

    public function test_alur_penuh_sd_skenario_kondisi_awal(): void
    {
        $sa = $this->sa();
        $sd = Jenjang::where('kode', 'SD')->firstOrFail();

        $paket = PaketSoal::create([
            'jenjang_id' => $sd->id,
            'nama' => 'Paket SD Alur Penuh',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $sa->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'score',
            'kode_komponen' => 'MAT',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);

        // ── BANK GLOBAL: 6 soal SD dengan kondisi berbeda ──
        $kondisi = [
            ['material_mapel' => 'Matematika', 'is_active' => true, 'jenjang_id' => $sd->id, 'label' => 'A lengkap+benar'],
            ['material_mapel' => 'matematika', 'is_active' => true, 'jenjang_id' => $sd->id, 'label' => 'B huruf kecil'],
            ['material_mapel' => 'Numerasi', 'is_active' => true, 'jenjang_id' => $sd->id, 'label' => 'C nama beda'],
            ['material_mapel' => null, 'is_active' => true, 'jenjang_id' => $sd->id, 'label' => 'D mapel kosong'],
            ['material_mapel' => 'Matematika', 'is_active' => false, 'jenjang_id' => $sd->id, 'label' => 'E nonaktif'],
            ['material_mapel' => 'Matematika', 'is_active' => true, 'jenjang_id' => null, 'label' => 'F jenjang null'],
        ];

        foreach ($kondisi as $k) {
            GlobalQuestion::create([
                'jenjang_id' => $k['jenjang_id'],
                'question_type' => 'multiple_choice',
                'question_text' => "Soal {$k['label']}",
                'options' => ['1', '2', '3'],
                'answer_key' => '2',
                'is_active' => $k['is_active'],
                'material_mapel' => $k['material_mapel'],
            ]);
        }

        $hasil = [];
        foreach ($kondisi as $k) {
            $gq = GlobalQuestion::where('question_text', 'Soal '.$k['label'])->first();

            // filter persis seperti PaketSoalController::mapelBlock
            $params = array_filter([
                'jenjang_id' => $paket->jenjang_id,
                'material_mapel' => str($mapel->nama_mapel)->headline()->toString(),
            ]);

            $terlihat = GlobalQuestion::where('is_active', true)
                ->when($params['jenjang_id'] !== '', fn ($q) => $q->where('jenjang_id', $params['jenjang_id']))
                ->when($params['material_mapel'] !== '', fn ($q) => $q->where('material_mapel', $params['material_mapel']))
                ->where('id', $gq->id)
                ->exists();

            $terlihatTanpaMapel = GlobalQuestion::where('is_active', true)
                ->when($params['jenjang_id'] !== '', fn ($q) => $q->where('jenjang_id', $params['jenjang_id']))
                ->where('id', $gq->id)
                ->exists();

            $hasil[] = [$k['label'], $terlihat, $terlihatTanpaMapel];
        }

        fwrite(STDERR, "\n=== BANK BUILDER (filter otomatis: jenjang=SD, mapel='Matematika') ===\n");
        fwrite(STDERR, sprintf("%-22s %-10s %-10s\n", 'kondisi soal', 'terlihat', 'tanpaMapel'));
        foreach ($hasil as [$label, $t, $tm]) {
            fwrite(STDERR, sprintf("%-22s %-10s %-10s\n", $label, $t ? 'YA' : 'TIDAK', $tm ? 'YA' : 'TIDAK'));
        }

        // ── IMPORT 1 soal yang terlihat ──
        $terlihatIds = GlobalQuestion::where('is_active', true)
            ->where('jenjang_id', $sd->id)
            ->where('material_mapel', 'Matematika')
            ->pluck('id')->all();

        $this->actingAs($sa)->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
            'global_question_ids' => $terlihatIds,
        ])->assertRedirect();

        $soalCount = $mapel->soals()->count();
        fwrite(STDERR, "\n=== IMPORT ke paket ===\n");
        fwrite(STDERR, "soal masuk ke mapel: {$soalCount}\n");

        // ── BUAT UJIAN dari paket ──
        $resp = $this->actingAs($sa)->post(route('superadmin.exams.store'), [
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian SD Alur Penuh',
            'tanggal_terbit' => now()->format('Y-m-d H:i:s'),
            'max_peserta' => 30,
            'timer' => 75,
            'status' => 'draft',
        ]);
        fwrite(STDERR, 'exams.store status='.$resp->getStatusCode()."\n");

        $exam = Exam::where('judul', 'Ujian SD Alur Penuh')->first();
        $this->assertNotNull($exam, 'Ujian gagal dibuat dari paket SD');
        fwrite(STDERR, "exam terbuat: id={$exam->id} paket_soal_id={$exam->paket_soal_id}\n");

        // builder page
        $this->actingAs($sa)->get(route('superadmin.exams.builder', $exam));
        $r = $this->actingAs($sa)->get(route('superadmin.exams.builder', $exam), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ]);
        fwrite(STDERR, 'builder status='.$r->getStatusCode().' component='.$r->json('component')."\n");

        $this->assertTrue(true);
    }
}

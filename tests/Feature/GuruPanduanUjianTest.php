<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamMapelToken;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruPanduanUjianTest extends TestCase
{
    use RefreshDatabase;

    private function guru(string $jenjang = 'SD'): User
    {
        return User::factory()->create([
            'role' => User::ROLE_GURU,
            'jenjang' => $jenjang,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function jenjang(string $kode = 'SD'): Jenjang
    {
        return Jenjang::firstOrCreate(['kode' => $kode], ['nama' => 'Sekolah '.$kode, 'urutan' => 1]);
    }

    private function examDenganToken(int $jenjangId, string $token): Exam
    {
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjangId,
            'nama' => 'Paket SD',
            'tahun_ajaran' => '2026/2027',
            'is_active' => true,
            'created_by' => User::factory()->create(['role' => User::ROLE_SUPERADMIN])->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'otomatis',
            'kode_komponen' => 'matematika',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $exam = Exam::create([
            'user_id' => $paket->created_by,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian TKA SD',
            'tanggal_terbit' => now(),
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        ExamMapelToken::create([
            'exam_id' => $exam->id,
            'mapel_paket_id' => $mapel->id,
            'token' => $token,
        ]);

        return $exam;
    }

    public function test_guru_can_open_panduan_ujian(): void
    {
        $this->jenjang();

        $response = $this->actingAs($this->guru())->get(route('guru.panduan-ujian'));

        $response->assertOk();
        $response->assertSee('PanduanUjian', false);
        $response->assertSee('loginUrl', false);
    }

    public function test_panduan_menampilkan_token_ujian_sesuai_jenjang(): void
    {
        $jenjang = $this->jenjang('SD');
        $this->examDenganToken($jenjang->id, 'TOKENSD1');

        $response = $this->actingAs($this->guru('SD'))->get(route('guru.panduan-ujian'));

        $response->assertOk();
        $response->assertSee('TOKENSD1', false);
    }

    public function test_panduan_tidak_menampilkan_token_jenjang_lain(): void
    {
        $sd = $this->jenjang('SD');
        $smp = $this->jenjang('SMP');
        $this->examDenganToken($smp->id, 'TOKENSMP1');

        $response = $this->actingAs($this->guru('SD'))->get(route('guru.panduan-ujian'));

        $response->assertOk();
        $response->assertDontSee('TOKENSMP1', false);
    }

    public function test_guest_tidak_bisa_buka_panduan(): void
    {
        $this->get(route('guru.panduan-ujian'))->assertRedirect();
    }
}

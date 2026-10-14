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

class GuruPanduanUjianQaTest extends TestCase
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

    private function jenjang(string $kode): Jenjang
    {
        return Jenjang::firstOrCreate(['kode' => $kode], ['nama' => 'Sekolah '.$kode, 'urutan' => 1]);
    }

    /**
     * @param  array{status?: string, is_active?: bool}  $state
     */
    private function exam(int $jenjangId, string $token, array $state = []): Exam
    {
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjangId,
            'nama' => 'Paket '.$jenjangId,
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
            'judul' => 'Ujian '.$token,
            'tanggal_terbit' => now(),
            'timer' => 60,
            'status' => $state['status'] ?? 'terbit',
            'is_active' => $state['is_active'] ?? true,
        ]);

        ExamMapelToken::create([
            'exam_id' => $exam->id,
            'mapel_paket_id' => $mapel->id,
            'token' => $token,
        ]);

        return $exam;
    }

    public function test_guru_panduan_ujian_200_dan_memuat_halaman_inertia(): void
    {
        $this->jenjang('SD');

        $response = $this->actingAs($this->guru('SD'))->get(route('guru.panduan-ujian'));

        $response->assertOk();
        $response->assertSee('data-page', false);
        $response->assertSee('Guru\/PanduanUjian', false);
    }

    public function test_token_terbit_jenjang_sendiri_muncul_jenjang_lain_tidak(): void
    {
        $sd = $this->jenjang('SD');
        $smp = $this->jenjang('SMP');
        $this->exam($sd->id, 'TOKENSDOK');
        $this->exam($smp->id, 'TOKENSMPNO');

        $response = $this->actingAs($this->guru('SD'))->get(route('guru.panduan-ujian'));

        $response->assertOk();
        $this->assertStringContainsString('TOKENSDOK', $response->getContent());
        $this->assertStringNotContainsString('TOKENSMPNO', $response->getContent());
    }

    public function test_login_url_mengarah_ke_halaman_login_siswa(): void
    {
        $this->jenjang('SD');

        $content = $this->actingAs($this->guru('SD'))
            ->get(route('guru.panduan-ujian'))
            ->assertOk()
            ->getContent();

        $loginUrl = route('siswa.login');
        $escaped = str_replace('/', '\\/', $loginUrl);

        $this->assertStringContainsString('"loginUrl":"'.$escaped.'"', $content);
        $this->assertStringContainsString('\\/siswa\\/login', $content);
        $this->assertSame(url('/siswa/login'), $loginUrl);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->jenjang('SD');

        $this->get(route('guru.panduan-ujian'))
            ->assertRedirect(route('login'));
    }

    public function test_halaman_simulasi_ujian_tetap_200_dan_punya_tombol_ke_panduan(): void
    {
        $this->jenjang('SD');

        $response = $this->actingAs($this->guru('SD'))->get(route('guru.exams'));

        $response->assertOk();
        $response->assertSee('data-page', false);
        $response->assertSee('Guru\/Exams', false);
    }

    public function test_ujian_belum_terbit_atau_nonaktif_tidak_menampilkan_token(): void
    {
        $sd = $this->jenjang('SD');
        $this->exam($sd->id, 'TOKENDRAFT', ['status' => 'draft']);
        $this->exam($sd->id, 'TOKENOFF', ['is_active' => false]);
        $this->exam($sd->id, 'TOKENLIVE');

        $content = $this->actingAs($this->guru('SD'))
            ->get(route('guru.panduan-ujian'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('TOKENDRAFT', $content);
        $this->assertStringNotContainsString('TOKENOFF', $content);
        $this->assertStringContainsString('TOKENLIVE', $content);
    }

    public function test_guru_jenjang_tanpa_ujian_melihat_daftar_kosong(): void
    {
        $smp = $this->jenjang('SMP');
        $this->exam($smp->id, 'TOKENSMPAJA');

        $content = $this->actingAs($this->guru('SD'))
            ->get(route('guru.panduan-ujian'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('TOKENSMPAJA', $content);
        $this->assertStringContainsString('"exams":[]', $content);
    }

    public function test_guru_nonaktif_tidak_bisa_buka_panduan(): void
    {
        $this->jenjang('SD');

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'jenjang' => 'SD',
            'account_status' => User::STATUS_SUSPEND,
        ]);

        $this->actingAs($guru)
            ->get(route('guru.panduan-ujian'))
            ->assertRedirect();
    }

    public function test_siswa_tidak_bisa_buka_panduan(): void
    {
        $this->jenjang('SD');

        $siswa = User::factory()->create([
            'role' => User::ROLE_SISWA,
            'jenjang' => 'SD',
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($siswa)
            ->get(route('guru.panduan-ujian'))
            ->assertForbidden();
    }
}

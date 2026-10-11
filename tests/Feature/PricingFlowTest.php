<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit alur pricing / tombol "Langganan".
 *
 * Dibuat oleh Rokimah (UX/UI audit).
 *
 * DUA TEMUAN:
 *
 * T-1 (UX): tombol "Langganan" di halaman pricing menunjuk ke form DAFTAR,
 *           bukan halaman pembayaran. Hasil akhirnya selalu halaman
 *           "Lengkapi profil". Itu yang dilaporkan user.
 *
 * T-2 (BUG P1): TrialProfileController::complete() memanggil startTrial()
 *           TANPA memeriksa trial_status. Akun yang trial-nya sudah habis
 *           bisa mengaktifkan trial lagi tanpa batas lewat alur:
 *           pricing -> Langganan -> daftar ulang -> lengkapi profil -> trial aktif.
 *
 * Catatan: assertion di test_reset_trial_mengikuti_status_sekarang memakai
 * perilaku SAAT INI (buggy). Ubah jadi assertSame(TRIAL_EXPIRED) setelah fix.
 */
class PricingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function expiredGuru(array $extra = []): User
    {
        static $seq = 0;
        $seq++;

        return User::factory()->create(array_merge([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'trial_status' => User::TRIAL_EXPIRED,
            'trial_ends_at' => now()->subDays(30),
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Uji',
            'no_wa' => '0811111111'.str_pad((string) $seq, 2, '0', STR_PAD_LEFT),
        ], $extra));
    }

    // ─── T-1: ke mana tombol "Langganan" berakhir ────────────────────────────

    public function test_halaman_pricing_selalu_200_di_semua_kondisi_akun(): void
    {
        $kondisi = [
            'pending+belum-lengkap' => ['trial_status' => User::TRIAL_NONE, 'trial_ends_at' => null],
            'pending+trial-aktif' => ['trial_status' => User::TRIAL_ACTIVE, 'trial_ends_at' => now()->addDays(5)],
            'pending+trial-habis' => [],
            'active-berlangganan' => ['account_status' => User::STATUS_ACTIVE],
        ];

        foreach ($kondisi as $label => $extra) {
            $u = $this->expiredGuru($extra);
            $r = $this->actingAs($u)->get('/pricing');
            $this->assertSame(200, $r->getStatusCode(), "/pricing gagal untuk {$label}");
        }

        // tamu juga harus bisa lihat pricing
        auth()->logout();
        $this->assertSame(200, $this->get('/pricing')->getStatusCode());
    }

    public function test_tombol_langganan_di_pricing_mengarah_ke_register(): void
    {
        // Sumber: resources/js/Pages/Public/Pricing.vue -> route('register.guru.form')
        $this->assertSame(
            '/register/guru',
            parse_url(route('register.guru.form'), PHP_URL_PATH),
            'tujuan tombol Langganan berubah — perbarui laporan ini'
        );

        // GET register tidak membedakan sudah login atau belum
        $u = $this->expiredGuru();
        $r = $this->actingAs($u)->get(route('register.guru.form'));
        $this->assertNull($r->headers->get('Location'), 'GET register ternyata redirect — perbarui laporan');
        $this->assertSame(200, $r->getStatusCode());
    }

    public function test_setelah_klik_langganan_user_selalu_mendarat_di_lengkapi_profil(): void
    {
        // Kasus: pendaftar baru
        auth()->logout();
        $r = $this->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru-baru@example.test',
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '082222222222',
        ]);
        $this->assertSame(
            '/guru/trial/profile-complete',
            parse_url($r->headers->get('Location'), PHP_URL_PATH)
        );

        // Kasus: akun PENDING sudah ada (email sama) — persis kasus user
        auth()->logout();
        $u = $this->expiredGuru();
        $r2 = $this->post(route('register.guru'), [
            'name' => $u->name,
            'email' => $u->email,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Uji',
            'no_wa' => '081111111111',
        ]);
        $this->assertSame(
            '/guru/trial/profile-complete',
            parse_url($r2->headers->get('Location'), PHP_URL_PATH),
            'akun sudah terdaftar tidak lagi diarahkan ke lengkapi profil'
        );
    }

    public function test_middleware_trial_habis_mengarahkan_ke_pricing(): void
    {
        $u = $this->expiredGuru();
        $r = $this->actingAs($u)->get('/guru/dashboard');
        $this->assertSame('/pricing', parse_url($r->headers->get('Location'), PHP_URL_PATH));

        // ...dan dari pricing, satu-satunya CTA kembali ke register.
        // Ini loop: trial habis -> pricing -> Langganan -> daftar -> lengkapi profil -> trial aktif
        $this->assertSame('/register/guru', parse_url(route('register.guru.form'), PHP_URL_PATH));
    }

    // ─── T-2: loop trial ─────────────────────────────────────────────────────

    public function test_reset_trial_mengikuti_status_sekarang(): void
    {
        $u = $this->expiredGuru();
        $this->actingAs($u);

        // halaman lengkapi profil terbuka untuk akun expired (route tanpa middleware trial.active)
        $this->assertSame(200, $this->get(route('guru.trial.profile.show'))->getStatusCode());

        $this->post(route('guru.trial.profile.complete'), [
            'name' => $u->name,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Uji',
            'no_wa' => '081111111111',
        ]);

        $u->refresh();

        // PERILAKU SAAT INI (bug): trial di-reset jadi aktif lagi.
        // Ubah dua assertion ini jadi assertSame(TRIAL_EXPIRED) setelah fix masuk.
        $this->assertSame(User::TRIAL_ACTIVE, $u->trial_status);
        $this->assertTrue($u->trial_ends_at->isFuture());
    }
}

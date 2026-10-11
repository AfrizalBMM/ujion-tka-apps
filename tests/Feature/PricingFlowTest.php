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
 * STATUS (setelah fix P1):
 * - TrialProfileController::complete() menolak user yang trial-nya sudah
 *   habis → redirect /pricing, trial_status tetap expired.
 * - RegisterGuruController::register() (dua titik) akun PENDING + trial
 *   habis → redirect /pricing, bukan ke lengkapi profil.
 * - GET /register/guru untuk user sudah login → redirect, form hanya untuk tamu.
 * - T-1 (arah tombol Langganan) tetap urusan Rokimah, tidak disentuh di sini.
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

        // Form daftar hanya untuk tamu
        auth()->logout();
        $r = $this->get(route('register.guru.form'));
        $this->assertNull($r->headers->get('Location'), 'tamu mendapat form, bukan redirect');
        $this->assertSame(200, $r->getStatusCode());
    }

    public function test_get_register_mengalihkan_user_yang_sudah_login(): void
    {
        $u = $this->expiredGuru();
        $this->actingAs($u)->get(route('register.guru.form'))
            ->assertRedirect(route('guru.dashboard'));

        auth()->logout();
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

        // Kasus: akun PENDING sudah ada (email sama) tapi trial sudah habis —
        // tidak boleh diputar ke lengkapi profil lagi (loop trial P1).
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
            '/pricing',
            parse_url($r2->headers->get('Location'), PHP_URL_PATH),
            'akun trial habis masih diarahkan ke lengkapi profil'
        );
        $r2->assertSessionHas('flash.message');
        $this->assertAuthenticatedAs($u);
        $u->refresh();
        $this->assertSame(User::TRIAL_EXPIRED, $u->trial_status);

        // Kasus: akun PENDING belum pernah trial → tetap ke lengkapi profil
        auth()->logout();
        $fresh = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'trial_status' => User::TRIAL_NONE,
            'trial_ends_at' => null,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Segar',
            'no_wa' => '081111111199',
        ]);
        $r3 = $this->post(route('register.guru'), [
            'name' => $fresh->name,
            'email' => $fresh->email,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Segar',
            'no_wa' => '081111111199',
        ]);
        $this->assertSame(
            '/guru/trial/profile-complete',
            parse_url($r3->headers->get('Location'), PHP_URL_PATH),
            'pendaftar baru tanpa trial malah dilempar ke pricing'
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
        $endsSebelum = $u->trial_ends_at->toDateTimeString();
        $this->actingAs($u);

        // halaman lengkapi profil terbuka untuk akun expired (route tanpa middleware trial.active)
        $this->assertSame(200, $this->get(route('guru.trial.profile.show'))->getStatusCode());

        $r = $this->post(route('guru.trial.profile.complete'), [
            'name' => $u->name,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Uji',
            'no_wa' => '081111111111',
        ]);

        $r->assertRedirect(route('pricing'));
        $r->assertSessionHas('flash.message', 'Trial Anda sudah berakhir, silakan berlangganan.');

        $u->refresh();

        $this->assertSame(User::TRIAL_EXPIRED, $u->trial_status);
        $this->assertSame($endsSebelum, $u->trial_ends_at->toDateTimeString());
    }

    public function test_trial_pertama_tetap_bisa_diaktifkan(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'trial_status' => User::TRIAL_NONE,
            'trial_ends_at' => null,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Pertama',
            'no_wa' => '081111111188',
        ]);

        $this->actingAs($guru)->post(route('guru.trial.profile.complete'), [
            'name' => $guru->name,
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Pertama',
            'no_wa' => '081111111188',
        ])->assertRedirect(route('guru.dashboard'));

        $guru->refresh();
        $this->assertSame(User::TRIAL_ACTIVE, $guru->trial_status);
        $this->assertTrue($guru->trial_ends_at->isFuture());
    }
}

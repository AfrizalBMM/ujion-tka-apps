<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaBottomNavPaymentLockProbeTest extends TestCase
{
    use RefreshDatabase;

    private function lockedGuru(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
            'trial_status' => User::TRIAL_EXPIRED,
        ]);
    }

    public function test_bottom_nav_routes_blocked_server_side_when_payment_locked(): void
    {
        $guru = $this->lockedGuru();

        foreach (['guru.materials', 'guru.paket-soal.index', 'guru.exams'] as $route) {
            $response = $this->actingAs($guru)->get(route($route));
            $response->assertRedirect(route('pricing'));
            $this->assertSame(
                'Masa trial Anda telah berakhir. Silakan pilih paket berlangganan untuk melanjutkan.',
                session('warning'),
                "warning flash hilang untuk {$route}"
            );
        }
    }

    public function test_bottom_nav_routes_open_for_trial_active_guru(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
            'trial_status' => User::TRIAL_ACTIVE,
            'trial_ends_at' => now()->addDays(7),
        ]);

        foreach (['guru.materials', 'guru.paket-soal.index', 'guru.exams'] as $route) {
            $this->actingAs($guru)->get(route($route))->assertOk();
        }
    }

    public function test_bottom_nav_ships_real_hrefs_and_keeps_locked_cues(): void
    {
        $vue = file_get_contents(resource_path('js/Layouts/GuruLayout.vue'));
        $start = strpos($vue, '<div class="bottom-nav-track">');
        $this->assertNotFalse($start, 'bottom-nav-track tidak ditemukan');
        $end = strpos($vue, '</nav>', $start);
        $bottom = substr($vue, $start, $end - $start); // blok bottom-nav saja, sidebar di luar

        // link navigasi asli, bukan href="#"
        $this->assertStringNotContainsString('href="#"', $bottom);
        $this->assertStringContainsString("route('guru.materials')", $bottom);
        $this->assertStringContainsString("route('guru.paket-soal.index')", $bottom);
        $this->assertStringContainsString("route('guru.exams')", $bottom);

        // cue terkunci tetap ada
        $this->assertSame(3, substr_count($bottom, 'paymentLocked ? \'opacity-60\''));
        $this->assertSame(3, substr_count($bottom, 'fa-lock'));
        $this->assertSame(3, substr_count($bottom, 'Selesaikan pembayaran untuk membuka'));

        // modal payment masih punya pemicu lain (sidebar + dashboard cards)
        $this->assertGreaterThanOrEqual(2, substr_count($vue, 'data-payment-locked'));
        $dashboard = file_get_contents(resource_path('js/Pages/Guru/Dashboard.vue'));
        $this->assertGreaterThanOrEqual(6, substr_count($dashboard, 'data-payment-locked'));
        $this->assertStringContainsString('doku:info-open', $vue);
    }
}

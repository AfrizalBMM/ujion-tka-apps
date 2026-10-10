<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\LandingExam;
use App\Models\LandingExamMapel;
use App\Models\LandingExamOrder;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LandingExamPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_start_returns_503_when_doku_not_enabled(): void
    {
        $order = $this->createOrder();

        $response = $this->postJson(route('ujian-online.pay.start', $order->session_token));

        $response->assertStatus(503)
            ->assertJsonPath('ok', false);
    }

    public function test_start_returns_404_for_invalid_order_token(): void
    {
        $this->enableDoku();

        $response = $this->postJson(route('ujian-online.pay.start', 'invalid-token-xyz'));

        $response->assertStatus(404)
            ->assertJsonPath('ok', false);
    }

    public function test_start_returns_409_when_already_paid(): void
    {
        $this->enableDoku();
        $order = $this->createOrder();
        $order->update(['status' => LandingExamOrder::STATUS_PAID]);

        $response = $this->postJson(route('ujian-online.pay.start', $order->session_token));

        $response->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('status', 'paid');
    }

    public function test_start_resets_failed_order_to_pending(): void
    {
        $this->enableDoku();
        $order = $this->createOrder();
        $order->update([
            'status' => LandingExamOrder::STATUS_FAILED,
            'doku_invoice_number' => 'OLD-INV-001',
            'doku_transaction_status' => 'expired',
        ]);

        // Doku enabled but createCheckoutPaymentForOrder will fail (no real API).
        // Expect 502 from RuntimeException, but order status was reset first.
        $response = $this->postJson(route('ujian-online.pay.start', $order->session_token));

        $response->assertStatus(502);

        $order->refresh();
        $this->assertSame(LandingExamOrder::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertNull($order->doku_invoice_number);
        $this->assertNull($order->doku_transaction_status);
    }

    public function test_status_returns_404_for_missing_order(): void
    {
        $response = $this->getJson(route('ujian-online.pay.status', ['order_id' => 'NONEXISTENT']));

        $response->assertStatus(404)
            ->assertJsonPath('ok', false);
    }

    public function test_status_returns_order_state_for_pending(): void
    {
        $order = $this->createOrder();

        $response = $this->getJson(route('ujian-online.pay.status', ['order_id' => $order->doku_invoice_number]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', $order->status);
    }

    public function test_finish_redirects_to_landing_for_missing_order(): void
    {
        $response = $this->get(route('ujian-online.pay.finish', ['order_id' => 'NONEXISTENT']));

        $response->assertRedirect(route('landing'));
    }

    public function test_start_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->get('POST')['_post/ujian-online/pay/{orderToken}'] ?? null;

        // Fallback: find by name
        if (! $route) {
            foreach (Route::getRoutes() as $r) {
                if ($r->getName() === 'ujian-online.pay.start') {
                    $route = $r;
                    break;
                }
            }
        }

        $this->assertNotNull($route, 'Route ujian-online.pay.start not found');

        $middleware = $route->gatherMiddleware();
        $hasThrottle = false;
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }
        $this->assertTrue($hasThrottle, 'Route ujian-online.pay.start should have throttle middleware');
    }

    private function enableDoku(): void
    {
        AppSetting::putValue('doku_enabled', '1');
        AppSetting::putValue('doku_client_id', 'test-client-id');
        AppSetting::putValue('doku_secret_key', 'test-secret-key');
    }

    private function createOrder(): LandingExamOrder
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Landing Exam',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $exam = Exam::create([
            'user_id' => $superadmin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Landing Exam Test',
            'tanggal_terbit' => now(),
            'max_peserta' => 100,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $landingExam = LandingExam::create([
            'exam_id' => $exam->id,
            'jenjang' => 'SMP',
            'slug' => 'test-landing-exam',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $landingExamMapel = LandingExamMapel::create([
            'landing_exam_id' => $landingExam->id,
            'mapel_paket_id' => $mapel->id,
            'price' => 15000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return LandingExamOrder::create([
            'landing_exam_mapel_id' => $landingExamMapel->id,
            'nama' => 'Test Siswa',
            'nomor_wa' => '6281234567890',
            'session_token' => 'test-token-'.uniqid(),
            'status' => LandingExamOrder::STATUS_PENDING_PAYMENT,
            'amount' => 15000,
            'doku_invoice_number' => 'INV-'.strtoupper(uniqid()),
        ]);
    }
}

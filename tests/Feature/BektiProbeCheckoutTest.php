<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BektiProbeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response('error', 500)]);

        AppSetting::putValue('doku_enabled', '1');
        AppSetting::putValue('doku_client_id', 'BRN-test-client-id');
        AppSetting::putValue('doku_secret_key', 'SK-test-secret-key');

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 99000,
            'is_active' => true,
        ]);
    }

    public function test_superadmin_cannot_trigger_teacher_checkout(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->actingAs($admin)->postJson(route('payments.doku.start'))->assertStatus(401);
        $this->assertSame(0, Transaction::count());
    }

    public function test_siswa_cannot_trigger_teacher_checkout(): void
    {
        $siswa = User::factory()->create(['role' => User::ROLE_SISWA, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($siswa)->postJson(route('payments.doku.start'))->assertStatus(401);
        $this->assertSame(0, Transaction::count());
    }

    public function test_error_payload_does_not_leak_internal_details(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);

        $res = $this->actingAs($guru)->postJson(route('payments.doku.start'));
        $res->assertStatus(502);

        $body = $res->getContent();
        foreach (['SK-', 'BRN-', 'api.doku.com', '/home/', 'app/Services', 'Secret', 'secret_key'] as $needle) {
            $this->assertStringNotContainsString($needle, $body, "leak: {$needle}");
        }
    }

    public function test_request_body_cannot_tamper_amount_or_owner(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);

        $this->actingAs($guru)->postJson(route('payments.doku.start'), [
            'amount' => 1,
            'user_id' => 9999,
            'status' => 'success',
            'pricing_plan_id' => 999,
        ])->assertStatus(502);

        $t = $guru->transactions()->firstOrFail();
        $this->assertSame(99000.0, (float) $t->amount);
        $this->assertSame($guru->id, (int) $t->user_id);
        $this->assertSame(Transaction::STATUS_PENDING, $t->status);
    }
}

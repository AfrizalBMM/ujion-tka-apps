<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\LandingClickLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatParticipantGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_cannot_destroy_chat_between_others(): void
    {
        $superadminA = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $superadminB = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $chat = Chat::create([
            'from_user_id' => $guru->id,
            'to_user_id' => $superadminB->id,
            'message' => 'percakapan milik admin B',
            'is_read' => false,
        ]);

        $response = $this->actingAs($superadminA)
            ->post(route('superadmin.chat.destroy', $chat));

        $response->assertStatus(403);
        $this->assertDatabaseHas('chats', ['id' => $chat->id]);
    }

    public function test_chat_participant_can_destroy_own_chat(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $chat = Chat::create([
            'from_user_id' => $guru->id,
            'to_user_id' => $superadmin->id,
            'message' => 'percakapan saya',
            'is_read' => false,
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.chat.destroy', $chat));

        $response->assertRedirect();
        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
    }

    public function test_superadmin_cannot_send_chat_to_non_guru(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.chat.store'), [
                'to_user_id' => $otherAdmin->id,
                'message' => 'halo sesama admin',
            ]);

        $response->assertSessionHasErrors('to_user_id');
        $this->assertDatabaseMissing('chats', [
            'to_user_id' => $otherAdmin->id,
        ]);
    }

    public function test_landing_click_log_masks_ipv4(): void
    {
        $response = $this->postJson(route('api.landing-click'), [
            'event' => 'landing_view',
        ], ['REMOTE_ADDR' => '202.155.100.77']);

        $response->assertOk();

        $masked = LandingClickLog::query()->latest('id')->first()?->ip_address;

        $this->assertNotNull($masked);
        $this->assertStringEndsWith('.x', $masked);
        $this->assertStringStartsWith('202.155.100.', $masked);
        $this->assertStringNotContainsString('77', $masked);
    }
}

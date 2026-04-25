<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_chat_removes_image_from_storage(): void
    {
        Storage::fake('public');

        $from = User::factory()->create();
        $to = User::factory()->create();

        Storage::disk('public')->put('chat-images/sample.png', 'dummy');

        $eventFired = false;
        $pathAtDelete = null;
        $existsAtDelete = null;
        Chat::deleting(function (Chat $chat) use (&$eventFired, &$pathAtDelete, &$existsAtDelete): void {
            $eventFired = true;
            $pathAtDelete = $chat->image_path;
            $existsAtDelete = Storage::disk('public')->exists((string) $chat->image_path);
        });

        $chat = Chat::create([
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'message' => 'hello',
            'image_path' => 'chat-images/sample.png',
            'is_read' => false,
        ]);

        $chat->refresh();
        $chat->delete();

        $this->assertTrue($eventFired);
        $this->assertSame('chat-images/sample.png', $pathAtDelete);
        $this->assertTrue($existsAtDelete);
        Storage::disk('public')->assertMissing('chat-images/sample.png');
        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
    }

    public function test_superadmin_chat_uses_uploaded_guru_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/guru-chat.png', 'dummy');

        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'avatar' => 'avatars/guru-chat.png',
            'name' => 'Guru Chat Avatar',
        ]);

        Chat::create([
            'from_user_id' => $guru->id,
            'to_user_id' => $superadmin->id,
            'message' => 'Halo admin',
            'is_read' => false,
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.chat.index', ['user' => $guru->id]))
            ->assertOk()
            ->assertSee(Storage::url($guru->avatar), false);
    }

    public function test_guru_chat_uses_latest_names_and_uploaded_avatars(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/admin-chat.png', 'admin');
        Storage::disk('public')->put('avatars/guru-chat.png', 'guru');

        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
            'avatar' => 'avatars/admin-chat.png',
            'name' => 'Admin Chat Baru',
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'avatar' => 'avatars/guru-chat.png',
            'name' => 'Guru Chat Baru',
        ]);

        Chat::create([
            'from_user_id' => $superadmin->id,
            'to_user_id' => $guru->id,
            'message' => 'Pesan dari admin',
            'is_read' => false,
        ]);

        Chat::create([
            'from_user_id' => $guru->id,
            'to_user_id' => $superadmin->id,
            'message' => 'Pesan dari guru',
            'is_read' => false,
        ]);

        $this->actingAs($guru)
            ->get(route('guru.chat'))
            ->assertOk()
            ->assertSee('Admin Chat Baru')
            ->assertSee('Guru Chat Baru')
            ->assertSee(Storage::url($superadmin->avatar), false)
            ->assertSee(Storage::url($guru->avatar), false);
    }
}

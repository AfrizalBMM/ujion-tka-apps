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
}

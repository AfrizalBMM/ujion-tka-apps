<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    public const CONVERSATION_GURU_ADMIN = 'guru_admin';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'conversation_type',
        'message',
        'image_path',
        'is_read',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $chat): void {
            if (! $chat->image_path) {
                return;
            }

            $filesystem = app('filesystem');
            if (method_exists($filesystem, 'forgetDisk')) {
                $filesystem->forgetDisk('local');
            }

            $filesystem->disk('local')->delete($chat->image_path);
        });
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class TokenGenerator
{
    public const TEACHER_TOKEN_LENGTH = 6;

    public static function uniqueTeacherToken(): string
    {
        do {
            $token = strtoupper(Str::random(self::TEACHER_TOKEN_LENGTH));
        } while (User::query()->where('access_token', $token)->exists());

        return $token;
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class TokenGenerator
{
    public static function uniqueTeacherToken(): string
    {
        do {
            $token = strtoupper(Str::random(10));
        } while (User::query()->where('access_token', $token)->exists());

        return $token;
    }
}

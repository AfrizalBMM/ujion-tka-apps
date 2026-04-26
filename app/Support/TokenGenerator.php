<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class TokenGenerator
{
    public static function uniqueTeacherToken(): string
    {
        return strtoupper(Str::random(10));
    }
}

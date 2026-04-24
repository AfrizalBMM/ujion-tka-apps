<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    protected $guarded = [];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $value = static::query()->where('key', $key)->value('value');

        return $value !== null ? (string) $value : $default;
    }

    public static function putValue(string $key, ?string $value): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}

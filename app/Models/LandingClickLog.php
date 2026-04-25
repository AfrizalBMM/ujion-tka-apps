<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingClickLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'href',
        'path',
        'referrer',
        'user_agent',
        'ip_address',
    ];
}

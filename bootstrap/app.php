<?php

use App\Http\Middleware\AuditRequest;
use App\Http\Middleware\EnsureGuruAccountIsActive;
use App\Http\Middleware\EnsureGuruJenjangAccess;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/guru.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'audit' => AuditRequest::class,
            'role' => EnsureRole::class,
            'guru.active' => EnsureGuruAccountIsActive::class,
            'guru.jenjang' => EnsureGuruJenjangAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

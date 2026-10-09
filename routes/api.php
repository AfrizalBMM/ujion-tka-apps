<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\LandingClickController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\DokuPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and assigned the "api"
| middleware group. Enjoy building your API!
|
*/

Route::post('/landing-click', [LandingClickController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('api.landing-click');

Route::post('/coupons/verify', [CouponController::class, 'verify'])
    ->middleware('throttle:30,1')
    ->name('api.coupons.verify');

// Chat API (guru-admin only)
Route::middleware(['auth'])->prefix('chat')->group(function () {
    Route::get('/threads', [ChatController::class, 'threads'])->name('api.chat.threads');
    Route::post('/send', [ChatController::class, 'send'])->name('api.chat.send');
    Route::get('/messages/{thread}', [ChatController::class, 'messages'])->name('api.chat.messages');
});

Route::post('/wa-webhook', [WebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('api.wa-webhook');

Route::post('/payments/doku/notification', [DokuPaymentController::class, 'notification'])
    ->middleware('throttle:120,1')
    ->name('api.payments.doku.notification');

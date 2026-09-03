<?php

use App\Http\Controllers\Api\LandingClickController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\MidtransPaymentController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/landing-click', [LandingClickController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('api.landing-click');

Route::post('/wa-webhook', [WebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('api.wa-webhook');

Route::post('/payments/midtrans/notification', [MidtransPaymentController::class, 'notification'])
    ->middleware('throttle:120,1')
    ->name('api.payments.midtrans.notification');

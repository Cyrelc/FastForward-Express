<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use Sanctum\BillController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['guest'])->post(
    '/login', 'Auth\LoginController@getSanctumToken'
);

// Route::middleware(['auth:sanctum'])->controller(BillController::class)->group(function() {
//     Route::get('/getBillsByDriver', 'getBillsByDriver');
//     Route::post('/bills/setTime', 'setTime');
// });


Route::controller(WebhookController::class)->prefix('webhooks')->group(function() {
    Route::post('/stripe/receivePaymentIntentUpdate', 'receivePaymentIntentUpdate');
    Route::post('/stripe/receiveRefundUpdate', 'receiveRefundUpdate');
});

Route::controller(ToolController::class)->prefix('tools')->group(function() {
    Route::get('/getStripeReceipts', 'getStripeReceipts');
});


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

// Token login for the (paused) driver mobile app was removed: it issued tokens without checking the
// password. Rebuild it - verifying the password and throttling attempts - when the mobile work resumes.

// Public site forms. Stateless and CORS-enabled (config/cors.php) so a marketing site on another host can post to them
Route::middleware('throttle:public-forms')->controller(GuestController::class)->prefix('public')->group(function() {
    Route::post('/contact', 'submitContactForm');
    Route::post('/requestAccount', 'requestAccount');
    Route::post('/requestDelivery', 'requestDeliveryForm');
});

// Route::middleware(['auth:sanctum'])->controller(BillController::class)->group(function() {
//     Route::get('/getBillsByDriver', 'getBillsByDriver');
//     Route::post('/bills/setTime', 'setTime');
// });


Route::controller(WebhookController::class)->prefix('webhooks')->group(function() {
    Route::post('/stripe/receivePaymentIntentUpdate', 'receivePaymentIntentUpdate');
    Route::post('/stripe/receiveRefundUpdate', 'receiveRefundUpdate');
});

// /api/tools/getStripeReceipts moved to routes/web.php so it runs behind the session login


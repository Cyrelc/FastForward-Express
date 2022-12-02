<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Authenticated views
Route::group(['middleware' => ['auth']],
    function() {
        Route::get('/', 'HomeController@index');
        Route::get('/getDashboard', 'HomeController@getDashboard');
        Route::get('/getAppConfiguration', 'HomeController@getAppConfiguration');

        Route::get('/admin/getAccountsReceivable/{startDate}/{endDate}', 'AdminController@getAccountsReceivable');

        Route::get('/bills/chart', 'AdminController@getChart');

        Route::get('/app/{route}', 'HomeController@index');

        Route::post('/payments/accountPayment', 'PaymentController@ProcessAccountPayment');
        Route::get('/payments/accountPayment/{accountId}', 'PaymentController@GetReceivePaymentModel');
        Route::get('/payments/{accountId}', 'PaymentController@GetModelByAccountId');
        Route::delete('/payments/undo', 'PaymentController@UndoPayment');

        Route::get('/logout', 'Auth\LoginController@logout');

        Route::get('/appsettings/get', 'AdminController@getModel');
        Route::post('/appsettings/store', 'AdminController@store');
        Route::post('/appsettings/hashPassword', 'AdminController@hashPassword');
        //API
        // Route::resource('/customers', 'AccountController',
        //     ['only' => ['index', 'create', 'edit', 'store']]);
    }
);

Route::middleware(['auth'])->controller(AccountController::class)->prefix('accounts')->group(function() {
    Route::get('/chart', 'getChart');
    Route::get('/toggleActive/{accountId}', 'toggleActive');
    Route::get('/getShippingAddress', 'getShippingAddress');
    Route::post('/adjustCredit', 'adjustAccountCredit');
    Route::get('/getModel/{accountId?}', 'getModel');
    Route::post('/', 'store');
    Route::get('/', 'index');
});

Route::middleware(['auth'])->controller(BillController::class)->prefix('bills')->group(function() {
    Route::get('/create', 'getModel');
    Route::get('/template/{billId}', 'template');
    Route::post('/manageLineItemLinks', 'manageLineItemLinks');
    Route::post('/store', 'store');
    Route::post('/generateCharges', 'generateCharges');
    Route::get('/copy/{billId}', 'copyBill');
    Route::get('/print/{billId}', 'print');
    Route::get('/{billId}', 'getModel');
    Route::delete('/{billId}', 'delete');
    Route::get('/', 'index');
    Route::get('/trend', '');
});

Route::middleware(['auth'])->controller(ChargebackController::class)->prefix('chargebacks')->group(function() {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::delete('/{id}', 'delete');
});

Route::middleware(['auth'])->controller(DispatchController::class)->prefix('dispatch')->group(function() {
    Route::post('/assignBillToDriver', 'AssignBillToDriver');
    Route::get('/getDrivers', 'GetDrivers');
    Route::get('/getBills', 'GetBills');
    Route::post('/setBillPickupOrDeliveryTime', 'SetBillPickupOrDeliveryTime');
});

Route::middleware(['auth'])->controller(EmployeeController::class)->prefix('employees')->group(function() {
    Route::get('/emergencyContacts/{id?}', 'getEmergencyContactModel');
    Route::post('/emergencyContacts', 'storeEmergencyContact');
    Route::delete('/emergencyContacts', 'deleteEmergencyContact');
    Route::get('/create', 'getModel');
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'getModel');
    Route::get('/toggleActive/{id}', 'toggleActive');
});

Route::middleware(['auth'])->controller(InterlinerController::class)->prefix('interliners')->group(function() {
    Route::get('/', 'buildTable');
    Route::post('/store', 'store');
});

Route::middleware(['auth'])->controller(InvoiceController::class)->prefix('invoices')->group(function() {
    Route::get('/', 'buildTable');
    Route::delete('/{id}', 'delete');
    Route::get('/download/{invoiceIds}', 'download');
    Route::get('/finalize/{invoiceIds}', 'finalize');
    Route::post('/getAccountsToInvoice', 'getAccountsToInvoice');
    Route::get('/getModel/{invoiceId?}','getModel');
    Route::get('/getOutstanding', 'getOutstandingByAccountId');
    Route::get('/print/{invoiceIds}', 'print');
    Route::get('/printPreview/{invoiceId}', 'printPreview');
    Route::post('/', 'store');
    // Route::get('/invoices/regather/{invoiceId}', 'regather');
});

Route::middleware(['auth'])->controller(ManifestController::class)->prefix('manifests')->group(function() {
    Route::get('/getDriversToManifest', 'getDriversToManifest');
    Route::get('/{manifest_id}', 'getModel');
    Route::post('/store', 'store');
    Route::delete('/{id}', 'delete');
    Route::get('/download/{manifestIds}', 'download');
    Route::get('/print/{manifestIds}', 'print');
    Route::get('/', 'index');
});

Route::middleware(['auth'])->controller(PaymentController::class)->prefix('paymentMethods')->group(function() {
    Route::delete('/{accountId}', 'DeletePaymentMethod');
    Route::get('/{accountId}', 'GetAccountPaymentMethods');
    Route::get('/{accountId}/create', 'GetSetupIntent');
    Route::post('/{accountId}/setDefault', 'SetDefaultPaymentMethod');
});

Route::middleware(['auth'])->controller(RatesheetController::class)->prefix('ratesheets')->group(function() {
    Route::get('/create', 'getModel');
    Route::get('/{id?}', 'getModel');
    Route::get('/', 'buildTable');
    Route::post('/', 'store');
    Route::get('/{ratesheetId}/getZone', 'GetZone');
});

Route::middleware(['auth'])->controller(SearchController::class)->group(function() {
    Route::get('/search', 'Search');
});

Route::middleware(['auth'])->controller(UserController::class)->prefix('users')->group(function() {
    Route::post('/setPrimary/{account_id}/{contact_id}', 'setPrimary');
    Route::post('/changePassword/{id}', 'changePassword');
    Route::get('/generatePassword', 'generatePassword');
    Route::get('/getAccountUsers/{id}', 'getAccountUsers');
    Route::post('/storeAccountUser', 'storeAccountUser');
    Route::get('/editAccountUser/{id}', 'editAccountUser');
    Route::get('/createAccountUser/{accountId}', 'createAccountUser');
    Route::get('/deleteAccountUser/{contactId}/{accountId}', 'deleteAccountUser');
    Route::get('/getAccountUserModel/{accountId}/{contactId?}', 'getAccountUserModel');
    Route::post('/checkIfAccountUserExists', 'checkIfAccountUserExists');
    Route::get('/linkAccountUser/{contactId}/{accountId}', 'LinkAccountUser');
    Route::post('/impersonate', 'impersonate');
    Route::get('/unimpersonate', 'unimpersonate');
    Route::get('/sendPasswordReset/{userId}', 'sendPasswordResetEmail');
});

//Authenticated SPA
Route::group(['prefix' => 'app', 'middleware' => 'auth'], function() {
    Route::get('/{any_path?}', 'HomeController@index');
    Route::get('/{object}/{action}', 'HomeController@index');
    Route::get('/{object}/{action}/{object_id?}', 'HomeController@index');
});

//Guest views web
Route::middleware(['guest'])->controller(GuestController::class)->group(function() {
    Route::get('/about', 'about');
    Route::get('/contact', 'contact');
    Route::post('/contact', 'submitContactForm');
    Route::get('/home', 'home');
    Route::post('/requestAccount', 'requestAccount');
    Route::get('/requestDelivery', 'requestDelivery');
    Route::post('/requestDelivery', 'requestDeliveryForm');
    Route::get('requestQuote', 'requestQuote');
    Route::get('/services', 'services');
});

Route::auth();

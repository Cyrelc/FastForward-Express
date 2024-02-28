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

Route::middleware(['auth'])->group(function() {
    Route::get('/bills/chart', 'AdminController@getChart');
});

Route::middleware(['auth'])->controller(AccountController::class)->prefix('accounts')->group(function() {
    Route::get('/chart', 'getChart');
    Route::get('/toggleActive/{accountId}', 'toggleActive');
    Route::get('/getShippingAddress', 'getShippingAddress');
    Route::post('/adjustCredit', 'adjustAccountCredit');
    Route::get('/getModel/{accountId?}', 'getModel');
    Route::get('/billing/{accountId}', 'GetBillingModel');
    Route::post('/', 'store');
    Route::get('/', 'index');
});

Route::middleware(['auth'])->controller(AccountUserController::class)->prefix('accountUsers')->group(function() {
    Route::get('/account/{accountId}', 'getAccountUsers');
    Route::post('/', 'storeAccountUser');
    // potential problem here
    Route::get('/{accountId}/{contactId?}', 'getAccountUserModel');
    Route::delete('/{accountId}/{contactId}', 'deleteAccountUser');
    Route::post('/checkIfExists', 'checkIfAccountUserExists');
    Route::get('/link/{accountId}/{contactId}', 'LinkAccountUser');
    Route::get('/setPrimary/{account_id}/{contact_id}', 'setPrimary');
});

Route::middleware(['auth'])->controller(AdminController::class)->prefix('appsettings')->group(function() {
    Route::get('/', 'AdminController@getModel');
    Route::post('/accounting', 'AdminController@StoreAccountingSettings');
    Route::post('/scheduling/blockedDates', 'AdminController@StoreBlockedDate');
    Route::delete('/scheduling/blockedDates/{blockedDateId}', 'AdminController@DeleteAppSetting');
    Route::get('/selections', 'AdminController@getSelections');
    Route::post('/selections', 'AdminController@StoreSelection');
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
    Route::get('/', 'GetModel');
    Route::get('/getBills', 'GetBills');
    Route::post('/setBillPickupOrDeliveryTime', 'SetBillPickupOrDeliveryTime');
});

Route::middleware(['auth'])->controller(EmployeeController::class)->prefix('employees')->group(function() {
    Route::get('/{employeeId}/emergencyContacts', 'getEmergencyContacts');
    Route::get('/emergencyContacts/{id?}', 'getEmergencyContact');
    Route::post('/{employeeId}/emergencyContacts', 'storeEmergencyContact');
    Route::delete('/{employeeId}/emergencyContacts/{contactId}', 'deleteEmergencyContact');
    Route::get('/create', 'getModel');
    Route::get('/', 'index');
    Route::post('/', 'create');
    Route::put('/{employeeId}', 'update');
    Route::get('/{id}', 'getModel');
    Route::get('/toggleActive/{id}', 'toggleActive');
});

Route::middleware(['auth'])->controller(InterlinerController::class)->prefix('interliners')->group(function() {
    Route::get('/', 'buildTable');
    Route::post('/', 'store');
});

Route::middleware(['auth'])->controller(InvoiceController::class)->prefix('invoices')->group(function() {
    Route::get('/', 'buildTable');
    Route::delete('/{id}', 'delete');
    Route::get('/download/{invoiceIds}', 'download');
    Route::get('/finalize/{invoiceIds}', 'finalize');
    Route::post('/getUninvoiced', 'getUninvoiced');
    Route::get('/getModel/{invoiceId?}','getModel');
    Route::get('/getOutstanding', 'getOutstandingByAccountId');
    Route::get('/print/{invoiceIds}', 'print');
    Route::get('/printPreview/{invoiceId}', 'printPreview');
    Route::post('/', 'store');
    Route::post('/createFromCharge', 'createFromCharge');
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
    Route::get('/regather/{invoiceId}', 'Regather');
});

Route::middleware(['auth'])->controller(PaymentController::class)->prefix('payments')->group(function() {
    Route::post('/{invoiceId}', 'ProcessPayment');
    Route::get('/{invoiceId}', 'GetReceivePaymentModel');
    Route::delete('/{paymentId}', 'RevertPayment');
});

Route::middleware(['auth'])->controller(PaymentController::class)->prefix('paymentMethods')->group(function() {
    Route::delete('/{accountId}', 'DeletePaymentMethod');
    Route::get('/{accountId}', 'GetAccountPaymentMethods');
    Route::get('/{accountId}/create', 'GetSetupIntent');
    Route::post('/{accountId}/setDefault', 'SetDefaultPaymentMethod');
    Route::post('/getPaymentIntent', 'GetPaymentIntent');
});

Route::middleware(['auth'])->controller(QueryController::class)->prefix('queries')->group(function() {
    Route::post('/', 'StoreQuery');
    Route::delete('/{queryId}', 'DeleteQuery');
});

Route::middleware(['auth'])->controller(RatesheetController::class)->prefix('ratesheets')->group(function() {
    Route::get('/create', 'GetModel');
    Route::get('/{id?}', 'GetModel');
    Route::get('/', 'BuildTable');
    Route::post('/', 'Store');
    Route::get('/{ratesheetId}/getZone', 'GetZone');
    Route::delete('/conditional/{id}', 'DeleteConditional');
    Route::get('/conditional/{id}', 'GetConditional');
    Route::post('/conditional/{id?}', 'StoreConditional');
    Route::get('/conditionals/{ratesheetId}', 'ListConditionals');
});

Route::middleware(['auth'])->controller(SearchController::class)->group(function() {
    Route::get('/search', 'Search');
});

Route::middleware(['auth'])->controller(UserController::class)->prefix('users')->group(function() {
    Route::post('/changePassword/{id}', 'changePassword');
    Route::get('/generatePassword', 'generatePassword');
    Route::post('/impersonate', 'impersonate');
    Route::get('/unimpersonate', 'unimpersonate');
    Route::get('/sendPasswordReset/{userId}', 'sendPasswordResetEmail');
    Route::post('/settings', 'storeSettings');
    Route::get('/getConfiguration', 'getUserConfiguration');
});

// Authenticated views
Route::group(['middleware' => ['auth']],
    function() {
        Route::get('/', 'HomeController@index');
        Route::get('/getDashboard', 'HomeController@getDashboard');
        Route::get('/getAppConfiguration', 'HomeController@getAppConfiguration');

        Route::get('/admin/getAccountsReceivable/{startDate}/{endDate}', 'AdminController@getAccountsReceivable');
        Route::get('/admin/getAccountsPayable', 'AdminController@getAccountsPayable');

        Route::get('/logout', 'Auth\LoginController@logout');

        // Route::post('/appsettings/hashPassword', 'AdminController@hashPassword');
        //API
        // Route::resource('/customers', 'AccountController',
        //     ['only' => ['index', 'create', 'edit', 'store']]);
    }
);

// Authenticated SPA
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

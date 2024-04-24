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
    Route::get('/chart/{accountId}', 'getChart');
    Route::get('/toggleActive/{accountId}', 'toggleActive');
    Route::get('/getShippingAddress', 'getShippingAddress');
    Route::post('/adjustCredit', 'adjustAccountCredit');
    Route::get('/getModel/{accountId?}', 'getModel');
    Route::get('/billing/{accountId}', 'getBillingModel');
    Route::post('/', 'store');
    Route::get('/', 'index');
});

Route::middleware(['auth'])->controller(AccountUserController::class)->prefix('accountUsers')->group(function() {
    Route::get('/account/{accountId}', 'getAccountUsers');
    Route::post('/', 'storeAccountUser');
    // potential problem here
    Route::get('/{accountId}/{contactId?}', 'getAccountUserModel');
    Route::delete('/{accountId}/{contactId}', 'delete');
    Route::post('/checkIfExists', 'checkIfAccountUserExists');
    Route::get('/link/{accountId}/{contactId}', 'linkAccountUser');
    Route::get('/setPrimary/{account_id}/{contact_id}', 'setPrimary');
});

Route::middleware(['auth'])->controller(AdminController::class)->prefix('appsettings')->group(function() {
    Route::get('/', 'getModel');
    Route::post('/accounting', 'storeAccountingSettings');
    Route::post('/scheduling/blockedDates', 'storeBlockedDate');
    Route::delete('/scheduling/blockedDates/{blockedDateId}', 'deleteAppSetting');
    Route::get('/selections', 'getSelections');
    Route::post('/selections', 'storeSelection');
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
    Route::post('/assignBillToDriver', 'assignBillToDriver');
    Route::get('/', 'getModel');
    Route::get('/getBills', 'getBills');
    Route::post('/setBillPickupOrDeliveryTime', 'setBillPickupOrDeliveryTime');
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
    Route::get('/printBills/{invoiceId}', 'printBills');
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
    Route::get('/regather/{invoiceId}', 'regather');
});

Route::middleware(['auth'])->controller(PaymentController::class)->prefix('payments')->group(function() {
    Route::post('/{invoiceId}', 'processPayment');
    Route::get('/{invoiceId}', 'getReceivePaymentModel');
    Route::delete('/{paymentId}', 'revertPayment');
});

Route::middleware(['auth'])->controller(PaymentController::class)->prefix('paymentMethods')->group(function() {
    Route::delete('/{accountId}', 'deletePaymentMethod');
    Route::get('/{accountId}', 'getAccountPaymentMethods');
    Route::get('/{accountId}/create', 'getSetupIntent');
    Route::post('/{accountId}/setDefault', 'setDefaultPaymentMethod');
    Route::post('/getPaymentIntent', 'getPaymentIntent');
});

Route::middleware(['auth'])->controller(QueryController::class)->prefix('queries')->group(function() {
    Route::post('/', 'storeQuery');
    Route::delete('/{queryId}', 'deleteQuery');
});

Route::middleware(['auth'])->controller(RatesheetController::class)->prefix('ratesheets')->group(function() {
    Route::get('/create', 'getModel');
    Route::get('/{id?}', 'getModel');
    Route::get('/', 'buildTable');
    Route::post('/', 'store');
    Route::get('/{ratesheetId}/getZone', 'getZone');
    Route::delete('/conditional/{id}', 'deleteConditional');
    Route::get('/conditional/{id}', 'getConditional');
    Route::post('/conditional/{id?}', 'storeConditional');
    Route::get('/conditionals/{ratesheetId}', 'listConditionals');
});

Route::middleware(['auth'])->controller(SearchController::class)->group(function() {
    Route::get('/search', 'search');
});

Route::middleware(['auth'])->controller(UserController::class)->prefix('users')->group(function() {
    Route::post('/changePassword/{id}', 'changePassword');
    Route::get('/generatePassword', 'generatePassword');
    Route::get('/impersonate/{userId}', 'impersonate');
    Route::get('/unimpersonate', 'unimpersonate');
    Route::get('/sendPasswordReset/{userId}', 'sendPasswordResetEmail');
    Route::post('/settings', 'storeSettings');
    Route::get('/getConfiguration', 'getUserConfiguration');
});

// Authenticated views
Route::group(['middleware' => ['auth']],
    function() {
        Route::get('/', 'HomeController@index');
        Route::get('/lists', 'HomeController@getLists');
        Route::get('/getDashboard', 'HomeController@getDashboard');

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
    Route::get('/testExceptions', function() {
        throw new \Exception('THIS exception IS a TEST');
    });
});

Route::auth();

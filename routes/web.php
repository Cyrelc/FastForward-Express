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

/*
Controller actions:
index
create
store
show
edit
update
destroy
*/

/*
    Note: All authentication middleware also occurs in controllers.
    It is redundant here.
*/

// Authenticated views
Route::group(['middleware' => 'auth'],
    function() {
        Route::get('/', 'HomeController@index');
        Route::get('/getDashboard', 'HomeController@getDashboard');
        Route::get('/getAppConfiguration', 'HomeController@getAppConfiguration');

        Route::post('/accounts/store', 'AccountController@store');
        Route::get('/accounts/buildTable', 'AccountController@buildTable');
        Route::get('/accounts/chart', 'AccountController@getChart');
        Route::get('/accounts/toggleActive/{accountId}', 'AccountController@toggleActive');
        Route::get('/accounts/getShippingAddress', 'AccountController@getShippingAddress');
        Route::post('/accounts/adjustCredit', 'AccountController@adjustAccountCredit');
        Route::get('/accounts/getModel/{accountId?}', 'AccountController@getModel');

        Route::get('/admin/getAccountsReceivable/{startDate}/{endDate}', 'AdminController@getAccountsReceivable');

        Route::get('/app/{route}', 'HomeController@index');

        Route::get('/bills', 'BillController@buildTable');
        Route::get('/bills/chart', 'AdminController@getChart');
        Route::get('/bills/create', 'BillController@getModel');
        Route::get('/bills/{billId}', 'BillController@getModel');
        Route::delete('/bills/{billId}', 'BillController@delete');
        Route::post('/bills/manageLineItemLinks', 'BillController@manageLineItemLinks');
        Route::post('/bills/store', 'BillController@store');
        Route::post('/bills/generateCharges', 'BillController@generateCharges');
        Route::get('/bills/copy/{billId}', 'BillController@copyBill');

        Route::get('/chargebacks/buildTable', 'ChargebackController@buildTable');
        Route::post('/chargebacks/store', 'ChargebackController@store');
        Route::get('/chargebacks/delete/{id}', 'ChargebackController@delete');

        Route::post('/dispatch/assignBillToDriver', 'DispatchController@AssignBillToDriver');
        Route::get('/dispatch/getDrivers', 'DispatchController@GetDrivers');
        Route::get('/dispatch/getBills', 'DispatchController@GetBills');
        Route::post('/dispatch/setBillPickupOrDeliveryTime', 'DispatchController@SetBillPickupOrDeliveryTime');

        Route::get('/employees/buildTable', 'EmployeeController@buildTable');
        Route::get('/employees/emergencyContacts/getModel/{id?}', 'EmployeeController@getEmergencyContactModel');
        Route::post('/employees/emergencyContacts/store/{id?}', 'EmployeeController@storeEmergencyContact');
        Route::post('/employees/emergencyContacts/delete', 'EmployeeController@deleteEmergencyContact');
        Route::get('/employees/getModel/{id?}', 'EmployeeController@getModel');
        Route::post('/employees/store', 'EmployeeController@store');
        Route::get('/employees/toggleActive/{id}', 'EmployeeController@toggleActive');

        Route::get('/interliners/buildTable', 'InterlinerController@buildTable');
        Route::post('/interliners/store', 'InterlinerController@store');

        Route::get('/invoices/buildTable', 'InvoiceController@buildTable');
        Route::get('/invoices/delete/{id}', 'InvoiceController@delete');
        Route::get('/invoices/download/{invoiceIds}', 'InvoiceController@download');
        Route::get('/invoices/finalize/{invoiceIds}', 'InvoiceController@finalize');
        Route::post('/invoices/getAccountsToInvoice', 'InvoiceController@getAccountsToInvoice');
        Route::get('/invoices/getModel/{invoiceId?}','InvoiceController@getModel');
        Route::get('/invoices/getOutstanding', 'InvoiceController@getOutstandingByAccountId');
        Route::get('/invoices/print/{invoiceIds}', 'InvoiceController@print');
        Route::get('/invoices/printPreview/{invoiceId}', 'InvoiceController@printPreview');
        Route::post('/invoices/store', 'InvoiceController@store');
        // Route::get('/invoices/regather/{invoiceId}', 'InvoiceController@regather');

        Route::get('/manifests', 'ManifestController@buildTable');
        Route::get('/manifests/getDriversToManifest', 'ManifestController@getDriversToManifest');
        Route::get('/manifests/{manifest_id}', 'ManifestController@getModel');
        Route::post('/manifests/store', 'ManifestController@store');
        Route::get('/manifests/delete/{id}', 'ManifestController@delete');
        Route::get('/manifests/download/{manifestIds}', 'ManifestController@download');
        Route::get('/manifests/print/{manifestIds}', 'ManifestController@print');

        Route::post('/payments/accountPayment', 'PaymentController@ProcessAccountPayment');
        Route::get('/payments/accountPayment/{accountId}', 'PaymentController@GetReceivePaymentModel');
        Route::get('/payments/getCreditCards/{accountId}', 'PaymentController@GetCreditCardsForAccount');
        Route::get('/payments/{accountId}', 'PaymentController@GetModelByAccountId');
        Route::post('/payments/storeCreditCard', 'PaymentController@StoreCreditCard');
        Route::get('/payments/getCreditCardFull/{creditCardId}', 'PaymentController@GetCreditCardFull');

        Route::get('/ratesheets/buildTable', 'RatesheetController@buildTable');
        Route::post('/ratesheets/store', 'RatesheetController@store');
        Route::get('/ratesheets/getModel/{id?}', 'RatesheetController@getModel');

        Route::get('/logout', 'Auth\LoginController@logout');

        Route::get('/appsettings/get', 'AdminController@getModel');
        Route::post('/appsettings/store', 'AdminController@store');
        Route::post('/appsettings/hashPassword', 'AdminController@hashPassword');

        Route::post('/users/changePassword/{id}', 'UserController@changePassword');
        Route::get('/users/generatePassword', 'UserController@generatePassword');
        Route::get('/users/getAccountUsers/{id}', 'UserController@getAccountUsers');
        Route::post('/users/storeAccountUser', 'UserController@storeAccountUser');
        Route::get('/users/editAccountUser/{id}', 'UserController@editAccountUser');
        Route::get('/users/createAccountUser/{id}', 'UserController@createAccountUser');
        Route::get('/users/deleteAccountUser/{contactId}/{accountId}', 'UserController@deleteAccountUser');
        Route::get('/users/getAccountUserModel/{accountId}/{contactId?}', 'UserController@getAccountUserModel');
        Route::get('/users/checkIfEmailTaken/{email}', 'UserController@checkIfEmailTaken');
        Route::post('/users/checkIfAccountUserExists', 'UserController@checkIfAccountUserExists');
        Route::get('/users/linkAccountUser/{contactId}/{accountId}', 'UserController@LinkAccountUser');
        Route::post('/users/impersonate', 'UserController@impersonate');
        Route::get('/users/unimpersonate', 'UserController@unimpersonate');
        //API
        // Route::resource('/customers', 'AccountController',
        //     ['only' => ['index', 'create', 'edit', 'store']]);
    }
);

//Authenticated SPA
Route::group(['prefix' => 'app', 'middleware' => 'auth'], function() {
    Route::get('/{any_path?}', 'HomeController@index');
    Route::get('/{object}/{action}', 'HomeController@index');
    Route::get('/{object}/{action}/{object_id?}', 'HomeController@index');
});

//Guest views
Route::group(['middleware' => 'guest'],
    function() {
        Route::get('/about', 'GuestController@about');
        Route::get('/contact', 'GuestController@contact');
        Route::post('/contact', 'GuestController@submitContactForm');
        Route::get('/home', 'GuestController@home');
        Route::post('/requestAccount', 'GuestController@requestAccount');
        Route::get('/requestDelivery', 'GuestController@requestDelivery');
        Route::post('/requestDelivery', 'GuestController@requestDeliveryForm');
        Route::get('requestQuote', 'GuestController@requestQuote');
        Route::get('/services', 'GuestController@services');
    }
);

// Route::post('/sanctum', 'LoginController@getSanctumToken');

Route::auth();

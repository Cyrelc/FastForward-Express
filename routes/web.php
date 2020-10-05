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
Route::group(
        ['middleware' => 'auth'],
        function() {
            Route::get('/', 'HomeController@index');
            Route::get('/getList/{type}/{parameter?}', 'HomeController@getList');
            Route::get('/getDashboard', 'HomeController@getDashboard');

            Route::get('/accounts/create', 'AccountController@create');
            Route::post('/accounts/store', 'AccountController@store');
            Route::get('/accounts/edit/{id}', 'AccountController@edit');
            Route::post('/accounts/is_unique', 'AccountController@is_unique');
            Route::get('/accounts/buildTable', 'AccountController@buildTable');
            Route::get('/accounts/toggleActive/{id}', 'AccountController@toggleActive');
            Route::get('/accounts/getShippingAddress', 'AccountController@getShippingAddress');
            Route::post('/accounts/giveCredit', 'AccountController@giveAccountCredit');
            Route::post('/accounts/{id}/storeInvoiceLayout', 'AccountController@storeInvoiceLayout');

            Route::get('/amendments/delete/{id}', 'AmendmentController@delete');
            Route::post('/amendments/store', 'AmendmentController@store');

            Route::get('/app/{route}', 'HomeController@index');

            Route::get('/bills/buildTable', 'BillController@buildTable');
            Route::get('/bills/chart', 'AdminController@getChart');
            Route::get('/bills/delete/{id}', 'BillController@delete');
            Route::get('/bills/getModel/{id?}', 'BillController@getModel');
            Route::post('/bills/store', 'BillController@store');

            Route::post('/chargebacks/deactivate/{id}', 'ChargebackController@deactivate');
            Route::get('/chargebacks/edit', 'ChargebackController@edit');
            Route::get('/chargebacks', 'ChargebackController@manage');
            Route::post('/chargebacks/store', 'ChargebackController@store');
            Route::post('/chargebacks/edit/{id}', 'ChargebackController@update');

            Route::post('/dispatch/assignBillToDriver', 'DispatchController@AssignBillToDriver');
            Route::get('/dispatch/GetDrivers', 'DispatchController@GetDrivers');
            Route::post('/dispatch/setBillPickupOrDeliveryTime', 'DispatchController@SetBillPickupOrDeliveryTime');

            Route::get('/employees/buildTable', 'EmployeeController@buildTable');
            Route::get('/employees/emergencyContacts/getModel/{id?}', 'EmployeeController@getEmergencyContactModel');
            Route::post('/employees/emergencyContacts/store/{id?}', 'EmployeeController@storeEmergencyContact');
            // Route::get('/employees/emergencyContacts/setPrimary/{employee_id}/{contact_id}', 'EmployeeController@setPrimaryEmergencyContact');
            Route::post('/employees/emergencyContacts/delete', 'EmployeeController@deleteEmergencyContact');
            Route::get('/employees/getModel/{id?}', 'EmployeeController@getModel');
            Route::post('/employees/store', 'EmployeeController@store');
            Route::get('/employees/toggleActive/{id}', 'EmployeeController@toggleActive');

            Route::get('/interliners/buildTable', 'InterlinerController@buildTable');
            Route::get('/interliners/create', 'InterlinerController@create');
            Route::post('/interliners/store', 'InterlinerController@store');
            Route::get('/interliners/edit/{id}', 'InterlinerController@edit');

            Route::get('/invoices/buildTable', 'InvoiceController@buildTable');
            Route::get('/invoices/view/{id}','InvoiceController@view');
            Route::post('/invoices/store', 'InvoiceController@store');
            Route::post('/invoices/getAccountsToInvoice', 'InvoiceController@getAccountsToInvoice');
            Route::get('/invoices/delete/{id}', 'InvoiceController@delete');
            Route::get('/invoices/print/{id}', 'InvoiceController@print');
            Route::post('/invoices/printMass', 'InvoiceController@printMass');
            Route::get('/invoices/download/{filename}', 'InvoiceController@download');
            Route::get('/invoices/getOutstanding', 'InvoiceController@getOutstandingByAccountId');

            Route::get('/manifests/getDriversToManifest', 'ManifestController@getDriversToManifest');
            Route::post('/manifests/store', 'ManifestController@store');
            Route::get('/manifests/delete/{id}', 'ManifestController@delete');
            Route::get('/manifests/view/{manifest_id}', 'ManifestController@view');
            Route::get('/manifests/print/{id}', 'ManifestController@print');
            Route::get('/manifests/buildTable', 'ManifestController@buildTable');
            Route::post('/manifests/printMass', 'ManifestController@printMass');
            Route::get('/manifests/download/{filename}', 'ManifestController@download');

            Route::post('/payments/accountPayment', 'PaymentController@ProcessAccountPayment');
            Route::get('/payments/getPaymentsTableByAccount', 'PaymentController@GetPaymentsTableByAccount');

            Route::get('/ratesheets/buildTable', 'RatesheetController@buildTable');
            Route::post('/ratesheets/store', 'RatesheetController@store');
            Route::get('/ratesheets/getModel/{id?}', 'RatesheetController@getModel');

            Route::get('/logout', 'Auth\LoginController@logout');

            Route::post('/contactus', 'HomeController@ContactUs');

            Route::get('/appsettings/get', 'AdminController@getModel');
            Route::post('/appsettings/store', 'AdminController@store');
            Route::post('/appsettings/hashPassword', 'AdminController@hashPassword');

            Route::post('/users/changePassword/{id}', 'UserController@changePassword');
            Route::get('/users/generatePassword', 'UserController@generatePassword');
            Route::get('/users/getAccountUsers/{id}', 'UserController@getAccountUsers');
            Route::post('/users/storeAccountUser', 'UserController@storeAccountUser');
            Route::get('/users/editAccountUser/{id}', 'UserController@editAccountUser');
            Route::get('/users/createAccountUser/{id}', 'UserController@createAccountUser');
            Route::post('/users/deleteAccountUser', 'UserController@deleteAccountUser');
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
Route::group(
        ['middleware' => 'guest'],
        function() {
            Route::get('/about', 'GuestController@about');
            Route::get('/login', 'Auth\AuthController@getLogin');
            Route::post('/login', 'Auth\AuthController@postLogin');
        }
);

Route::auth();

Route::get('/home', 'HomeController@index');

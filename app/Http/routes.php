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
            Route::get('/', function() {
                return view('welcome');
            });

            Route::get('/accounts', 'AccountController@index');
            Route::get('/accounts/create', 'AccountController@create');
            Route::post('/accounts/store', 'AccountController@store');
            Route::get('/accounts/edit/{id}', 'AccountController@edit');
            Route::post('/accounts/is_unique', 'AccountController@is_unique');
            Route::get('/accounts/buildTable', 'AccountController@buildTable');
            Route::post('/accounts/deactivate/{id}', 'AccountController@deactivate');
            Route::post('/accounts/activate/{id}', 'AccountController@activate');
            Route::get('/accounts/getShippingAddress', 'AccountController@getShippingAddress');
            Route::post('/accounts/{id}/storeInvoiceLayout', 'AccountController@storeInvoiceLayout');

            Route::get('/bills', 'BillController@index');
            Route::get('/bills/create', 'BillController@create');
            Route::get('/bills/edit/{id}','BillController@edit');
            Route::post('/bills/storePartial/{id}', 'BillController@storePartial');
            Route::post('/bills/store', 'BillController@store');
            Route::get('/bills/delete/{id}', 'BillController@delete');
            Route::get('/bills/buildTable', 'BillController@buildTable');

            Route::post('/chargebacks/deactivate/{id}', 'ChargebackController@deactivate');
            Route::get('/chargebacks/edit', 'ChargebackController@edit');
            Route::get('/chargebacks', 'ChargebackController@manage');
            Route::post('/chargebacks/store', 'ChargebackController@store');
            Route::post('/chargebacks/edit/{id}', 'ChargebackController@update');

            Route::get('/dispatch', 'DispatchController@view');

            Route::get('/employees', 'EmployeeController@index');
            Route::get('/employees/create', 'EmployeeController@create');
            Route::post('/employees/store', 'EmployeeController@store');
            Route::get('/employees/edit/{id}', 'EmployeeController@edit');
            Route::post('/employees/action', 'EmployeeController@action');
            Route::get('/employees/buildTable', 'EmployeeController@buildTable');
            Route::get('/employees/getEmergencyContacts/{id}', 'EmployeeController@getEmergencyContactsTable');
            Route::get('/employees/editEmergencyContact/{id}', 'EmployeeController@editEmergencyContact');
            Route::post('/employees/editEmergencyContact', 'EmployeeController@storeEmergencyContact');
            Route::post('/employees/deleteEmergencyContact/{id}', 'EmployeeController@deleteEmergencyContact');
            Route::get('/employees/createEmergencyContact/{id}', 'EmployeeController@createEmergencyContact');

            Route::get('/interliners/create', 'InterlinerController@create');
            Route::post('/interliners/store', 'InterlinerController@store');
            Route::get('/interliners/edit/{id}', 'InterlinerController@edit');
            Route::get('/interliners', 'InterlinerController@index');

            Route::get('/invoices/generate', 'InvoiceController@generate');
            Route::get('/invoices', 'InvoiceController@index');
            Route::get('/invoices/buildTable', 'InvoiceController@buildTable');
            Route::get('/invoices/view/{id}','InvoiceController@view');
            Route::post('/invoices/store', 'InvoiceController@store');
            Route::post('/invoices/getAccountsToInvoice', 'InvoiceController@getAccountsToInvoice');
            Route::get('/invoices/delete/{id}', 'InvoiceController@delete');
            Route::get('/invoices/print/{id}', 'InvoiceController@print');
            Route::post('/invoices/printMass', 'InvoiceController@printMass');
            Route::get('/invoices/download/{filename}', 'InvoiceController@download');
            Route::get('/invoices/getOutstanding', 'InvoiceController@getOutstandingByAccountId');

            Route::get('/manifests/generate', 'ManifestController@generate');
            Route::get('/manifests/getDriversToManifest', 'ManifestController@getDriversToManifest');
            Route::post('/manifests/store', 'ManifestController@store');
            Route::get('/manifests/delete/{id}', 'ManifestController@delete');
            Route::get('/manifests', 'ManifestController@index');
            Route::get('/manifests/view/{manifest_id}', 'ManifestController@view');
            Route::get('/manifests/print/{id}', 'ManifestController@print');
            Route::get('/manifests/buildTable', 'ManifestController@buildTable');
            Route::post('/manifests/printMass', 'ManifestController@printMass');
            Route::get('/manifests/download/{filename}', 'ManifestController@download');

            Route::post('/payments/accountPayment', 'PaymentController@ProcessAccountPayment');
            Route::get('/payments/getPaymentsTableByAccount', 'PaymentController@GetPaymentsTableByAccount');

            Route::get('/logout', 'Auth\LoginController@logout');

            Route::post('/contactus', 'HomeController@ContactUs');

            Route::get('/appsettings', 'AdminController@load');
            Route::post('/appsettings/storeGST', 'AdminController@storeGST');
            Route::post('/appsettings/hashPassword', 'AdminController@hashPassword');

            Route::post('/users/changePassword/{id}', 'UserController@changePassword');
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

//Guest views
Route::group(
        ['middleware' => 'guest'],
        function() {
            Route::get('/login', 'Auth\AuthController@getLogin');
            Route::post('/login', 'Auth\AuthController@postLogin');
        }
);

Route::auth();

Route::get('/home', 'HomeController@index');

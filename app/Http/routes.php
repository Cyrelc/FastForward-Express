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
            Route::post('/accounts/action', 'AccountController@action');
            Route::post('/accounts/is_unique', 'AccountController@is_unique');

            Route::get('/bills', 'BillController@index');
            Route::get('/bills/create', 'BillController@create');
            Route::get('/bills/edit/{id}','BillController@edit');
            Route::post('/bills/store', 'BillController@store');
            Route::get('/bills/delete/{id}', 'BillController@delete');

            Route::get('/chargebacks', 'ChargebackController@manage');

            Route::get('/employees', 'EmployeeController@index');
            Route::get('/employees/create', 'EmployeeController@create');
            Route::post('/employees/store', 'EmployeeController@store');
            Route::get('/employees/edit/{id}', 'EmployeeController@edit');
            Route::post('/employees/action', 'EmployeeController@action');

            Route::get('/interliners/create', 'InterlinerController@create');
            Route::post('/interliners/store', 'InterlinerController@store');
            Route::get('/interliners/edit/{id}', 'InterlinerController@edit');
            Route::get('/interliners', 'InterlinerController@index');

            Route::get('/invoices/generate', 'InvoiceController@generate');
            Route::get('/invoices', 'InvoiceController@index');
            Route::get('/invoices/view/{id}','InvoiceController@view');
            Route::post('/invoices/store', 'InvoiceController@store');
            Route::get('/invoices/layouts/{id}', 'InvoiceController@layouts');
            Route::post('/invoices/storeLayout', 'InvoiceController@storeLayout');
            Route::post('/invoices/getAccountsToInvoice', 'InvoiceController@getAccountsToInvoice');
            Route::get('/invoices/delete/{id}', 'InvoiceController@delete');
            Route::get('/invoices/print/{id}', 'InvoiceController@print');

            Route::post('/partials/contact/', 'PartialsController@NewContact');
            Route::post('/partials/phone', 'PartialsController@NewPhone');

            Route::get('/logout', 'Auth\AuthController@getLogout');

            Route::post('/contactus', 'HomeController@ContactUs');

            Route::get('/appsettings', 'AdminController@load');
            Route::post('/appsettings/storeGST', 'AdminController@storeGST');

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

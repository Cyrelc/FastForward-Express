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
            Route::post('/accounts/submitEdit', 'AccountController@submitEdit');
            Route::post('/accounts/is_unique', 'AccountController@is_unique');

            Route::get('/bills', 'BillController@index');
            Route::get('/bills/create', 'BillController@create');
            Route::get('/bills/edit/{id}','BillController@edit');
            Route::post('/bills/store', 'BillController@store');

            Route::get('/drivers', 'DriverController@index');
            Route::get('/drivers/create', 'DriverController@create');
            Route::post('/drivers/store', 'DriverController@store');
            Route::get('/drivers/edit/{id}', 'DriverController@edit');
            Route::post('/drivers/submitEdit', 'DriverController@submitEdit');
            Route::post('/drivers/action', 'DriverController@action');

            Route::get('/invoices/generate', 'InvoiceController@generate');
            Route::get('/invoices', 'InvoiceController@index');
            Route::post('/invoices/store', 'InvoiceController@store');
            Route::get('/invoices/layouts/{id}', 'InvoiceController@layouts');
            Route::post('/invoices/getAccountsToInvoice', 'InvoiceController@getAccountsToInvoice');

            Route::post('/partials/getcontact', 'PartialsController@GetContact');

            Route::get('/logout', 'Auth\AuthController@getLogout');

            Route::post('/contactus', 'HomeController@ContactUs');

            Route::get('/appsettings', 'HomeController@AppSettings');

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

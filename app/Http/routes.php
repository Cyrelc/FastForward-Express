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
            Route::get('/accounts/edit', 'AccountController@edit');
            Route::post('/accounts/submitEdit', 'AccountController@submitEdit');
            Route::post('/accounts/get', 'AccountController@get');

            Route::resource('/bills', 'BillController',
                    ['only' => ['index', 'create', 'edit', 'store']]
            );
            Route::post('/bills/get', 'BillController@getData');

            Route::get('/drivers', 'DriverController@index');
            Route::get('/drivers/create', 'DriverController@create');
            Route::post('/drivers/store', 'DriverController@store');
            Route::get('/drivers/edit', 'DriverController@edit');
            Route::post('/drivers/submitEdit', 'DriverController@submitEdit');

            Route::resource('/invoices', 'InvoiceController',
                    ['only' => 'index']);

            Route::get('/logout', 'Auth\AuthController@getLogout');

            Route::post('/contactus', 'HomeController@ContactUs');

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
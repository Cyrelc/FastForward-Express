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

            Route::resource('/bills', 'BillController',
                    ['only' => ['index', 'create', 'edit', 'store']]
            );

            Route::resource('/invoices', 'InvoiceController',
                    ['only' => 'index']);

            Route::resource('/customers', 'AccountController',
                    ['only' => ['index', 'create', 'edit', 'store']]);

            Route::resource('/drivers', 'DriverController',
                    ['only' => 'index']);

            Route::get('/logout', 'Auth\AuthController@getLogout');

            //API
            Route::post('/bills/get', 'BillController@getData');
            Route::post('/accounts/get', 'AccountController@getData');
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

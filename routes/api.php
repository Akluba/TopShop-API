<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/login', 'Auth\AuthController@login');
Route::post('/auth/refresh', 'Auth\AuthController@refresh');

Route::post('/emailReminders', 'Shared\ReminderController@index');

Route::middleware(['auth:api'])->group(function() {

	/**
	 * Auth Routes.
	 */
	Route::get('/auth/currentUser', 'Auth\AuthController@currentUser');
	Route::post('/auth/logout', 'Auth\AuthController@logout');

	Route::resource('users', 'Auth\UserController', ['only' => ['index','store','update','destroy']]);

	Route::get('/dash', 'Shared\DashController@index');
	Route::get('/dash/notes/{source_class?}/{field_id?}', 'Shared\DashController@notes');

	Route::get('/search', 'Shared\SearchController@index');
	Route::get('/search/{field_id}', 'Shared\SearchController@show');
	Route::post('/search', 'Shared\SearchController@results');

	/**
	 * Field set up Routes.
	 */
	Route::apiResource('category', 'Setup\CategoryController');
	Route::resource('field', 'Setup\FieldController', ['only' => ['store','show','update','destroy']]);
	Route::resource('column', 'Setup\ColumnController', ['only' => ['store','show','update','destroy']]);
	Route::resource('option', 'Setup\OptionController', ['only' => ['store','update','destroy']]);

	Route::resource('shops', 'Shops\ShopController', ['only' => ['index','store','show','update','destroy']]);
	Route::resource('managers', 'Managers\ManagerController', ['only' => ['index','store','show','update','destroy']]);
	Route::resource('companies', 'Companies\CompanyDetailController', ['only' => ['index','store','show','update','destroy']]);
	Route::resource('vendors', 'Vendors\VendorController', ['only' => ['index','store','show','update','destroy']]);
	Route::resource('cpr', 'CPR\CPRController', ['only' => ['index','store','show','update','destroy']]);

	Route::resource('company/requirements', 'Companies\CompanyRequirementsController', ['only' => ['index','store','show','update','destroy']]);
});
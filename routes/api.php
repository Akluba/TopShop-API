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

Route::post('/login', 'Auth\AuthController@login');
Route::post('/login/refresh', 'Auth\AuthController@refresh');

Route::middleware(['auth:api'])->group(function() {
	Route::get('/user', function (Request $request) {
		return $request->user();
	});
	Route::apiResource('category', 'Setup\CategoryController');
	Route::resource('field', 'Setup\FieldController', ['only' => ['store','show','update','destroy']]);
	Route::resource('column', 'Setup\ColumnController', ['only' => ['store','show','update','destroy']]);
	Route::resource('option', 'Setup\OptionController', ['only' => ['store','update','destroy']]);
});
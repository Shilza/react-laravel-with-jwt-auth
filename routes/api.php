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


Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'Auth\AuthController@login');
    Route::post('logout', 'Auth\AuthController@logout');
    Route::post('refresh', 'Auth\AuthController@refresh')->middleware('jwt_refresh');
    Route::post('me', 'Auth\AuthController@me');
    Route::post('payload', 'Auth\AuthController@payload');
    Route::post('register', 'Auth\AuthController@register');
    Route::post('/login/{social}/callback','Auth\LoginController@handleProviderCallback')->where('social','twitter|facebook|linkedin|google|');

    Route::group(['prefix' => 'password'], function (){
        Route::post('create', 'Auth\PasswordResetController@create');
        Route::post('reset', 'Auth\PasswordResetController@reset');
    });
});

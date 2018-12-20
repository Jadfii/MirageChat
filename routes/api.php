<?php

use App\User;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('messages')->group(function () {
    Route::post('/', 'MessageController@store');

    Route::delete('{message}', 'MessageController@delete');

    Route::put('{message}/read', 'MessageController@read');

    Route::get('/', 'MessageController@index');
    Route::get('{message}', 'MessageController@show');
    Route::get('{message}/channel', 'MessageController@show_channel');
});

Route::prefix('channels')->group(function () {
    Route::post('/', 'ChannelController@store');
    Route::post('{channel}', 'MessageController@store');

    Route::delete('{channel}', 'ChannelController@delete');

    Route::put('{channel}', 'ChannelController@update');
    Route::put('{channel}/read', 'ChannelController@read');

    Route::get('/', 'ChannelController@index');
    Route::get('{channel}', 'ChannelController@show');
    Route::get('{channel}/messages', 'ChannelController@show_messages');
});

Route::prefix('users')->group(function () {
    Route::post('{user}', 'UserController@update');

    Route::put('{user}/{status}', 'UserController@status');

    Route::get('/', 'UserController@index');
    Route::get('{user}', 'UserController@show');
});

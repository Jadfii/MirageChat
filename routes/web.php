<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('2fa')->group(function () {
    Route::post('/', 'Google2FAController@create');
    Route::post('/submit', 'Google2FAController@store');
});

Route::post('/webhooks/users/status', 'UserController@status_web');


Route::get('/home', function () {
    return redirect('/');
});
Route::get('/', 'HomeController@index')->name('home')->middleware('verified');

Auth::routes(['verify' => true]);
Route::get('/password/reset/{token}/{email}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');

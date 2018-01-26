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

Route::get('/', function () {
    return view('welcome');
});

Route::get('api_check_all.json', 'ApiCheckController@runAll');
Route::get('api_check/{key}.json', 'ApiCheckController@runOne');
Route::group(['prefix' => 'fireBug'], function() {
    Route::get('order_refund/{order_sn}', 'OrderRefundController@orderRefund');
});

Route::get('api_list.json', 'ApiCheckController@apiList');

Route::get('{all}', function () {
    return view('vue');
});

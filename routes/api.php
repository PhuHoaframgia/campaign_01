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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['namespace' => 'Api', 'middleware' => ['auth:api', 'xssProtection']], function () {
    Route::group(['prefix' => '/campaign', 'as' => 'campaign.'], function () {
        Route::post('post-create', 'CampaignController@create')->name('create');
        Route::delete('post-delete', 'CampaignController@delete')->name('delete');
    });

    Route::group(['prefix' => '/event', 'as' => 'event.'], function () {
        Route::post('create', 'EventController@create')->name('create');
        Route::patch('/update-event/{id}', 'EventController@update')->name('update-event');
        Route::patch('/update-setting-event/{id}', 'EventController@updateSetting')->name('update-setting');
    });
});

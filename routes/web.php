<?php

use Illuminate\Support\Facades\Route;
use idvLab\LaravelYookassa\Http\Middleware\IpAccess;

Route::group(['middleware' => [IpAccess::class]], function () {
    Route::namespace('idvLab\LaravelYookassa\Http\Controllers')->group(function () {
        Route::post('/yookassa/notifications', 'NotificationController@index')->name('yookassa.notifications');
    });
});

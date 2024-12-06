<?php

use idvLab\LaravelYookassa\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/yookassa/notifications', [NotificationController::class, 'index'])->name('yookassa.notifications');

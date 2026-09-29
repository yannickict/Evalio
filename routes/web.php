<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home');

Route::middleware('auth')->group(function () {
    Route::view('/feedback', 'feedback');
});

Route::view('/register', 'register')->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::view('/login', 'login')->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

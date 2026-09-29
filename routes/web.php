<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/register', 'register')->name('register');
Route::view('/login', 'login')->name('login');

Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/approve', [ApprovalController::class, 'index'])
        ->name('approve');

    Route::patch('/approve/{user}', [ApprovalController::class, 'update'])
        ->name('approve.update');

    Route::delete('/approve/{user}', [ApprovalController::class, 'destroy'])
        ->name('approve.destroy');
});

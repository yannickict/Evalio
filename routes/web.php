<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseSessionController;
use App\Http\Controllers\FeedbackResponseController;
use App\Http\Controllers\QuestionnaireTemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home.index')->name('home');
Route::view('/register', 'pages.auth.register')->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::view('/login', 'pages.auth.login')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('questionnaire')->name('feedback.')->controller(FeedbackResponseController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/', 'store')->name('store');
});

Route::middleware('auth')->group(function () {
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->middleware('can:view-course-lists')->name('index');
        Route::view('/create', 'pages.courses.create')->name('create');
    });

    Route::prefix('questionnaires')->name('questionnaires.')->controller(QuestionnaireTemplateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/preview', 'preview')->name('preview');
    });

    Route::name('sessions.')->controller(CourseSessionController::class)->group(function () {
        Route::get('/overview', 'index')->middleware('can:view-course-lists')->name('index');
        Route::get('/session/create', 'create')->name('create');
        Route::post('/session', 'store')->name('store');
    });

    Route::prefix('users')->name('users.')->middleware('can:manage-users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });
});

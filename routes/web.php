<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home.index')->name('home');
Route::view('/register', 'pages.auth.register')->name('register');
Route::view('/login', 'pages.auth.login')->name('login');

Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::post('/questionnaire', [QuestionnaireController::class, 'submit'])
    ->name('questionnaire.submit');

Route::middleware('auth')->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::view('/courses/create', 'pages.courses.create')->name('courses.create');

    Route::get('/questionnaires', [QuestionnaireController::class, 'library'])->name('questionnaires.index');
    Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('questionnaires.store');
    Route::get('/questionnaires/create', [QuestionnaireController::class, 'create'])
        ->name('questionnaires.create');

    Route::post('/questionnaires/preview', [QuestionnaireController::class, 'preview'])
        ->name('questionnaires.preview');

    Route::get('/session/create', [SessionController::class, 'index'])
        ->name('sessionscreate');

    Route::post('/session', [SessionController::class, 'store'])
        ->name('sessions.store');

    Route::get('/overview', [OverviewController::class, 'index'])
        ->name('overview');

    Route::get('/users', [UserController::class, 'index'])
        ->name('users');

    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->name('users.update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy');
});

Route::get('/questionnaire', [QuestionnaireController::class, 'index'])
    ->name('questionnaire');

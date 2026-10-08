<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseImportController;
use App\Http\Controllers\CourseSessionController;
use App\Http\Controllers\EvaluationResultsController;
use App\Http\Controllers\FeedbackResponseController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionnaireTemplateController;
use App\Http\Controllers\SessionImportController;
use App\Http\Controllers\SqlDumpController;
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
    Route::prefix('profile')->name('profile.')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'show')->name('show');
        Route::patch('/', 'update')->name('update');
        Route::patch('/password', 'updatePassword')->middleware('throttle:6,1')->name('password.update');
    });

    Route::prefix('courses')->name('courses.')->controller(CourseController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:view-course-lists')->name('index');
        Route::get('/create', 'create')->middleware('can:create-courses')->name('create');
        Route::post('/', 'store')->middleware('can:create-courses')->name('store');
        Route::get('/{course}/edit', 'edit')->middleware('can:edit-courses')->name('edit');
        Route::patch('/{course}', 'update')->middleware('can:edit-courses')->name('update');
        Route::delete('/{course}', 'delete')->middleware('can:delete-courses')->name('delete');
    });

    Route::prefix('questionnaires')->name('questionnaires.')->controller(QuestionnaireTemplateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->middleware('can:create-questionnaires')->name('create');
        Route::get('/{template}/duplicate', 'duplicate')->middleware('can:create-questionnaires')->name('duplicate');
        Route::post('/', 'store')->middleware('can:create-questionnaires')->name('store');
        Route::post('/preview', 'preview')->middleware('can:create-questionnaires')->name('preview');
        Route::delete('/{template}', 'delete')->middleware('can:delete-questionnaires')->name('delete');
    });

    Route::name('sessions.')->controller(CourseSessionController::class)->group(function () {
        Route::get('/overview', 'index')->middleware('can:view-course-lists')->name('index');
        Route::get('/session/create', 'create')->name('create');
        Route::get('/session/{courseSession}/edit', 'edit')->middleware('can:edit-sessions')->name('edit');
        Route::patch('/session/{courseSession}', 'update')->middleware('can:edit-sessions')->name('update');
        Route::post('/session', 'store')->name('store');
        Route::patch('/session/{courseSession}/evaluation', 'updateEvaluationStatus')
            ->middleware('can:manage-evaluations')
            ->name('evaluation.update');
        Route::delete('/delete/{courseSession}', 'delete')->middleware('can:delete-sessions')->name('delete');
    });
    Route::get('/session/{courseSession}/results', [EvaluationResultsController::class, 'show'])
        ->middleware('can:view-evaluation-results,courseSession')->name('sessions.results');

    Route::prefix('users')->name('users.')->middleware('can:manage-users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->middleware('can:manage-settings')->group(function () {
        Route::view('/', 'pages.settings.index')->name('index');
        Route::view('/imports', 'pages.settings.imports')->name('imports');
        Route::get('/courses-import', [CourseImportController::class, 'create'])->name('courses-import.create');
        Route::post('/courses-import', [CourseImportController::class, 'store'])->name('courses-import.store');
        Route::post('/sql-dump', [SqlDumpController::class, 'store'])
            ->name('sql-dump');

        Route::post('/backup', [BackupController::class, 'store'])
            ->name('backup');

        Route::get('/sessions-import', [SessionImportController::class, 'create'])
            ->name('sessions-import.create');

        Route::post('/sessions-import', [SessionImportController::class, 'store'])
            ->name('sessions-import.store');
    });
});

Route::middleware('guest')
    ->controller(PasswordResetController::class)
    ->group(function () {
        Route::get('/forgot-password', 'create')
            ->name('password.request');

        Route::post('/forgot-password', 'store')
            ->middleware('throttle:5,1')
            ->name('password.email');

        Route::get('/reset-password/{token}', 'edit')
            ->name('password.reset');

        Route::post('/reset-password', 'update')
            ->middleware('throttle:5,1')
            ->name('password.update');
    });

<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::prefix('password')->middleware('guest')->group(function () {
        Route::post('/forgot', [PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::post('/reset', [NewPasswordController::class, 'store'])->name('password.store');
    });

    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/email/resend', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });

    Route::prefix('user')->middleware('verified')->group(function () {
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/', [UserController::class, 'update']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::post('/cancel-email-change', [UserController::class, 'cancelEmailChange']);
    });
});

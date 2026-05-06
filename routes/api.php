<?php

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest')
        ->name('register');
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login');
});

Route::prefix('auth/password')->middleware('guest')->group(function () {
    Route::post('/forgot', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::post('/reset', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::prefix('auth/email')->group(function () {
    Route::get('/verify/{id}/{hash}',[VerifyEmailController::class,'verify'] )
        ->middleware(['signed'])
        ->name('verification.verify');
});
Route::post('profile', [ProfileController::class, 'store'])->middleware('auth:sanctum');
//Route::get('profile/get', [ProfileController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::prefix('auth/email')->group(function () {
        Route::post('/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware(['auth:sanctum','throttle:6,1'])
            ->name('verification.send');
    });

    // Route::prefix('')->group(function () {
    // });
});

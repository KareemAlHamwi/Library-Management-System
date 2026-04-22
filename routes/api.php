<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum']);

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');




Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');



Route::get('/verify-email/{id}/{hash}', function (Request $request, $id, $hash) {

    $user = User::findOrFail($id);


    if (! $request->hasValidSignature()) {
        return response()->json([
            'message' => 'Invalid or expired verification link'
        ], 403);
    }


    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return response()->json([
            'message' => 'Invalid verification hash'
        ], 403);
    }


    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email already verified'
        ]);
    }


    $user->markEmailAsVerified();

    event(new Verified($user));

    return response()->json([
        'message' => 'Email verified successfully'
    ]);
})->middleware(['signed'])->name('verification.verify');



















// Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
//     ->middleware([ 'signed', 'throttle:6,1'])
//     ->name('verification.verify');

// Route::get('/verify-email/{id}/{hash}', function (EmailVerificationRequest $request) {

//     $request->fulfill();

//     return response()->json([
//         'message' => 'Email verified successfully'
//     ]);

// })->middleware(['signed'])->name('verification.verify');
///////////////////////////////////////////////////////////////////////////
// Route::post('/login', [AuthenticatedSessionController::class, 'store'])
//     ->middleware('guest')
//     ->name('login');


//////////////////////////////////////
// Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
//     ->middleware(['auth:sanctum'])
//     ->name('logout');

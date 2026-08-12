<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Author\AuthorsController;
use App\Http\Controllers\Book\BooksController;
use App\Http\Controllers\Book\GoogleBooksController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\Reward\RewardController;
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
    /////////////////////////Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/{book}', [CartController::class, 'update']);
        Route::delete('/{book}', [CartController::class, 'remove']);
        Route::delete('/', [CartController::class, 'clear']);
    });
    ////////////////////////////////////////////////

    // Wallet Routes
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/transactions/{type}', [WalletController::class, 'transactionsByType']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/wallet/request-topup', [WalletController::class, 'requestTopUp']);
    Route::get('/wallet/my-topup-requests', [WalletController::class, 'myTopUpRequests']);



     // Reward Routes (User)
    Route::post('/reward/request-convert', [RewardController::class, 'requestConversion']);
    Route::get('/reward/my-requests', [RewardController::class, 'myConversionRequests']);
    Route::get('/reward/balance', [RewardController::class, 'balance']);
    Route::get('/reward/history', [RewardController::class, 'history']);

    // ==================== Routes للأدمن فقط ====================
    Route::middleware('can:is-admin')->group(function () {

        // ===== Wallet Admin Routes =====

        Route::get('/wallet/admin/list', [WalletController::class, 'adminListWallets']);

        // تعبئة مباشرة
        Route::post('/wallet/admin-topup', [WalletController::class, 'adminTopUp']);
        Route::post('/wallet/admin-topup/{userId}', [WalletController::class, 'adminTopUpUser']);

        // طلبات تعبئة المحفظة
        Route::get('/wallet/admin/topup-requests', [WalletController::class, 'adminListTopUpRequests']);
        Route::post('/wallet/admin/approve-topup/{requestId}', [WalletController::class, 'adminApproveTopUp']);
        Route::post('/wallet/admin/reject-topup/{requestId}', [WalletController::class, 'adminRejectTopUp']);

        // سحب وإدارة
        Route::post('/wallet/admin-withdraw', [WalletController::class, 'withdraw']);
        Route::get('/wallet/admin/{userId}', [WalletController::class, 'adminShowWallet']);
        // Route::get('/wallet/admin/list', [WalletController::class, 'adminListWallets']);

        // ===== Reward Admin Routes =====
        // تحويل مباشر
        Route::post('/reward/admin-convert', [RewardController::class, 'adminConvertPoints']);

        // طلبات تحويل النقاط
        Route::get('/reward/admin/requests', [RewardController::class, 'adminListConversionRequests']);
        Route::post('/reward/admin/approve/{requestId}', [RewardController::class, 'adminApproveConversion']);
        Route::post('/reward/admin/reject/{requestId}', [RewardController::class, 'adminRejectConversion']);
    });

    // Purchase Routes
    Route::post('/purchase/checkout', [PurchaseController::class, 'checkout']);
    Route::post('/purchase/checkout/loyalty', [PurchaseController::class, 'checkoutWithLoyaltyPoints']);
    // ==================== Reward Routes ====================
    Route::get('/reward/balance', [RewardController::class, 'balance']);
    Route::get('/reward/history', [RewardController::class, 'history']);
    Route::post('/reward/redeem', [RewardController::class, 'redeem']);
    Route::post('/reward/convert', [RewardController::class, 'convertPoints']); // تحويل نقاط الشراء إلى نقاط ولاء
    //////////////////////////////////////////////////
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/email/resend', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
    ///////////////////////////////////


    Route::prefix('user')->middleware('verified')->group(function () {
        Route::get('/me', [UserController::class, 'getCurrentUser']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/', [UserController::class, 'update']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::post('/cancel-email-change', [UserController::class, 'cancelEmailChange']);
    });




    Route::prefix('books')->middleware('verified')->group(function () {
        Route::get('/', [BooksController::class, 'list']);
        Route::get('/recommended', [BooksController::class, 'recommended']);
        Route::get('/new-arrivals', [BooksController::class, 'newArrivals']);
        Route::get('/popular', [BooksController::class, 'popular']);
        Route::get('/{bookId}', [BooksController::class, 'get']);

        Route::middleware('can:is-admin')->group(function () {
            Route::post('/', [BooksController::class, 'add']);
            Route::put('/{bookId}', [BooksController::class, 'update']);






            Route::delete('/{authorId}', [BooksController::class, 'delete']);

        });
    });

    Route::prefix('books/google')->middleware(['verified', 'can:is-admin'])->group(function () {
        Route::get('/search', [GoogleBooksController::class, 'search']);
        Route::get('/{volumeId}', [GoogleBooksController::class, 'getVolume']);
    });

    Route::prefix('authors')->middleware('verified')->group(function () {
        Route::get('/', [AuthorsController::class, 'list']);
        Route::get('/{authorId}', [AuthorsController::class, 'get']);

        Route::middleware('can:is-admin')->group(function () {
            Route::post('/', [AuthorsController::class, 'add']);
            Route::put('/{authorId}', [AuthorsController::class, 'update']);
            Route::delete('/{authorId}', [AuthorsController::class, 'delete']);
        });
    });

    Route::prefix('categories')->middleware('verified')->group(function () {
        Route::get('/', [CategoryController::class, 'list']);
        Route::get('/{categoryId}', [CategoryController::class, 'get']);

        Route::middleware('can:is-admin')->group(function () {
            Route::post('/', [CategoryController::class, 'add']);
            Route::delete('/{categoryId}', [CategoryController::class, 'delete']);
        });
    });
});

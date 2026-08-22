<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Author\AuthorsController;
use App\Http\Controllers\Book\BooksController;
use App\Http\Controllers\Book\GoogleBooksController;
use App\Http\Controllers\Borrow\BorrowController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Event\SubmissionController;
use App\Http\Controllers\Favorite\FavoritesController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Reward\RewardController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Wallet\WalletController;
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

Route::get('/books', [BooksController::class, 'list']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // ///////////////////////Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/{book}', [CartController::class, 'update']);
        Route::delete('/{book}', [CartController::class, 'remove']);
        Route::delete('/', [CartController::class, 'clear']);
    });
    // //////////////////////////////////////////////

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

    // ==================== Reward Routes ====================
    Route::get('/reward/balance', [RewardController::class, 'balance']);
    Route::get('/reward/history', [RewardController::class, 'history']);

    // ==================== Routes للأدمن فقط ====================
    Route::middleware('can:is-admin')->group(function () {

        // ===== Wallet Admin Routes =====

        Route::get('/wallet/admin/list', [WalletController::class, 'adminListWallets']);

        Route::post('/wallet/admin-topup', [WalletController::class, 'adminTopUp']);
        Route::post('/wallet/admin-topup/{userId}', [WalletController::class, 'adminTopUpUser']);

        Route::get('/wallet/admin/topup-requests', [WalletController::class, 'adminListTopUpRequests']);
        Route::post('/wallet/admin/approve-topup/{requestId}', [WalletController::class, 'adminApproveTopUp']);
        Route::post('/wallet/admin/reject-topup/{requestId}', [WalletController::class, 'adminRejectTopUp']);

        Route::post('/wallet/admin-withdraw', [WalletController::class, 'withdraw']);
        Route::get('/wallet/admin/{userId}', [WalletController::class, 'adminShowWallet']);

        // ===== Reward Admin Routes =====

        Route::post('/reward/admin-convert', [RewardController::class, 'adminConvertPoints']);

        Route::get('/reward/admin/requests', [RewardController::class, 'adminListConversionRequests']);
        Route::post('/reward/admin/approve/{requestId}', [RewardController::class, 'adminApproveConversion']);
        Route::post('/reward/admin/reject/{requestId}', [RewardController::class, 'adminRejectConversion']);
        Route::post('/reward/convert', [RewardController::class, 'adminConvertPoints']);
        // Admin Purchases
        Route::get('/admin/purchases', [PurchaseController::class, 'getAllPurchases']);
        Route::get('/admin/users/{userId}/purchases', [PurchaseController::class, 'getUserPurchases']);
        Route::get('/admin/books/{bookId}/buyers', [PurchaseController::class, 'getBookBuyers']);
        Route::get('/admin/purchases/statistics', [PurchaseController::class, 'getPurchaseStatistics']);
        // });
    });

    // Purchase Routes
    Route::post('/purchase/checkout', [PurchaseController::class, 'checkout']);
    Route::post('/purchase/checkout/loyalty', [PurchaseController::class, 'checkoutWithLoyaltyPoints']);
    Route::get('/purchase/all', [PurchaseController::class, 'getAllMypurchases']);
    // ////////////////////////////////////////////////
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/email/resend', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
    // /////////////////////////////////
    // Notifications
    Route::get('/notifications/type/{type}', [NotificationController::class, 'getByType']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'delete']);
    Route::delete('/notifications/read/delete', [NotificationController::class, 'deleteRead']);
    Route::delete('/notifications/unread/delete', [NotificationController::class, 'deleteUnread']);
    Route::delete('/notifications/type', [NotificationController::class, 'deleteByType']);
    Route::delete('/notifications/all', [NotificationController::class, 'deleteAll']);

    Route::prefix('user')->middleware('verified')->group(function () {
        Route::get('/me', [UserController::class, 'getCurrentUser']);
        Route::get('/{id}', [UserController::class, 'show'])->middleware('can:is-admin');
        Route::delete('/delete/{id}', [UserController::class, 'destroy'])->middleware('can:is-admin');
        Route::put('/admin/users/{userId}/role', [UserController::class, 'changeRole'])->middleware('can:is-admin');
        Route::get('/admin/users/all', [UserController::class, 'listAll'])->middleware('can:is-admin');
        Route::get('/admin/users', [UserController::class, 'index'])->middleware('can:is-admin');
        Route::put('/', [UserController::class, 'update']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::post('/cancel-email-change', [UserController::class, 'cancelEmailChange']);
    });

    Route::prefix('books')->middleware('verified')->group(function () {
        Route::get('/recommended', [BooksController::class, 'recommended']);
        Route::get('/new-arrivals', [BooksController::class, 'newArrivals']);
        Route::get('/popular', [BooksController::class, 'popular']);
        Route::get('/{bookId}', [BooksController::class, 'get']);

        Route::middleware('can:is-admin')->group(function () {
            Route::post('/', [BooksController::class, 'add']);
            Route::put('/{bookId}', [BooksController::class, 'update']);

            // //
            //   Route::post('/category', [BooksController::class, 'storeCategory']);

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
            Route::post('/{categoryId}', [CategoryController::class, 'update']);
            Route::delete('/{categoryId}', [CategoryController::class, 'delete']);
        });
    });

    Route::prefix('favorites')->middleware('verified')->group(function () {
        Route::get('/', [FavoritesController::class, 'index']);
        Route::get('/{book}', [FavoritesController::class, 'check']);
        Route::post('/{book}/toggle', [FavoritesController::class, 'toggle']);
    });

    Route::get('/books/{book}/reviews', [ReviewController::class, 'index']);
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store']);
    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::middleware('can:is-admin')->group(function () {
        Route::get('/admin/reviews', [ReviewController::class, 'adminIndex']);
        Route::post('/admin/reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::delete('/admin/reviews/{review}', [ReviewController::class, 'adminDestroy']);
    });

    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/my-events', [EventController::class, 'myEvents']);
    Route::post('/events/{event}/join', [EventController::class, 'join']);
    Route::delete('/events/{event}/leave', [EventController::class, 'leave']);

    Route::post('/events/{event}/submissions', [SubmissionController::class, 'store']);
    Route::get('/my-submissions', [SubmissionController::class, 'mySubmissions']);

    Route::middleware('can:is-admin')->prefix('admin')->group(function () {
        Route::get('/events', [EventController::class, 'adminIndex']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::patch('/events/{event}/status', [EventController::class, 'changeStatus']);

        Route::get('/events/{event}/submissions', [SubmissionController::class, 'eventSubmissions']);
        Route::post('/submissions/{submission}/approve', [SubmissionController::class, 'approve']);
        Route::post('/submissions/{submission}/reject', [SubmissionController::class, 'reject']);
    });

    Route::middleware('can:is-supervisor')->prefix('supervisor')->group(function () {
        Route::get('/events', [EventController::class, 'supervisorIndex']);
        Route::post('/events', [EventController::class, 'supervisorStore']);
        Route::put('/events/{event}', [EventController::class, 'supervisorUpdate']);
        Route::delete('/events/{event}', [EventController::class, 'supervisorDestroy']);
        Route::patch('/events/{event}/status', [EventController::class, 'supervisorChangeStatus']);

        Route::get('/events/{event}/submissions', [SubmissionController::class, 'supervisorEventSubmissions']);
        Route::post('/submissions/{submission}/approve', [SubmissionController::class, 'supervisorApprove']);
        Route::post('/submissions/{submission}/reject', [SubmissionController::class, 'supervisorReject']);
    });

    Route::post('/books/{book}/borrow', [BorrowController::class, 'store']);
    Route::get('/my-borrows', [BorrowController::class, 'myBorrows']);
    Route::get('/my-borrows/{id}', [BorrowController::class, 'show']);
    Route::get('/my-fines', [BorrowController::class, 'myFines']);
    Route::post('/fines/{fine}/pay', [BorrowController::class, 'payFine']);

    Route::middleware('can:is-admin')->prefix('admin')->group(function () {
        Route::get('/borrows', [BorrowController::class, 'adminIndex']);
        Route::get('/borrows/{id}', [BorrowController::class, 'adminShow']);
        Route::post('/borrows/{borrow}/approve', [BorrowController::class, 'approve']);
        Route::post('/borrows/{borrow}/reject', [BorrowController::class, 'reject']);
        Route::post('/borrows/{borrow}/return', [BorrowController::class, 'markReturned']);
        Route::post('/borrows/{borrow}/overdue', [BorrowController::class, 'markOverdue']);
    });
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminIdentityVerificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IdentityVerificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\AdminSubscriptionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RentalRequestController;
use App\Http\Controllers\Api\SavedItemController;

Route::prefix('v1')->group(function () {
    // 1. Public Routes (متاحة للجميع بدون تسجيل دخول)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);

    // 2. Protected Routes (تتطلب Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::post('/profile', [ProfileController::class, 'update']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::match(['put', 'post'], '/products/{product}', [ProductController::class, 'update']);
        Route::post('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // 3. Saved Items Routes (المفضلة / العناصر المحفوظة)
        Route::get('/saved-items', [SavedItemController::class, 'index']);
        Route::post('/saved-items', [SavedItemController::class, 'store']);
        Route::delete('/saved-items/{product}', [SavedItemController::class, 'destroy']);
        Route::post('/products/{product}/toggle-save', [SavedItemController::class, 'toggle']);

        // 4. Rental Requests Routes
        Route::get('/rental-requests', [RentalRequestController::class, 'index']);
        Route::get('/rental-requests/my', [RentalRequestController::class, 'myRequests']);
        Route::post('/rental-requests', [RentalRequestController::class, 'store']);
        Route::patch('/rental-requests/{rentalRequest}/status', [RentalRequestController::class, 'updateStatus']);
        Route::patch('/rental-requests/{rentalRequest}/cancel', [RentalRequestController::class, 'cancel']);

        // 5. Subscription Routes
        Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);
        Route::get('/my-subscription', [SubscriptionController::class, 'current']);
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/subscriptions', [AdminSubscriptionController::class, 'index']);
            Route::patch('/subscriptions/{subscription}/approve', [AdminSubscriptionController::class, 'approve']);
            Route::patch('/subscriptions/{subscription}/reject', [AdminSubscriptionController::class, 'reject']);
        });

        // 6. Notification Routes
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // 6. Identity Verification Routes
        Route::post('/identity-verifications', [IdentityVerificationController::class, 'store']);
        Route::get('/identity-verifications/current', [IdentityVerificationController::class, 'current']);
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            Route::get('/identity-verifications', [AdminIdentityVerificationController::class, 'index']);
            Route::patch('/identity-verifications/{identityVerification}/approve', [AdminIdentityVerificationController::class, 'approve']);
            Route::patch('/identity-verifications/{identityVerification}/reject', [AdminIdentityVerificationController::class, 'reject']);
        });

        // 7. Payment Routes
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/payments', [PaymentController::class, 'adminIndex']);
            Route::patch('/payments/{payment}/verify', [PaymentController::class, 'verify']);
            Route::patch('/payments/{payment}/reject', [PaymentController::class, 'reject']);
        });
    });
});

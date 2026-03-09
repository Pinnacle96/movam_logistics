<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\Api\SupportChatController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ConfigController;

// Public Auth Routes
Route::post('/register/otp', [AuthController::class, 'sendRegistrationOtp']);
Route::post('/register/customer', [AuthController::class, 'registerCustomer']);
Route::post('/register/merchant', [AuthController::class, 'registerMerchant']);
Route::post('/register/rider', [AuthController::class, 'registerRider']);
Route::post('/login', [AuthController::class, 'login']);

// Public Data Routes
Route::get('/config', [ConfigController::class, 'index']);
Route::get('/merchants', [MerchantController::class, 'index']);
Route::get('/merchants/{id}', [MerchantController::class, 'show']);
Route::get('/products/public', [ProductController::class, 'publicIndex']);
Route::get('/categories/public', [CategoryController::class, 'index']);
Route::get('/reviews/{type}/{id}', [ReviewController::class, 'index']);

//\App\Http\Controllers\Api\
// Public Webhooks (Exclude CSRF)
Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Payment Routes
    Route::post('/payments/initialize', [PaymentController::class, 'initializePayment']);
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);

    // Wallet Routes
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::post('/wallet/add-funds', [WalletController::class, 'addFunds']);
    Route::post('/wallet/verify-funds', [WalletController::class, 'verifyAddFunds']);
    Route::post('/wallet/withdraw/request', [WalletController::class, 'requestWithdrawal']);
    Route::post('/wallet/withdraw/confirm', [WalletController::class, 'confirmWithdrawal']);
    Route::get('/banks', [WalletController::class, 'getBanks']);

    // Product Routes (Merchant)
    Route::get('/products/categories', [ProductController::class, 'categories']);
    Route::patch('/products/{product}/toggle-availability', [ProductController::class, 'toggleAvailability']);

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'storeUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        
        Route::get('/merchants', [AdminController::class, 'merchants']);
        Route::get('/merchants/{merchant}', [AdminController::class, 'showMerchant']);
        Route::patch('/merchants/{merchant}/status', [AdminController::class, 'updateMerchantStatus']);
        
        Route::get('/riders', [AdminController::class, 'riders']);
        Route::get('/riders/{rider}', [AdminController::class, 'showRider']);
        Route::patch('/riders/{rider}/status', [AdminController::class, 'updateRiderStatus']);
        
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);
    });

    // Merchant Routes
    Route::prefix('merchant')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\MerchantController::class, 'dashboard']);
        Route::get('/orders', [\App\Http\Controllers\Api\MerchantController::class, 'orders']);
        Route::get('/products', [\App\Http\Controllers\Api\MerchantController::class, 'products']);
    });

    // Rider Routes
    Route::prefix('rider')->group(function () {
        Route::get('/dashboard', [RiderController::class, 'dashboard']);
        Route::get('/available-orders', [RiderController::class, 'availableOrders']);
        Route::get('/my-deliveries', [RiderController::class, 'myDeliveries']);
        Route::post('/orders/{order}/accept', [RiderController::class, 'acceptOrder']);
        Route::post('/location', [RiderController::class, 'updateLocation']);
    });

    // Order Routes
    Route::apiResource('orders', OrderController::class);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Product & Category Routes
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);

    // Transaction Routes
    Route::get('/transactions', [TransactionController::class, 'index']);

    // Coupon Routes
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);
    Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);

    // Dispute Routes
    Route::get('/disputes', [DisputeController::class, 'index']);
    Route::post('/disputes', [DisputeController::class, 'store']);
    Route::post('/disputes/{dispute}/resolve', [DisputeController::class, 'resolve']);

    // Support Chat Routes
    Route::get('/chat', [SupportChatController::class, 'index']);
    Route::post('/chat', [SupportChatController::class, 'store']);
    Route::post('/chat/read', [SupportChatController::class, 'markAsRead']);

    // Subscription Routes
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
    Route::post('/admin/subscriptions/plans', [SubscriptionController::class, 'storePlan']);

    // Analytics Routes
    Route::get('/admin/analytics', [AnalyticsController::class, 'adminOverview']);
    Route::get('/merchant/analytics', [AnalyticsController::class, 'merchantOverview']);

    // Review Routes
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Referral Routes
    Route::get('/referrals', [ReferralController::class, 'index']);

    // Address Routes
    Route::apiResource('addresses', UserAddressController::class);

    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    
    // Admin Broadcast
    Route::post('/admin/notifications/broadcast', [NotificationController::class, 'broadcast']);
});

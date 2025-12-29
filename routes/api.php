<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\HotelReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomReviewController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Public hotel routes
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
Route::get('/hotels/{hotel}/reviews', [HotelReviewController::class, 'index']);
Route::get('/hotels/{hotel}/reviews/stats', [HotelReviewController::class, 'stats']);

// Public room routes
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/rooms/{room}/reviews', [RoomReviewController::class, 'index']);
Route::get('/rooms/{room}/reviews/stats', [RoomReviewController::class, 'stats']);

// Public booking routes
Route::post('/bookings/check-availability', [BookingController::class, 'checkAvailability']);

// Stripe webhook (must be outside auth middleware)
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Protected user routes (admin only)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    
    // Protected hotel routes
    // create: admin only (checked in HotelPolicy)
    // update: admin or hotel_owner (for their hotels only, checked in HotelPolicy)
    // delete: admin only (checked in HotelPolicy)
    
    // يمكن استخدام middleware هنا أيضاً: Route::middleware('role:admin')->group(...)
    // لكننا نستخدم Policies لأنها أكثر مرونة (تتحقق من الملكية أيضاً)
    Route::post('/hotels', [HotelController::class, 'store']);
    Route::put('/hotels/{id}', [HotelController::class, 'update']);
    Route::delete('/hotels/{id}', [HotelController::class, 'destroy']);
    Route::post('/hotels/{id}/images', [HotelController::class, 'uploadImages']);
    Route::delete('/hotels/{id}/images', [HotelController::class, 'deleteImage']);
    
    // Protected room routes
    // create: admin or hotel_owner (checked in RoomPolicy)
    // update: admin or hotel_owner (for their hotels only, checked in RoomPolicy)
    // delete: admin or hotel_owner (for their hotels only, checked in RoomPolicy)
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    Route::post('/rooms/{id}/images', [RoomController::class, 'uploadImages']);
    Route::delete('/rooms/{id}/images', [RoomController::class, 'deleteImage']);
    
    // Protected booking routes
    // index: filtered by role (admin: all, hotel_owner: their hotels, user: their own)
    // create: user only (checked in BookingPolicy)
    // show: admin (all), hotel_owner (their hotels), user (their own) - checked in BookingPolicy
    // cancel: admin (all), hotel_owner (their hotels), user (their own) - checked in BookingPolicy
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    
    // Protected payment routes
    Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
    
    // Protected favorites routes
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);
    Route::post('/favorites/remove', [FavoriteController::class, 'remove']);
    Route::get('/favorites/check', [FavoriteController::class, 'check']);
    
    // Protected hotel review routes
    Route::post('/hotels/{hotel}/reviews', [HotelReviewController::class, 'store']);
    Route::get('/hotels/{hotel}/reviews/check', [HotelReviewController::class, 'check']);
    
    // Protected room review routes
    Route::post('/rooms/{room}/reviews', [RoomReviewController::class, 'store']);
    Route::get('/rooms/{room}/reviews/check', [RoomReviewController::class, 'check']);
    
    // Protected review routes (Update & Delete)
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
});


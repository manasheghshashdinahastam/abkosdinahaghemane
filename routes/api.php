<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserVerificationController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAdvertisementController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminKycController;
use Illuminate\Support\Facades\Route;

Route::get('advertisements', [AdvertisementController::class, 'index']);
Route::get('advertisements/{id}', [AdvertisementController::class, 'show']);
Route::post('advertisements', [AdvertisementController::class, 'store'])
    ->middleware(['auth:sanctum', 'verified.user']);
Route::post('advertisements/{advertisement}/contact', [AdvertisementController::class, 'contact'])
    ->middleware(['auth:sanctum', 'verified.user']);
Route::get('banks', [BankController::class, 'index']);
Route::get('banks/{id}/plans', [BankController::class, 'plans']);
Route::get('locations/provinces', [LocationController::class, 'provinces']);
Route::get('locations/{provinceId}/cities', [LocationController::class, 'cities']);

Route::prefix('auth')->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('complete-registration', [AuthController::class, 'completeRegistration']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::post('kyc', [ProfileController::class, 'submitKyc']);
    Route::get('kyc/status', [ProfileController::class, 'kycStatus']);
});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::get('profile/avatar', [ProfileController::class, 'showAvatar'])->name('user.profile.avatar');
    Route::post('kyc/submit', [ProfileController::class, 'submitKyc']);
    Route::get('kyc/status', [ProfileController::class, 'kycStatus']);
    Route::get('kyc/history', [ProfileController::class, 'kycHistory']);
    Route::get('kyc/{verificationId}/details', [ProfileController::class, 'kycDetails'])->name('user.kyc.details');
    Route::get('kyc/{verificationId}/documents/{type}', [ProfileController::class, 'kycDocument'])->name('user.kyc.document');
});

Route::get('admin/kyc/media/{kyc}/{type}', [AdminKycController::class, 'showMedia'])
    ->name('admin.kyc.media')
    ->middleware('signed');

Route::middleware(['auth:sanctum', 'role_or_permission:super_admin|admin|financial_manager|operator|auditor'])
    ->prefix('admin')->group(function () {
    Route::get('me', [AdminController::class, 'me'])->middleware('role.audit');
    Route::get('dashboard', [AdminController::class, 'dashboard'])->middleware('role.audit');
    Route::get('dashboard/operator-stats', [AdminController::class, 'operatorStats'])->middleware(['role.audit', 'permission:ads.view']);
    Route::get('ads/pending', [AdminAdvertisementController::class, 'pending'])->middleware(['role.audit', 'permission:ads.view']);
    Route::get('ads/{advertisement}', [AdminAdvertisementController::class, 'show'])->middleware(['role.audit', 'permission:ads.view']);
    Route::patch('ads/{advertisement}/approve', [AdminAdvertisementController::class, 'approve'])->middleware(['role.audit', 'permission:ads.approve']);
    Route::post('ads/{advertisement}/reject', [AdminAdvertisementController::class, 'reject'])->middleware(['role.audit', 'permission:ads.reject']);
    Route::get('kyc/pending', [AdminKycController::class, 'pending'])->middleware(['role.audit', 'permission:users.verify']);
    Route::get('kyc/{verification}', [UserVerificationController::class, 'show'])->name('admin.kyc.show')->middleware(['role.audit', 'permission:users.verify']);
    Route::patch('kyc/{verification}/approve', [UserVerificationController::class, 'approve'])->name('admin.kyc.approve')->middleware(['role.audit', 'permission:users.verify']);
    Route::post('kyc/{verification}/reject', [UserVerificationController::class, 'reject'])->name('admin.kyc.reject')->middleware(['role.audit', 'permission:users.verify']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->middleware(['role.audit', 'permission:users.ban']);
    Route::get('ads', fn () => response()->json(['data' => []]))->middleware(['role.audit', 'permission:ads.view']);
    Route::get('finance', fn () => response()->json(['data' => []]))->middleware(['role.audit', 'permission:finance.view']);
    Route::get('users', [AdminUserController::class, 'index'])->middleware(['role.audit', 'permission:users.view']);
    Route::get('verifications', [UserVerificationController::class, 'index'])->name('admin.verifications.index')->middleware(['role.audit', 'permission:users.verify']);
    Route::get('verifications/{verification}', [UserVerificationController::class, 'show'])->name('admin.verifications.show')->middleware(['role.audit', 'permission:users.verify']);
    Route::get('verifications/{verification}/documents/{field}', [UserVerificationController::class, 'document'])->name('admin.verifications.document')->middleware(['role.audit', 'permission:users.verify']);
    Route::patch('verifications/{verification}/review', [UserVerificationController::class, 'review'])->name('admin.verifications.review')->middleware(['role.audit', 'permission:users.verify']);
});

Route::middleware('auth:sanctum')->prefix('operator')->group(function () {
    Route::get('ads', fn () => response()->json(['data' => []]))->middleware(['role.audit', 'role:super-admin|operator']);
});

Route::middleware(['auth:sanctum', 'verified.user'])->prefix('user')->group(function () {
    Route::get('ads', [AdvertisementController::class, 'userAds'])->middleware(['role.audit', 'role:buyer|seller']);
    Route::get('advertisements', [AdvertisementController::class, 'userAds'])->middleware(['role.audit', 'role:buyer|seller']);
    Route::put('ads/{advertisement}', [AdvertisementController::class, 'update'])->middleware('verified.user');
    Route::put('advertisements/{advertisement}', [AdvertisementController::class, 'update'])->middleware('verified.user');
    Route::patch('ads/{advertisement}/status', [AdvertisementController::class, 'changeStatus']);
    Route::patch('advertisements/{advertisement}/status', [AdvertisementController::class, 'changeStatus']);
    Route::delete('ads/{advertisement}', [AdvertisementController::class, 'destroy']);
    Route::delete('advertisements/{advertisement}', [AdvertisementController::class, 'destroy']);
    Route::get('bookmarks', [BookmarkController::class, 'index']);
    Route::post('bookmarks/{advertisementId}', [BookmarkController::class, 'toggle']);
    Route::post('advertisements/{advertisement}/bookmark', [BookmarkController::class, 'toggleByModel']);
    Route::delete('bookmarks/{advertisementId}', [BookmarkController::class, 'destroy']);
});

Route::post('admin/login', [AuthController::class, 'adminLogin']);
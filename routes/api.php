<?php

/* ------------------------------------------------------------ */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SubscriberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 */

// Public routes (no auth required)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Password reset routes
Route::post('password/email', [AuthController::class, 'sendResetOTP']);
Route::post('password/verify-otp', [AuthController::class, 'verifyResetOTP'])->name('password.verify-otp');
Route::post('password/reset', [AuthController::class, 'passwordReset'])->name('password.reset');

// Routes requiring authentication
Route::middleware('auth:api')->group(function () {
    // General authenticated routes for all logged-in users
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Settings (accessible by all authenticated users)
    Route::middleware('role:admin,author,editor')->prefix('settings')->group(function () {
        Route::put('password', [SettingController::class, 'storeOrUpdatePassword']);
        Route::post('info', [SettingController::class, 'storeOrUpdate']);
        Route::get('info', [SettingController::class, 'index']);
    });

    // Admin-only routes
    Route::middleware(['role:admin,editor,author'])->group(function () {
        // Full management of categories & subcategories
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('subcategories', SubCategoryController::class);
        Route::post('change-color', [SettingController::class, 'storeOrUpdateColor']);

        Route::apiResource('comment', CommentController::class)->only(['store']);


        Route::middleware('role:admin')->group(function () {
            // Role management
            Route::apiResource('roles', RoleManagementController::class);
            Route::apiResource('comment', CommentController::class)->only(['store', 'update', 'destroy']);
            Route::patch('contents/update-status/{id}', [ContentController::class, 'updateStatus']);

        });
    });



    // Author-only routes
    Route::middleware('role:author,admin,editor')->group(function () {
        // Authors can create content but only update/delete their own content (check in controller)
        Route::prefix('contents')->group(function () {
            Route::post('/', [ContentController::class, 'store']);
            Route::put('/{id}', [ContentController::class, 'update']);
            Route::delete('/{id}', [ContentController::class, 'destroy']);


        });
        Route::apiResource('comment', CommentController::class)->only(['store']);
    });



    // Subscriber/User-only routes
    Route::middleware('role:user')->group(function () {
        // for user
        Route::post('updateInfo', [SettingController::class, 'storeOrUpdateForUser']);
        Route::get('updateInfo', [SettingController::class, 'ShowsForUser']);
        Route::put('update-password', [SettingController::class, 'storeOrUpdatePasswordForUser']);
        Route::post('update-pic', [SettingController::class, 'profileUpdateOrStore']);
        Route::get('update-pic', [SettingController::class, 'showsProfilePic']);

        Route::apiResource('comment', CommentController::class)->only(['store']);
    });
});

// Public GET routes
Route::get('/shows', [ContentController::class, 'showContents']);  // List all content

// Route::get('contents/', [ContentController::class, 'index']);
Route::get('contents/{cat_id}/{sub_id}/{id}', [ContentController::class, 'index']);  // single content for edit
Route::get('contents/{cat_id}/{sub_id}', [ContentController::class, 'indexForSubCategory']);
Route::get('contents/{cat_id}/{sub_id}/{contentId}', [ContentController::class, 'relatedContents']);
Route::get('contents/{cat_id}', [ContentController::class, 'indexFrontend']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('subcategories', [SubCategoryController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('comment', CommentController::class)->only([
        'store', 'update', 'destroy'
    ]);;
});

Route::post('/subscribe', [SubscriberController::class, 'store']);
Route::get('change-color', [SettingController::class, 'showColor']);
Route::post('/upvote-downvote/{commentId}/vote', [ContentController::class, 'vote']);

Route::get('upvote-downvote', [ContentController::class, 'getVotes']);
// Route::get('comment/{content_id}', [CommentController::class, 'index']);
Route::get('comment/content/{content_id}', [CommentController::class, 'index']);
Route::get('footer', [SettingController::class, 'footer']);
Route::get('contents/landing-page', [ContentController::class, 'landingPage']);
Route::get('contents/{slug}', [ContentController::class,'showAllTags']);
// Route::get('contents/{slug}', [ContentController::class,'showAllTags']);

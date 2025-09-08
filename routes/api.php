<?php

/* ------------------------------------------------------------ */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\FooterController;
use App\Http\Controllers\FooterSectionController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\NewsletterApiController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PolicyController;
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

    Route::post('/contents/{content}/like', [ContentController::class, 'toggleLike']);
    Route::post('/contents/{content}/share', [ContentController::class, 'share']);

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
            // Route::post('status/{id}', [ContentController::class, 'storeOrUpdateStatus']);
            Route::post('privacy-policy', [PolicyController::class, 'storeOrUpdatePrivacyPolicy']);
            Route::post('terms-conditions', [PolicyController::class, 'storeOrUpdateTermsConditions']);
            Route::post('cookie-policy', [PolicyController::class, 'storeOrUpdateCookiesPolicy']);
            Route::post('investment-disclaimer', [PolicyController::class, 'storeOrUpdateInvestmentDisclaimer']);

            Route::apiResource('footer-menu', FooterController::class);
            Route::post('header/update', [SettingController::class, 'storeOrUpdateHeader']);
            Route::post('footer/update', [SettingController::class, 'storeOrUpdateFooter']);
            Route::post('advertising/{slug}', [SettingController::class, 'storeOrUpdateAdvertising']);

            Route::post('/pages', [PageController::class, 'store']);
            Route::put('/pages/{id}', [PageController::class, 'update']);
            Route::delete('/pages/{id}', [PageController::class, 'destroy']);

            Route::put('/footer-sections/{id}', [FooterSectionController::class, 'update']);
            Route::post('/footer-sections', [FooterSectionController::class, 'store']);

            Route::apiResource('newsletters', NewsletterApiController::class);

            // Route::post('advertising/vertical'  , [SettingController::class,'storeOrUpdateVerticalAdvertising']);
            // Route::post('advertising/{s'  , [SettingController::class,'storeOrUpdate']);
        });
    });

    // Author-only routes
    Route::middleware('role:author,admin,editor')->group(function () {
        // Authors can create content but only update/delete their own content (check in controller)
        Route::prefix('contents')->group(function () {
            Route::post('/', [ContentController::class, 'store']);
            Route::put('/{id}', [ContentController::class, 'update']);
            Route::delete('/{id}', [ContentController::class, 'destroy']);
            Route::get('/dashboard-content/{id}', [ContentController::class, 'show']);
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

    Route::middleware('role:admin,editor,author')->group(function () {
        Route::get('all-content', [ContentController::class, 'allContents']);
        Route::get('dashboard-overview', [ContentController::class, 'dashboard']);
    });

    Route::post('upvote-downvote/{commentId}/vote', [ContentController::class, 'vote']);

    Route::get('content-dashbaord/{cat_name}/{sub_name}', [ContentController::class, 'indexForSubCategoryForDashboard']);

    Route::post('status/{id}', [ContentController::class, 'storeOrUpdateStatus']);
});

// Public GET routes
Route::get('/shows', [ContentController::class, 'showContents']);  // List all content

// Route::get('contents/', [ContentController::class, 'index']);
Route::get('contents/{cat_name}/{sub_name}/{id}', [ContentController::class, 'index']);  // single content for edit
Route::get('contents/{slug1}/{slug2}', [ContentController::class, 'indexForSubCategory']);
Route::get('contents/details/{cat_name}/{sub_name}/{contentId}', [ContentController::class, 'relatedContents']);
Route::get('contents/{cat_id}', [ContentController::class, 'indexFrontend']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('subcategories', [SubCategoryController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('comment', CommentController::class)->only([
        'store', 'update', 'destroy'
    ]);
});

Route::post('/subscribe', [SubscriberController::class, 'store']);
Route::get('change-color', [SettingController::class, 'showColor']);
// Route::post('upvote-downvote/{commentId}/vote', [ContentController::class, 'vote']);

Route::get('upvote-downvote/{comment_id}', [ContentController::class, 'getVotes']);

// Route::get('comment/{content_id}', [CommentController::class, 'index']);
Route::get('comment/content/{content_id}', [CommentController::class, 'index']);
Route::get('footer', [SettingController::class, 'footer']);

// ----------------------content and content details------------------------//

Route::get('show-tags/{slug}', [ContentController::class, 'showAllTags']);
Route::get('content/{cat_id}', [ContentController::class, 'showCategoryLatestContent']);  // 1st page of content for a category
Route::get('content-2nd-page-left-side/{cat_id}', [ContentController::class, 'showCategoryExceptLatestContent']);  // 2nd page of content for a category
Route::get('content-2nd-page-right-side/{cat_id}', [ContentController::class, 'showCategoryExcept3LatestContent']);  // 2nd page of content for a category
Route::get('content-3rd-page-top-portion/{cat_id}', [ContentController::class, 'showCategoryExcept5LatestContent']);  // 2nd page of content for a category
Route::get('content-3nd-page-bottom-portion/{cat_id}', [ContentController::class, 'showCategoryExcept8LatestContent']);  // 2nd page of content for a category



// Landing page content by category
Route::get('home', [ContentController::class, 'HomeContent']);
Route::get('details/{slug}', [ContentController::class, 'HomeContentById']);

Route::get('home/{slug}', [ContentController::class, 'HomeCategoryContent']);
Route::get('/subscribe', [SubscriberController::class, 'showSubscribers']);
Route::get('header', [SettingController::class, 'getHeader']);
Route::get('footer', [SettingController::class, 'getFooter']);
Route::get('advertising/{slug}', [SettingController::class, 'getAdvertising']);
Route::get('view-posts/{user_id}', [ContentController::class, 'viewPosts']);

// Route::get('privacy-policy', [PolicyController::class, 'getPrivacyPolicy']);
// Route::get('terms-conditions', [PolicyController::class, 'getTermsConditions']);
// Route::get('cookies-policy', [PolicyController::class, 'getCookiesPolicy']);
// Route::get('investment-disclaimer', [PolicyController::class, 'getInvestmentDisclaimer']);

Route::get('search', [ContentController::class, 'search']);

Route::get('/pages', [PageController::class, 'index']);
Route::get('/pages/{id}', [PageController::class, 'show']);
Route::get('/pages/slug/{name}', [PageController::class, 'showByName']);

Route::get('/footer-sections', [FooterSectionController::class, 'index']);

// Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect']);
// Route::get('auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::post('google/auth/jwt-process', [GoogleController::class, 'process']);

// Route::get('/contents/{content}/stats', [ContentController::class, 'stats']);

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
Route::get('contents/details/{cat_id}/{sub_id}/{contentId}', [ContentController::class, 'relatedContents']);
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

//----------------------content and content details------------------------//

Route::get('show-tags/{slug}', [ContentController::class,'showAllTags']);
Route::get('content/{cat_id}', [ContentController::class,'showCategoryLatestContent']); //1st page of content for a category
Route::get('content-2nd-page-left-side/{cat_id}', [ContentController::class,'showCategoryExceptLatestContent']); //2nd page of content for a category
Route::get('content-2nd-page-right-side/{cat_id}', [ContentController::class,'showCategoryExcept3LatestContent']); //2nd page of content for a category
Route::get('content-3rd-page-top-portion/{cat_id}', [ContentController::class,'showCategoryExcept5LatestContent']); //2nd page of content for a category
Route::get('content-3nd-page-bottom-portion/{cat_id}', [ContentController::class,'showCategoryExcept8LatestContent']); //2nd page of content for a category

// ---------------------------latest ---------------------------//

Route::get('landing-page/top-portion', [ContentController::class, 'landingPageTopPortion']);
Route::get('landing-page/bottom-portion', [ContentController::class, 'landingPageBottomPortion']);

//art & culture
Route::get('landing-page/2nd-page-top-portion', [ContentController::class, 'landingPage2ndPageTopPortion']);
Route::get('landing-page/2nd-page-bottom-portion', [ContentController::class, 'landingPage2ndPageBottomPortion']);


//quiet calm
Route::get('landing-page/2nd-page-top-portion', [ContentController::class, 'landingPage2ndPageTopPortion']);
Route::get('landing-page/2nd-page-bottom-portion', [ContentController::class, 'landingPage2ndPageBottomPortion']);


//gear
Route::get('landing-page/3rd-page-top-portion', [ContentController::class, 'landingPage3rdPageTopPortion']);
Route::get('landing-page/3rd-page-bottom-portion', [ContentController::class, 'landingPage3rdPageBottomPortion']);

//ride
Route::get('landing-page/4th-page-top-portion', [ContentController::class, 'landingPage4thPageTopPortion']);
Route::get('landing-page/4th-page-bottom-portion', [ContentController::class, 'landingPage4thPageBottomPortion']);

//music
Route::get('landing-page/5th-page-top-portion', [ContentController::class, 'landingPage6thPageTopPortion']);
Route::get('landing-page/5th-page-bottom-portion', [ContentController::class, 'landingPage5thPageBottomPortion']);

//video 
Route::get('landing-page/6th-page-top-portion', [ContentController::class, 'landingPage6thPageTopPortion']);
Route::get('landing-page/6th-page-bottom-portion', [ContentController::class, 'landingPage6thPageBottomPortion']);




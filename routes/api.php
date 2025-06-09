<?php

// use App\Http\Controllers\AuthController;
// use App\Http\Controllers\BlogController;
// use App\Http\Controllers\CategoryController;
// use App\Http\Controllers\CommentController;
// use App\Http\Controllers\ContactMessageController;
// use App\Http\Controllers\ContentController;
// use App\Http\Controllers\GoogleReviewController;
// use App\Http\Controllers\PackageController;
// use App\Http\Controllers\PackageOrderController;
// use App\Http\Controllers\ProfileController;
// use App\Http\Controllers\ResetPasswordController;
// use App\Http\Controllers\RideController;
// use App\Http\Controllers\SeoController;
// use App\Http\Controllers\SettingController;
// use App\Http\Controllers\SubCategoryController;
// use App\Http\Controllers\VideoController;
// use App\Models\Content;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;

// /*
//  * |--------------------------------------------------------------------------
//  * | API Routes
//  * |--------------------------------------------------------------------------
//  * |
//  * | Here is where you can register API routes for your application. These
//  * | routes are loaded by the RouteServiceProvider and all of them will
//  * | be assigned to the "api" middleware group. Make something great!
//  * |
//  */

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// /* create by abu sayed (start) */

// // Auth routes
// Route::post('register', [AuthController::class, 'register']);
// Route::post('login', [AuthController::class, 'login']);
// Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
// Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
// // Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');

// Route::post('password/email', [AuthController::class, 'sendResetOTP']);
// Route::post('password/verify-otp', [AuthController::class, 'verifyResetOTP'])->name('password.verify-otp');
// Route::post('password/reset', [AuthController::class, 'passwordReset'])->name('password.reset');

// // contact message get from frontend
// // Route::post('/contactMessage', [ContactMessageController::class, 'store']);

// // //settings(backend) which is namely settings
// Route::middleware('auth:api')->group(function () {
//     Route::put('settings/password', [SettingController::class, 'storeOrUpdatePassword']);
//     Route::post('settings/info', [SettingController::class, 'storeOrUpdate']);
//     Route::get('settings/info', [SettingController::class, 'index']);
// });

// Route::middleware('auth:api')->group(function () {
//     // Videos API resource

//     /* shows all data as subcategory */

//     Route::post('contents/', [ContentController::class, 'store']);
//     Route::put('contents/{id}', [ContentController::class, 'update']);
//     Route::delete('contents/{id}', [ContentController::class, 'destroy']);

//     // Categories API resource
//     Route::apiResource('categories', CategoryController::class);

//     // Subcategories API resource
//     Route::apiResource('subcategories', SubCategoryController::class);
// });

// Route::get('contents/', [ContentController::class, 'index']);

// // when single content is given in dashboard(edit single content)
// Route::get('contents/{cat_id}/{sub_id}/{id}', [ContentController::class, 'index']);

// // when all content is shown in dashboard for every subcategory
// Route::get('contents/{cat_id}/{sub_id}', [ContentController::class, 'indexForSubCategory']);

// // get latest 4 content is shown in frontend
// Route::get('contents/{cat_id}', [ContentController::class, 'indexFrontend']);

// // Go to Frontend and Backend API routes
// Route::get('categories', [CategoryController::class, 'index']);
// Route::get('subcategories', [SubCategoryController::class, 'index']);

// //for comment
// Route::apiResource('comment', CommentController::class);

/* create by abu sayed (end) */

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
    Route::prefix('settings')->group(function () {
        Route::put('password', [SettingController::class, 'storeOrUpdatePassword']);
        Route::post('info', [SettingController::class, 'storeOrUpdate']);
        Route::get('info', [SettingController::class, 'index']);
    });

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        // Full management of categories & subcategories
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('subcategories', SubCategoryController::class);
        Route::post('change-color', [SettingController::class, 'storeOrUpdateColor']);

        Route::apiResource('comment', CommentController::class)->only(['index','store','update', 'destroy']);


        Route::prefix('contents')->group(function () {
            Route::post('/', [ContentController::class, 'store']);
            Route::put('/{id}', [ContentController::class, 'update']);
            Route::delete('/{id}', [ContentController::class, 'destroy']);

            // No delete route for editors
        });

        // Role management
        Route::apiResource('roles', RoleManagementController::class);
    });

    // -------------------
    // Editor-only routes
    // -------------------
    Route::middleware('role:editor')->group(function () {
        // Editors can create and update content, but maybe not delete or categories
        Route::prefix('contents')->group(function () {
            Route::post('/', [ContentController::class, 'store']);
            Route::put('/{id}', [ContentController::class, 'update']);
            // No delete route for editors
        });
        // Maybe editors can also manage comments?
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
        // Subscribers mostly read content, maybe comment, manage profile
        Route::apiResource('comment', CommentController::class)->only(['store', 'index']);
        // Additional user-specific routes can go here
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

Route::get('contents/', [ContentController::class, 'index']);
Route::get('contents/{cat_id}/{sub_id}/{id}', [ContentController::class, 'index']);  // single content for edit
Route::get('contents/{cat_id}/{sub_id}', [ContentController::class, 'indexForSubCategory']);
Route::get('contents/{cat_id}', [ContentController::class, 'indexFrontend']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('subcategories', [SubCategoryController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('comment', CommentController::class);
    Route::get('comment/{content_id}', [CommentController::class, 'index']);
});

Route::post('/subscribe', [SubscriberController::class, 'store']);
// Route::post('/comment', [CommentController::class, 'store']);
Route::get('change-color', [SettingController::class, 'showColor']);

Route::post('upvote/{content_id}', [ContentController::class, 'upvote']);
Route::post('downvote/{content_id}', [ContentController::class, 'downvote']);
Route::get('upvote/{content_id}', [ContentController::class, 'getUpvotes']);
Route::get('downvote/{content_id}', [ContentController::class, 'getDownvotes']);

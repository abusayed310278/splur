<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackageOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleReviewController;
use App\Http\Controllers\RideController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\VideoController;
use App\Models\Content;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/* create by abu sayed (start)*/


// Auth routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
// Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');



Route::post('password/email', [AuthController::class, 'sendResetOTP']);
Route::post('password/verify-otp', [AuthController::class, 'verifyResetOTP'])->name('password.verify-otp');
Route::post('password/reset', [AuthController::class, 'passwordReset'])->name('password.reset');




//contact message get from frontend
Route::post('/contactMessage', [ContactMessageController::class, 'store']);





// //settings(backend) which is namely settings
Route::middleware('auth:api')->group(function () {
    Route::put('settings/password', [SettingController::class, 'storeOrUpdatePassword']);
    Route::post('settings/info', [SettingController::class, 'storeOrUpdate']);
    Route::get('settings/info', [SettingController::class, 'index']);
});



Route::middleware('auth:api')->group(function () {

    // Videos API resource

    /*shows all data as subcategory */


    Route::get('contents/', [ContentController::class, 'index']);

    //when single content is given in dashboard(edit single content)
    Route::get('contents/{cat_id}/{sub_id}/{id}', [ContentController::class, 'index']);


    //when all content is shown in dashboard for every subcategory
    Route::get('contents/{cat_id}/{sub_id}', [ContentController::class, 'indexForSubCategory']);




    Route::post('contents/', [ContentController::class, 'store']);
    Route::put('contents/{id}', [ContentController::class, 'update']);
    Route::delete('contents/{id}', [ContentController::class, 'destroy']);


    // Categories API resource
    Route::apiResource('categories', CategoryController::class);

    // Subcategories API resource
    Route::apiResource('subcategories', SubCategoryController::class);
});



//get latest 4 content is shown in frontend
Route::get('contents/{cat_id}', [ContentController::class, 'indexFrontend']);


// Go to Frontend and Backend API routes
Route::get('categories', [CategoryController::class, 'index']);
Route::get('subcategories', [SubCategoryController::class, 'index']);




/* create by abu sayed (end)*/

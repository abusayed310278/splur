<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


// Auth routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
// Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');



Route::post('password/email', [AuthController::class, 'sendResetOTP']);
Route::post('password/verify-otp', [AuthController::class, 'verifyResetOTP'])->name('password.verify-otp');
Route::post('password/reset', [AuthController::class, 'passwordReset'])->name('password.reset');
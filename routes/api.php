<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('sms',[\App\Http\Controllers\Api\DoctorController::class,'sender']);
Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('search/{doctor_id}/{date}',[\App\Http\Controllers\Api\SearchController::class,'search']);
    Route::apiResource('doctors',\App\Http\Controllers\Api\DoctorController::class);
    Route::apiResource('customers',\App\Http\Controllers\Api\CustomerController::class);
    Route::get('get_customers',[\App\Http\Controllers\Api\CustomerController::class,'get_data']);
    //Route::get('get_customers_for_doctors',[\App\Http\Controllers\Api\CustomerController::class,'get_customers']);
    Route::get('get_customers_for_doctors',[\App\Http\Controllers\Api\CustomerController::class,'get_customers_v2']);
});


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

Route::post('login', [\App\Http\Controllers\Api\UserController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('/user', [\App\Http\Controllers\Api\UserController::class, 'user']);

    // Route::get('/quarters', [\App\Http\Controllers\Api\QuarterController::class, 'index']);
    // Route::post('/quarters', [\App\Http\Controllers\Api\QuarterController::class, 'store']);
    // Route::put('/quarters/{id}', [\App\Http\Controllers\Api\QuarterController::class, 'update']);
    // Route::delete('/quarters/{id}', [\App\Http\Controllers\Api\QuarterController::class, 'destroy']);

    Route::apiResource('quarters', \App\Http\Controllers\Api\QuarterController::class)->except('show');

    Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class);

    Route::apiResource('employee-status', \App\Http\Controllers\Api\EmployeeStatusController::class)->except(['index', 'destroy']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

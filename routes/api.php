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
    Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
    Route::get('/user-status/{id}', [\App\Http\Controllers\Api\UserController::class, 'changeStatus']);
    Route::post('/user', [\App\Http\Controllers\Api\UserController::class, 'store']);
    Route::post('/user/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);

    // Route::get('/quarters', [\App\Http\Controllers\Api\QuarterController::class, 'index']);
    // Route::post('/quarters', [\App\Http\Controllers\Api\QuarterController::class, 'store']);
    // Route::put('/quarters/{id}', [\App\Http\Controllers\Api\QuarterController::class, 'update']);
    // Route::delete('/quarters/{id}', [\App\Http\Controllers\Api\QuarterController::class, 'destroy']);

    Route::apiResource('quarters', \App\Http\Controllers\Api\QuarterController::class)->except('show');

    Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class);

    Route::apiResource('employee-status', \App\Http\Controllers\Api\EmployeeStatusController::class)->except(['index', 'destroy']);

    Route::apiResource('employee-bank', \App\Http\Controllers\Api\EmployeeBankController::class)->except('destroy');
    Route::get('/employee-bank-status/{id}', [\App\Http\Controllers\Api\EmployeeBankController::class, 'changeStatus']);

    Route::apiResource('employee-designation', \App\Http\Controllers\Api\EmployeeDesignationController::class)->except('destroy');

    Route::apiResource('employee-quarters', \App\Http\Controllers\Api\EmployeeQuarterController::class)->except('destroy');

    Route::apiResource('pay-matrix-levels', \App\Http\Controllers\Api\PayMatrixLevelController::class)->except(['destroy,show']);

    Route::apiResource('pay-matrix-cells', \App\Http\Controllers\Api\PayMatrixCellController::class)->except(['destroy,show']);

    Route::apiResource('employee-pay-structures', \App\Http\Controllers\Api\EmployeePayStructureController::class)->except(['destroy,show']);

    Route::apiResource('dearness-allowance-rate', \App\Http\Controllers\Api\DearnessAllowanceRateController::class)->except(['destroy,show']);

    Route::apiResource('house-rent-allowance-rate', \App\Http\Controllers\Api\HouseRentAllowanceRateController::class)->except(['destroy,show']);

    Route::apiResource('non-practicing-allowance-rate', \App\Http\Controllers\Api\NonPracticingAllowanceRateController::class)->except(['destroy,show']);

    Route::apiResource('transport-allowance-rate', \App\Http\Controllers\Api\TransportAllowanceRateController::class)->except(['destroy,show']);

    Route::apiResource('uniform-allowance-rate', \App\Http\Controllers\Api\UniformAllowanceRateController::class)->except(['destroy,show']);

    Route::apiResource('credit-society-member', \App\Http\Controllers\Api\CreditSocietyMembershipController::class)->except(['destroy,show']);

    Route::apiResource('employee-gis', \App\Http\Controllers\Api\EmployeeGISController::class)->except(['destroy,show']);

    Route::apiResource('employee-loan', \App\Http\Controllers\Api\LoanAdvanceController::class)->except(['destroy,show']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

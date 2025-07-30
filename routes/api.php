<?php

use App\Http\Controllers\Api\ExportController;
use App\Models\NetSalary;
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

// Forgot Password Routes (Public routes - no authentication required)
Route::post('forgot-password', [\App\Http\Controllers\Api\UserController::class, 'forgotPassword']);
Route::post('verify-otp', [\App\Http\Controllers\Api\UserController::class, 'verifyOtp']);
Route::post('reset-password', [\App\Http\Controllers\Api\UserController::class, 'resetPassword']);
Route::post('resend-otp', [\App\Http\Controllers\Api\UserController::class, 'resendOtp']);

// Test email route (for debugging)
Route::post('test-email', [\App\Http\Controllers\Api\UserController::class, 'testEmail']);

Route::get('export/multi-sheet', [ExportController::class, 'exportMultiSheet']);
Route::get('export/employee-sheet', [ExportController::class, 'exportEmployeeSheet']);
Route::get('export/pensioner-sheet', [ExportController::class, 'exportPensionerSheet']);
Route::group(['middleware' => 'auth:sanctum'], function () {


    Route::apiResource('/role', \App\Http\Controllers\Api\RoleController::class)->only('index', 'store', 'update');

    Route::get('/user', [\App\Http\Controllers\Api\UserController::class, 'user']);

    Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
    Route::get('/all-users', [\App\Http\Controllers\Api\UserController::class, 'allUsers']);

    Route::get('/user-status/{id}', [\App\Http\Controllers\Api\UserController::class, 'changeStatus']);

    Route::post('/user', [\App\Http\Controllers\Api\UserController::class, 'store']);

    Route::post('/user/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
    Route::post('/user/change-password/{id}', [\App\Http\Controllers\Api\UserController::class, 'changePassword']);
    Route::post('/user/assign-roles/{id}', [\App\Http\Controllers\Api\UserController::class, 'assignRoles']);
    Route::post('/user/remove-roles/{id}', [\App\Http\Controllers\Api\UserController::class, 'removeRoles']);

    // Route::get('/quarters', [\App\Http\Controllers\Api\QuarterController::class, 'index']);
    // Route::post('/quarters', [\App\Http\Controllers\Api\QuarterController::class, 'store']);
    // Route::put('/quarters/{id}', [\App\Http\Controllers\Api\QuarterController::class, 'update']);
    // Route::delete('/quarters/{id}', [\App\Http\Controllers\Api\QuarterController::class, 'destroy']);

    Route::apiResource('quarters', \App\Http\Controllers\Api\QuarterController::class);

    Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class)->except('destroy');

    Route::apiResource('employee-status', \App\Http\Controllers\Api\EmployeeStatusController::class)->except(['destroy']);

    Route::apiResource('employee-bank', \App\Http\Controllers\Api\EmployeeBankController::class)->except('destroy');

    Route::apiResource('designation', \App\Http\Controllers\Api\DesignationController::class)->only('index', 'store', 'update', 'show');

    Route::get('/employee-bank-status/{id}', [\App\Http\Controllers\Api\EmployeeBankController::class, 'changeStatus']);

    Route::apiResource('employee-designation', \App\Http\Controllers\Api\EmployeeDesignationController::class)->except('destroy');

    Route::apiResource('employee-quarters', \App\Http\Controllers\Api\EmployeeQuarterController::class)->except('destroy');
    Route::get('employee-quarter-status/{id}', [\App\Http\Controllers\Api\EmployeeQuarterController::class, 'changeStatus']);

    Route::apiResource('pensioner', \App\Http\Controllers\Api\PensionerController::class)->only('index', 'show', 'store', 'update');

    Route::post('pensioner-status/{id}', [\App\Http\Controllers\Api\PensionerController::class, 'changeStatus']);

    Route::apiResource('pay-matrix-levels', \App\Http\Controllers\Api\PayMatrixLevelController::class)->except(['destroy,show']);
    Route::get('level-by-commission/{id}', [\App\Http\Controllers\Api\PayMatrixLevelController::class, 'levelBycommission']);

    Route::apiResource('pay-matrix-cells', \App\Http\Controllers\Api\PayMatrixCellController::class)->except(['destroy,show']);

    Route::apiResource('employee-pay-structures', \App\Http\Controllers\Api\EmployeePayStructureController::class)->except(['destroy']);

    Route::apiResource('dearness-allowance-rate', \App\Http\Controllers\Api\DearnessAllowanceRateController::class)->except(['destroy']);

    Route::apiResource('house-rent-allowance-rate', \App\Http\Controllers\Api\HouseRentAllowanceRateController::class)->except(['destroy']);

    Route::apiResource('non-practicing-allowance-rate', \App\Http\Controllers\Api\NonPracticingAllowanceRateController::class)->except(['destroy']);

    Route::apiResource('transport-allowance-rate', \App\Http\Controllers\Api\TransportAllowanceRateController::class)->except(['destroy']);

    Route::apiResource('uniform-allowance-rate', \App\Http\Controllers\Api\UniformAllowanceRateController::class)->except(['destroy']);

    Route::apiResource('credit-society-member', \App\Http\Controllers\Api\CreditSocietyMembershipController::class)->except(['destroy,show']);

    Route::apiResource('employee-gis', \App\Http\Controllers\Api\EmployeeGISController::class)->except(['destroy']);

    Route::apiResource('employee-loan', \App\Http\Controllers\Api\LoanAdvanceController::class)->except(['destroy,show']);

    Route::apiResource('salary', \App\Http\Controllers\Api\NetSalaryController::class)->except(['destroy']);
    Route::get('view-salary', [\App\Http\Controllers\Api\NetSalaryController::class, 'viewOwnSalary']);
    Route::post('verify-salary', [\App\Http\Controllers\Api\NetSalaryController::class, 'verifySalary']);

    Route::apiResource('monthly-pension', \App\Http\Controllers\Api\MonthlyPensionController::class)->only('index', 'store', 'update', 'show');

    Route::apiResource('dearness-relief', \App\Http\Controllers\Api\DearnessReliefController::class)->only('index', 'show', 'store', 'update');

    Route::apiResource('bank-account', \App\Http\Controllers\Api\BankAccountController::class)->only('index', 'show', 'store', 'update');
    Route::get('bank-account-status/{id}', [\App\Http\Controllers\Api\BankAccountController::class, 'changeStatus']);

    Route::apiResource('pension-deduction', \App\Http\Controllers\Api\PensionDeductionController::class)->only('index', 'store', 'update', 'show');

    Route::apiResource('arrears', \App\Http\Controllers\Api\ArrearsController::class)->only('index', 'store', 'update', 'show');

    Route::apiResource('pension-documents', \App\Http\Controllers\Api\PensionDocumentController::class)->only('index', 'store', 'update', 'show');

    Route::apiResource('employee-pay-slip', \App\Http\Controllers\Api\PaySlipController::class)->only('index', 'store', 'update', 'show');
    Route::post('bulk-pay-slip', [\App\Http\Controllers\Api\PaySlipController::class, 'bulkStore']);

    Route::apiResource('employee-deduction', \App\Http\Controllers\Api\DeductionController::class)->only('index', 'store', 'update', 'show');

    Route::apiResource('pension-related-information', \App\Http\Controllers\Api\PensionRelatedInfoController::class)->only('index', 'store', 'update', 'show');

    Route::apiResource('net-pension', \App\Http\Controllers\Api\NetPensionController::class)->only('index', 'update', 'show');
    Route::post('bulk-pensions', [\App\Http\Controllers\Api\MonthlyPensionController::class, 'bulkPension']);
    Route::post('verify-pension', [\App\Http\Controllers\Api\NetPensionController::class, 'verifyPension']);

    Route::apiResource('pay-commission', \App\Http\Controllers\Api\PayCommissionController::class)->only('index', 'update', 'store', 'show');

    Route::apiResource('nps-govt-contribution', \App\Http\Controllers\Api\NPSGovtContributionController::class)->only(['index', 'store', 'update', 'show']);

    Route::apiResource('gpf-contribution', \App\Http\Controllers\Api\GPFContributionController::class)->only(['index', 'store', 'update', 'show']);

    Route::get('report-all', [\App\Http\Controllers\Api\ReportController::class, 'index']);
    Route::get('report-bank-statement', [\App\Http\Controllers\Api\ReportController::class, 'bankStatement']);
    Route::get('report-lic', [\App\Http\Controllers\Api\ReportController::class, 'lic']);
    Route::get('report-gis', [\App\Http\Controllers\Api\ReportController::class, 'gis']);
    Route::get('report-nps', [\App\Http\Controllers\Api\ReportController::class, 'newPensionScheme']);
    Route::get('all-data', [\App\Http\Controllers\Api\ReportController::class, 'allData']);

    Route::get('dashboard', [\App\Http\Controllers\Api\ReportController::class, 'dashBoardCount']);
    Route::get('dashboard-reports', [\App\Http\Controllers\Api\ReportController::class, 'dashBoardreports']);
    Route::get('test', function () {
        $data = NetSalary::with([
            'employee.employeePayStructure.PayMatrixCell.payMatrixLevel:id,name',
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,increment_month,pension_number',
            'employeeBank:id,employee_id,account_number',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip.salaryArrears',
            'deduction.deductionRecoveries',
        ])->orderBy('year', 'asc')->orderBy('month', 'asc')->get();
        // $salaryArrearTypes = $data->flatMap(fn($item) => optional($item->paySlip)->salaryArrears->pluck('type'))->unique()->values()->all();
        // $deductionRecoveryTypes = $data->flatMap(fn($item) => optional($item->deduction)->deductionRecoveries->pluck('type'))->unique()->values()->all();
        // return response()->json([$salaryArrearTypes, $deductionRecoveryTypes]);
        return response()->json([$data]);
    });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

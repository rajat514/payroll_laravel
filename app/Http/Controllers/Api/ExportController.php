<?php

namespace App\Http\Controllers\Api;

use App\Exports\MultiSheetExport;
use App\Exports\SelectedEmployeesSheet;
use App\Exports\SelectedPensionerSheet;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportMultiSheet(Request $request)
    {
        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth()->month;       // e.g., 6 for June
        $previousMonthYear = $now->copy()->subMonth()->year;
        $filters = $request->only(['start_month', 'start_year', 'end_month', 'end_year']);

        return Excel::download(new MultiSheetExport($filters), 'Data_of_' . $previousMonth . '_' . $previousMonthYear . '.xlsx');
    }

    public function exportEmployeeSheet(Request $request)
    {
        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth()->month;       // e.g., 6 for June
        $previousMonthYear = $now->copy()->subMonth()->year;
        $filters = $request->only(['start_month', 'start_year', 'end_month', 'end_year', 'employee_id']);

        return Excel::download(new SelectedEmployeesSheet($filters), 'Data_of_' . $previousMonth . '_' . $previousMonthYear . '.xlsx');
    }

    public function exportPensionerSheet(Request $request)
    {
        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth()->month;       // e.g., 6 for June
        $previousMonthYear = $now->copy()->subMonth()->year;
        $filters = $request->only(['start_month', 'start_year', 'end_month', 'end_year', 'pensioner_id']);

        return Excel::download(new SelectedPensionerSheet($filters), 'Data_of_' . $previousMonth . '_' . $previousMonthYear . '.xlsx');
    }
}

<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\LoanAdvance;
use App\Models\NetSalary;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ComputerAdvanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    protected $filters;
    private int $serial = 1;
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }


    public function title(): string
    {
        return 'Computer Advance';
    }

    //using net salary
    public function collection()
    {
        $user = auth()->user();
        $now = Carbon::now();

        $currentMonth = $now->month;
        $currentYear = $now->year;

        $previousMonth = $now->copy()->subMonth()->month;
        $previousMonthYear = $now->copy()->subMonth()->year;

        $query = NetSalary::query();

        if (!empty($this->filters['start_month']) && !empty($this->filters['start_year'])) {
            $query->where(function ($q) {
                $q->where('year', '>', $this->filters['start_year'])
                    ->orWhere(function ($q2) {
                        $q2->where('year', $this->filters['start_year'])
                            ->where('month', '>=', $this->filters['start_month']);
                    });
            });
        }

        if (!empty($this->filters['end_month']) && !empty($this->filters['end_year'])) {
            $query->where(function ($q) {
                $q->where('year', '<', $this->filters['end_year'])
                    ->orWhere(function ($q2) {
                        $q2->where('year', $this->filters['end_year'])
                            ->where('month', '<=', $this->filters['end_month']);
                    });
            });
        }

        // If no filters are passed, fallback to previous month
        if (
            empty($this->filters['start_month']) && empty($this->filters['start_year']) &&
            empty($this->filters['end_month']) && empty($this->filters['end_year'])
        ) {
            $query->where('month', $currentMonth)->where('year', $currentYear);
        }

        return $query->with([
            // 'employee.employeePayStructure.PayMatrixCell.payMatrixLevel:id,name',
            // 'employee:id,employee_code,prefix,first_name,middle_name,last_name,increment_month,pension_number',
            // 'employeeBank:id,employee_id,account_number',
            // 'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip',
            'deduction',
        ])->when(
            $user->institute !== 'BOTH',
            fn($q) => $q->where('employee->institute', $user->institute)
        )
            ->whereHas('deduction', function ($q) {
                $q->where('computer_advance_balance', '>', 0);
            })
            ->orderBy('year', 'asc')->orderBy('month', 'asc')->get();
    }

    //Merge Table
    // public function collection()
    // {
    //     $user = auth()->user();
    //     $now = Carbon::now();

    //     $currentMonth = $now->month;
    //     $currentYear = $now->year;

    //     $previousMonth = $now->copy()->subMonth()->month;
    //     $previousMonthYear = $now->copy()->subMonth()->year;

    //     /**
    //      * -------------------------
    //      * NetSalary (Computer Advance)
    //      * -------------------------
    //      */
    //     $query = NetSalary::query();

    //     if (!empty($this->filters['start_month']) && !empty($this->filters['start_year'])) {
    //         $query->where(function ($q) {
    //             $q->where('year', '>', $this->filters['start_year'])
    //                 ->orWhere(function ($q2) {
    //                     $q2->where('year', $this->filters['start_year'])
    //                         ->where('month', '>=', $this->filters['start_month']);
    //                 });
    //         });
    //     }

    //     if (!empty($this->filters['end_month']) && !empty($this->filters['end_year'])) {
    //         $query->where(function ($q) {
    //             $q->where('year', '<', $this->filters['end_year'])
    //                 ->orWhere(function ($q2) {
    //                     $q2->where('year', $this->filters['end_year'])
    //                         ->where('month', '<=', $this->filters['end_month']);
    //                 });
    //         });
    //     }

    //     // If no filters passed → fallback to current month
    //     if (
    //         empty($this->filters['start_month']) && empty($this->filters['start_year']) &&
    //         empty($this->filters['end_month']) && empty($this->filters['end_year'])
    //     ) {
    //         $query->where('month', $currentMonth)->where('year', $currentYear);
    //     }

    //     $netSalaries = $query->with(['paySlip', 'deduction'])
    //         ->when(
    //             $user->institute !== 'BOTH',
    //             fn($q) => $q->where('employee->institute', $user->institute)
    //         )
    //         ->orderBy('year', 'asc')->orderBy('month', 'asc')
    //         ->get()
    //         ->map(function ($ns) {
    //             return [
    //                 'type' => 'Computer Advance',
    //                 'month' => $ns->month,
    //                 'year' => $ns->year,
    //                 'employee_code' => $ns->employee->employee_code ?? '',
    //                 'name' => $ns->employee->name ?? '',
    //                 'designation' => $ns->employee->latest_employee_designation->designation ?? '',
    //                 'computer_advance_installment' => $ns->deduction->computer_advance_installment ?? 0,
    //                 // 'balance' => $ns->deduction->computer_advance_balance ?? 0,
    //             ];
    //         });

    //     /**
    //      * -------------------------
    //      * LoanAdvance
    //      * -------------------------
    //      */
    //     $loans = LoanAdvance::with('employee')
    //         ->where('is_active', 1)
    //         ->when(
    //             $user->institute !== 'BOTH',
    //             fn($q) => $q->whereHas('employee', fn($q2) => $q2->where('institute', $user->institute))
    //         )
    //         ->where('loan_type', 'Computer')
    //         ->get()
    //         ->map(function ($loan) {
    //             return [
    //                 // 'type' => 'Loan Advance',
    //                 // 'month' => null,
    //                 // 'year' => null,
    //                 // 'employee_code' => $loan->employee->employee_code ?? '',
    //                 // 'name' => trim($loan->employee->prefix . ' ' . $loan->employee->first_name . ' ' . $loan->employee->last_name),
    //                 // 'designation' => '',
    //                 'loan_amount' => $loan->loan_amount ?? 0,
    //                 'current_installment' => $loan->current_installment ?? 0,
    //                 'remaining_balance' => $loan->remaining_balance ?? 0,
    //             ];
    //         });

    //     /**
    //      * -------------------------
    //      * Merge Both Collections
    //      * -------------------------
    //      */
    //     return (new \Illuminate\Support\Collection())
    //         ->merge($netSalaries)
    //         ->merge($loans);
    // }

    //using Loan lable
    // public function collection()
    // {
    //     $user = auth()->user();

    //     return LoanAdvance::with('employee:id,employee_code,prefix,first_name,middle_name,last_name,pension_number', 'employee.latestEmployeeDesignation')
    //         ->where('is_active', 1)
    //         ->where('loan_type', 'Computer')
    //         ->when($user->institute !== 'BOTH', function ($q) use ($user) {
    //             $q->whereHas('employee', fn($q2) => $q2->where('institute', $user->institute));
    //         })
    //         ->get();
    // }


    private function safeValue($value)
    {
        return !is_null($value) && $value !== '' ? $value : '0';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $row = $sheet->getHighestRow() + 1;

                $totalPayCol = array_search('Gross Pay', $this->headings()) + 1;
                $totalDeductionCol = array_search('Total Deductions', $this->headings()) + 1;
                $netAmountCol = array_search('Net Amount', $this->headings()) + 1;

                $netSalaries = $this->collection();

                $totalPay = $netSalaries->sum(fn($item) => $item->paySlip->total_pay ?? 0);
                $total = $netSalaries->sum(fn($item) => $item->deduction->computer_advance_interest ?? 0);
                $totalRemaining = $netSalaries->sum(fn($item) => $item->deduction->computer_advance_balance ?? 0);
                $totalNetAmount = $netSalaries->sum('net_amount');

                // Label
                $sheet->setCellValue("A{$row}", 'TOTAL');
                $sheet->setCellValue("F{$row}", $total);
                $sheet->setCellValue("I{$row}", $totalRemaining);


                // Style Total Row
                $lastCol = $sheet->getHighestColumn();
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }

    public function map($netSalary): array
    {
        $employee = $netSalary->employee;
        // $bank = $netSalary->employeeBank ?? (object) [];
        $paySlip = $netSalary->paySlip ?? (object) [];
        $deduction = $netSalary->deduction ?? (object) [];
        // $pay_level = optional(optional(optional($employee->employeePayStructure)->payMatrixCell)->payMatrixLevel);

        $monthName = Carbon::create()->month($netSalary->month)->format('F');
        $monthYear = $monthName . ' ' . $netSalary->year;
        $payPlusNpa = ($paySlip->basic_pay ?? 0) + ($paySlip->npa_amount ?? 0);

        return [
            $this->serial++,
            $employee->latest_employee_designation->designation ?? '',
            $monthYear,
            $employee->employee_code,
            $employee->name,
            $this->safeValue($netSalary->deduction->computer_advance_interest),
            $this->safeValue($netSalary->deduction->computer_advance_installment),
            $this->safeValue($netSalary->deduction->computer_advance_inst_no),
            $this->safeValue($deduction->deduction->computer_advance_balance ?? null),
        ];
    }

    public function headings(): array
    {
        return [
            'अनु क्रमांक/S.NO.',
            'Designation',
            'माह/Month',
            'कर्मचारी कोड/Employee Code',
            'नाम/Name',
            'Computer Advacnce Total',
            'Computer Advance Inst. Amount',
            'Computer Advance Inst No.',
            'Computer Advance Remaining balance',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'indent' => 2,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(35);

        foreach (range(2, $sheet->getHighestRow()) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(25);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'indent' => 1,
                ],
            ]);
        }

        return [];
    }
}

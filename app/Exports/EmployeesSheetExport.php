<?php

namespace App\Exports;

use App\Models\NetSalary;
use Carbon\Carbon;
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

class EmployeesSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    protected $filters;
    protected static $netSalaries;
    protected static array $salaryArrearTypes = [];
    protected static array $deductionRecoveryTypes = [];
    private int $serial = 1;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        if (!self::$netSalaries) {
            $this->prepareDynamicColumns();
        }
    }

    public function title(): string
    {
        return 'Net Salary';
    }

    private function prepareDynamicColumns(): void
    {
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

        if (empty($this->filters['start_month']) && empty($this->filters['start_year']) && empty($this->filters['end_month']) && empty($this->filters['end_year'])) {
            $query->where('month', $currentMonth)->where('year', $currentYear);
        }

        self::$netSalaries = $query->with([
            'employee.employeePayStructure.PayMatrixCell.payMatrixLevel:id,name',
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,increment_month,pension_number',
            'employeeBank:id,employee_id,account_number',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip.salaryArrears',
            'deduction.deductionRecoveries',
        ])->orderBy('year', 'asc')->orderBy('month', 'asc')->get();

        self::$salaryArrearTypes = self::$netSalaries->flatMap(function ($item) {
            return collect(optional($item->paySlip)->salaryArrears)->pluck('type');
        })->unique()->values()->all();


        self::$deductionRecoveryTypes = self::$netSalaries->flatMap(function ($item) {
            return collect(optional($item->deduction)->deductionRecoveries)->pluck('type');
        })->unique()->values()->all();
    }

    public function collection()
    {
        return self::$netSalaries;
    }

    private function safeValue($value)
    {
        return !is_null($value) && $value !== '' ? $value : '0';
    }

    public function headings(): array
    {
        $fixedHeadings = [
            'S.No.',
            'Pension No.',
            'Month',
            'Emp Code',
            'Name',
            'Increment Month',
            'Matrix Level',
            'Designation',
            'Bank A/C',
            'Basic Pay',
            'NPA',
            'Pay + NPA',
            'DA',
            'HRA',
            'Transport',
            'Uniform',
            'DA on TA',
            'Leave Salary',
            'Arrears',
            'Gross Pay',
            'Income Tax',
            'Prof. Tax',
            'License Fee',
            'NFCH Donation',
            'GPF',
            'Transport Rec.',
            'HRA Rec.',
            'Computer Adv Inst.',
            'Computer Adv Bal.',
            'Emp 10%',
            'Govt 14%',
            'Dies Non Rec.',
            'GIS',
            'Pay Recovery',
            'LIC',
            'Credit Society'
        ];

        $arrearHeadings = array_map(fn($type) => "{$type}", self::$salaryArrearTypes);
        $recoveryHeadings = array_map(fn($type) => "{$type}", self::$deductionRecoveryTypes);

        return array_merge($fixedHeadings, $arrearHeadings, $recoveryHeadings, ['Total Deductions', 'Net Amount']);
    }

    public function map($netSalary): array
    {
        $employee = $netSalary->employee;
        $bank = $netSalary->employeeBank ?? (object) [];
        $paySlip = optional($netSalary->paySlip);
        $deduction = optional($netSalary->deduction);

        $pay_level = optional(optional(optional($employee->employeePayStructure)->payMatrixCell)->payMatrixLevel);

        $monthName = Carbon::create()->month($netSalary->month)->format('F');
        $monthYear = $monthName . ' ' . $netSalary->year;
        $payPlusNpa = ($paySlip->basic_pay ?? 0) + ($paySlip->npa_amount ?? 0);

        $arrears = collect(optional($paySlip)->salaryArrears ?? []);
        $recoveries = collect(optional($deduction)->deductionRecoveries ?? []);

        $arrearMap = collect(self::$salaryArrearTypes)->map(fn($type) => $this->safeValue($arrears->firstWhere('type', $type)?->amount));
        $recoveryMap = collect(self::$deductionRecoveryTypes)->map(fn($type) => $this->safeValue($recoveries->firstWhere('type', $type)?->amount));

        return array_merge([
            $this->serial++,
            $employee->pension_number,
            $monthYear,
            $employee->employee_code,
            $employee->name,
            $employee->increment_month,
            $pay_level->name ?? '',
            optional($employee->latestEmployeeDesignation)->designation,
            $bank->account_number,
            $this->safeValue($paySlip->basic_pay),
            $this->safeValue($paySlip->npa_amount),
            $this->safeValue($payPlusNpa),
            $this->safeValue($paySlip->da_amount),
            $this->safeValue($paySlip->hra_amount),
            $this->safeValue($paySlip->transport_amount),
            $this->safeValue($paySlip->uniform_rate_amount),
            $this->safeValue($paySlip->da_on_ta),
            $this->safeValue($paySlip->itc_leave_salary),
            $this->safeValue($paySlip->arrears),
            $this->safeValue($paySlip->total_pay),
            $this->safeValue($deduction->income_tax),
            $this->safeValue($deduction->professional_tax),
            $this->safeValue($deduction->license_fee),
            $this->safeValue($deduction->nfch_donation),
            $this->safeValue($deduction->gpf),
            $this->safeValue($deduction->transport_allowance_recovery),
            $this->safeValue($deduction->hra_recovery),
            $this->safeValue($deduction->computer_advance_installment),
            $this->safeValue($deduction->computer_advance_balance),
            $this->safeValue($deduction->employee_contribution_10),
            $this->safeValue($deduction->govt_contribution_14_recovery),
            $this->safeValue($deduction->dies_non_recovery),
            $this->safeValue($deduction->gis),
            $this->safeValue($deduction->pay_recovery),
            $this->safeValue($deduction->lic),
            $this->safeValue($deduction->credit_society),
        ], $arrearMap->toArray(), $recoveryMap->toArray(), [
            $this->safeValue($deduction->total_deductions),
            $this->safeValue($netSalary->net_amount),
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
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
                $totalDeduction = $netSalaries->sum(fn($item) => $item->deduction->total_deductions ?? 0);
                $totalNetAmount = $netSalaries->sum('net_amount');

                // Label
                $sheet->setCellValue("A{$row}", 'TOTAL');

                // Insert Totals
                $sheet->setCellValueByColumnAndRow($totalPayCol, $row, $totalPay);
                $sheet->setCellValueByColumnAndRow($totalDeductionCol, $row, $totalDeduction);
                $sheet->setCellValueByColumnAndRow($netAmountCol, $row, $totalNetAmount);

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
}

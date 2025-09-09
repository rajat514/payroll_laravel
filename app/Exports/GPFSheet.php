<?php

namespace App\Exports;

use App\Models\Employee;
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

class GPFSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{

    protected $filters;

    private int $serial = 1;
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }
    public function title(): string
    {
        return 'GPF';
    }
    public function collection()
    {
        $user = auth()->user();
        $now = Carbon::now();

        $currentMonth = $now->month;              // e.g., 7 for July
        $currentYear = $now->year;                // e.g., 2025

        $previousMonth = $now->copy()->subMonth()->month;       // e.g., 6 for June
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


        return $query->whereHas(
            'employeeRelation',
            fn($qn) => $qn->where('pension_scheme', 'GPF')
        )->with([
            // 'employee.employeePayStructure.PayMatrixCell.payMatrixLevel:id,name',
            // 'employee:id,employee_code,prefix,first_name,middle_name,last_name,increment_month,pension_number',
            // 'employeeBank:id,employee_id,account_number',
            // 'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip',
            'deduction',
        ])->when(
            $user->institute !== 'BOTH',
            fn($q) => $q->where('employee->institute', $user->institute)
        )->orderBy('year', 'asc')->orderBy('month', 'asc')->get();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $row = $sheet->getHighestRow() + 1;

                $netSalaries = $this->collection();

                $totalGPF = $netSalaries->sum(fn($item) => $item->deduction->gpf ?? 0);
                // $totalBasicPay = $netSalaries->sum(fn($item) => $item->paySlip->basic_pay ?? 0);
                // $totalDeductions = $netSalaries->sum(fn($item) => $item->deduction->total_deductions ?? 0);

                // $totalNetAmount = $netSalaries->sum('net_amount');

                // Add "TOTAL" label in first column
                $sheet->setCellValue("A{$row}", 'TOTAL');

                // Insert totals into correct columns
                $sheet->setCellValue("H{$row}", $totalGPF);

                // Style total row
                $sheet->getStyle("A{$row}:AL{$row}")->applyFromArray([
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

                // Optionally increase row height
                $sheet->getRowDimension($row)->setRowHeight(30);
            },
        ];
    }

    private function safeValue($value)
    {
        return !is_null($value) && $value !== '' ? $value : '0';
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
            $employee->pension_number,
            $monthYear,
            $employee->employee_code,
            $employee->name,
            $employee->latest_employee_designation->designation ?? '',
            $payPlusNpa,
            $this->safeValue(($deduction->gpf ?? 0))
        ];
    }

    public function headings(): array
    {
        return [
            'अनु क्रमांक/S.NO.',
            'GPF No.',
            'माह/Month',
            'कर्मचारी कोड/Employee Code',
            'नाम/Name',
            'पद/Designation',
            'पे + एन पी ए/Pay + NPA',
            'GPF',
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

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


    public function collection()
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

        // If no filters are passed, fallback to previous month
        if (
            empty($this->filters['start_month']) && empty($this->filters['start_year']) &&
            empty($this->filters['end_month']) && empty($this->filters['end_year'])
        ) {
            $query->where('month', $currentMonth)->where('year', $currentYear);
        }

        return $query->with([
            'employee.employeePayStructure.PayMatrixCell.payMatrixLevel:id,name',
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,increment_month,pension_number',
            'employeeBank:id,employee_id,account_number',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip',
            'deduction',
        ])->orderBy('year', 'asc')->orderBy('month', 'asc')->get();
    }

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

                $netSalaries = $this->collection();

                $computer_advance_balance = $netSalaries->sum(fn($item) => $item->deduction->computer_advance_balance ?? 0);
                $computer_advance_installment = $netSalaries->sum(fn($item) => $item->deduction->computer_advance_installment ?? 0);

                $totalNetAmount = $netSalaries->sum('net_amount');

                // Add "TOTAL" label in first column
                $sheet->setCellValue("A{$row}", 'TOTAL');

                // Insert totals into correct columns
                $sheet->setCellValue("G{$row}", $computer_advance_balance);  // Total Deductions
                $sheet->setCellValue("F{$row}", $computer_advance_installment);   // Net Amount

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

    public function map($netSalary): array
    {
        $employee = $netSalary->employee;
        $bank = $netSalary->employeeBank ?? (object) [];
        $paySlip = $netSalary->paySlip ?? (object) [];
        $deduction = $netSalary->deduction ?? (object) [];
        $pay_level = optional(optional(optional($employee->employeePayStructure)->payMatrixCell)->payMatrixLevel);

        $monthName = Carbon::create()->month($netSalary->month)->format('F');
        $monthYear = $monthName . ' ' . $netSalary->year;
        $payPlusNpa = ($paySlip->basic_pay ?? 0) + ($paySlip->npa_amount ?? 0);

        return [
            $this->serial++,
            $monthYear,
            $employee->employee_code,
            $employee->name,
            optional($employee->latestEmployeeDesignation)->designation,
            $this->safeValue($deduction->computer_advance_installment ?? null),
            $this->safeValue($deduction->computer_advance_balance ?? null),
        ];
    }

    public function headings(): array
    {
        return [
            'अनु क्रमांक/Sr.NO.',
            'माह/Month',
            'कर्मचारी कोड/Employee Code',
            'नाम/Name',
            'पद/Designation',
            'कंप्यूटर अग्रिम किस्त/Computer Advance Installment',
            'कंप्यूटर अग्रिम शेष/Computer Advance Balance',
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

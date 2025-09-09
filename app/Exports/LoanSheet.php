<?php

namespace App\Exports;

use App\Models\LoanAdvance;
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

class LoanSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    private int $serial = 1;

    public function title(): string
    {
        return 'Loan Advance';
    }

    public function collection()
    {
        $user = auth()->user();

        return LoanAdvance::with('employee:id,employee_code,prefix,first_name,middle_name,last_name,pension_number')
            ->where('is_active', 1)
            ->when($user->institute !== 'BOTH', function ($q) use ($user) {
                $q->whereHas('employee', fn($q2) => $q2->where('institute', $user->institute));
            })
            ->orderBy('sanctioned_date', 'asc')
            ->get();
    }

    public function map($loanAdvance): array
    {
        $employee = $loanAdvance->employee;

        return [
            $this->serial++,
            $employee->employee_code,
            $employee->pension_number,
            trim($employee->prefix . ' ' . $employee->first_name . ' ' . $employee->middle_name . ' ' . $employee->last_name),
            $loanAdvance->loan_type,
            $loanAdvance->loan_amount,
            $loanAdvance->interest_rate . '%',
            Carbon::parse($loanAdvance->sanctioned_date)->format('d-m-Y'),
            $loanAdvance->total_installments,
            $loanAdvance->current_installment,
            $loanAdvance->remaining_balance,
        ];
    }

    public function headings(): array
    {
        return [
            'S.No.',
            'Employee Code',
            'Pension/NPS No.',
            'Employee Name',
            'Loan Type',
            'Loan Amount',
            'Interest Rate',
            'Sanctioned Date',
            'Total Installments',
            'Current Installment',
            'Remaining Balance',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();

        // Style header
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        // Style all data rows
        foreach (range(2, $sheet->getHighestRow()) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(22);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
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

                $loanAdvances = $this->collection();
                $totalLoanAmount = $loanAdvances->sum('loan_amount');
                $totalRemaining = $loanAdvances->sum('remaining_balance');

                $sheet->setCellValue("A{$row}", 'TOTAL');
                $sheet->setCellValue("F{$row}", $totalLoanAmount);
                $sheet->setCellValue("K{$row}", $totalRemaining);

                $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension($row)->setRowHeight(28);
            },
        ];
    }
}

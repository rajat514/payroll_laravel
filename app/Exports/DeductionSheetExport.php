<?php

namespace App\Exports;

use App\Models\Deduction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DeductionSheetExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{


    public function title(): string
    {
        return "Deduction";
    }
    public function collection()
    {
        $now = Carbon::now();

        $currentMonth = $now->month;              // e.g., 7 for July
        $currentYear = $now->year;                // e.g., 2025

        $previousMonth = $now->copy()->subMonth()->month;       // e.g., 6 for June
        $previousMonthYear = $now->copy()->subMonth()->year;
        return Deduction::with('netSalary.employee')->get();
    }

    private function safeValue($value)
    {
        return !is_null($value) && $value !== '' ? $value : '0';
    }

    public function map($deduction): array
    {
        $employee = $deduction->netSalary->employee->name;
        return [
            $employee,
            $this->safeValue($deduction->income_tax),
            $this->safeValue($deduction->professional_tax),
            $this->safeValue($deduction->license_fee),
            $this->safeValue($deduction->nfch_donation),
            $this->safeValue(($deduction->gpf ?? 0) + ($deduction->nps_recovery ?? 0)),
            $this->safeValue($deduction->transport_allowance_recovery),
            $this->safeValue($deduction->hra_recovery),
            $this->safeValue($deduction->computer_advance),
            $this->safeValue($deduction->computer_advance_installment),
            $this->safeValue($deduction->computer_advance_balance),
            $this->safeValue($deduction->employee_contribution_10),
            $this->safeValue($deduction->govt_contribution_14_recovery),
            $this->safeValue($deduction->dies_non_recovery),
            $this->safeValue($deduction->gis),
            $this->safeValue($deduction->pay_recovery),
            $this->safeValue($deduction->lic),
            $this->safeValue($deduction->credit_society),
            $this->safeValue($deduction->total_deductions),
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Income Tax',
            'Professional Tax',
            'License Fee',
            'NFCH Donation',
            'GPF / NPS',
            'Transport Recovery',
            'HRA Recovery',
            'Computer Advance',
            'Computer Advance Installment',
            'Computer Advance Balance',
            'Employee Contribution 10%',
            'Govt Contribution 14% Recovery',
            'Dies Non-Recovery',
            'GIS',
            'Pay Recovery',
            'LIC',
            'Credit Society',
            'Total Deductions',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
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
                'indent' => 1,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach (range(2, $sheet->getHighestRow()) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(22);
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

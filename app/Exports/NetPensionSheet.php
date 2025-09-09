<?php

namespace App\Exports;

use App\Models\NetPension;
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

class NetPensionSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    private int $serial = 1;
    protected array $filters;
    protected static array $arrearTypes = [];
    protected static $netPensions;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        $query = NetPension::with([
            'pensioner.employee',
            'pensionerBank',
            'monthlyPension.arrears',
            'pensionerDeduction',
        ]);

        // Apply filters
        if (!empty($filters['start_month']) && !empty($filters['start_year'])) {
            $query->where(function ($q) {
                $q->where('year', '>', $this->filters['start_year'])
                    ->orWhere(function ($q2) {
                        $q2->where('year', $this->filters['start_year'])
                            ->where('month', '>=', $this->filters['start_month']);
                    });
            });
        }

        if (!empty($filters['end_month']) && !empty($filters['end_year'])) {
            $query->where(function ($q) {
                $q->where('year', '<', $this->filters['end_year'])
                    ->orWhere(function ($q2) {
                        $q2->where('year', $this->filters['end_year'])
                            ->where('month', '<=', $this->filters['end_month']);
                    });
            });
        }

        if (!empty($filters['pension_type'])) {
            $query->whereHas('pensioner', function ($q) {
                $q->where('type_of_pension', $this->filters['pension_type']);
            });
        }

        if (empty($filters)) {
            $now = now();
            $query->where('month', $now->month)->where('year', $now->year);
        }

        self::$netPensions = $query->orderBy('year')->orderBy('month')->get();

        // Collect dynamic arrear types
        self::$arrearTypes = self::$netPensions->flatMap(function ($item) {
            return collect($item->monthlyPension->arrears ?? [])->pluck('type');
        })->unique()->values()->all();
    }

    public function title(): string
    {
        return 'Net Pension';
    }

    public function collection()
    {
        return self::$netPensions;
    }

    private function safeValue($value)
    {
        return !is_null($value) && $value !== '' ? $value : '0';
    }

    public function headings(): array
    {
        $fixed = [
            'S.No.',
            'PPO No.',
            'Month',
            'Pensioner Name',
            'Pension Type',
            'Account Number',
            'Basic Pension',
            'Dearness Relief',
            'Medical Allowance',
            'Gross Pension',
            'Income Tax',
            'Commutation Amount',
            'Recovery',
            'Other Deduction',
            'Total Deduction',
        ];

        $verificationArray = [
            'Pensioner Operator Status',
            'Pensioner Operator Date',
            'DDO Status',
            'DDO Date',
            'Section Officer Status',
            'Section Officer Date',
            'Account Officer Status',
            'Account Officer Date',
            'Is Finalized',
            'Finalized Date',
            'Is Released',
            'Released Date',
        ];

        $arrears = array_map(fn($type) => "Arrear: {$type}", self::$arrearTypes);

        return array_merge($fixed, $arrears, ['Net Pension'], $verificationArray);
    }

    public function map($row): array
    {
        $pensioner = $row->pensioner;
        $monthly = $row->monthlyPension ?? (object)[];
        $deduction = $row->pensionerDeduction ?? (object)[];
        $bank = $row->pensionerBank ?? (object)[];
        $arrears = collect($monthly->arrears ?? []);

        $arrearMap = collect(self::$arrearTypes)->map(function ($type) use ($arrears) {
            return $this->safeValue($arrears->firstWhere('type', $type)?->amount);
        });

        return array_merge([
            $this->serial++,
            $pensioner->ppo_no,
            Carbon::create()->month($row->month)->format('F') . ', ' . $row->year,
            $pensioner->name,
            $pensioner->type_of_pension,
            $bank->account_no ?? '',
            $this->safeValue($monthly->basic_pension),
            $this->safeValue($monthly->dr_amount),
            $this->safeValue($monthly->medical_allowance),
            $this->safeValue($monthly->total_pension),
            $this->safeValue($deduction->income_tax),
            $this->safeValue($deduction->commutation_amount),
            $this->safeValue($deduction->recovery),
            $this->safeValue($deduction->other),
            $this->safeValue($deduction->amount),
        ], $arrearMap->toArray(), [
            $this->safeValue($row->net_pension),

            $this->formatStatus($row->pensioner_operator_status),
            $this->safeValue($row->pensioner_operator_date),
            $this->formatStatus($row->ddo_status),
            $this->safeValue($row->ddo_date),
            $this->formatStatus($row->section_officer_status),
            $this->safeValue($row->section_officer_date),
            $this->formatStatus($row->account_officer_status),
            $this->safeValue($row->account_officer_date),
            $this->formatStatus($row->is_finalize),
            $this->safeValue($row->finalized_date),
            $this->formatStatus($row->is_verified),
            $this->safeValue($row->released_date),
        ]);
    }

    private function formatStatus($value)
    {
        if (is_null($value)) {
            return '';
        }
        return $value == 1 ? 'Yes' : 'No';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $row = $sheet->getHighestRow() + 1;
                $data = $this->collection();

                $totalPension = $data->sum(fn($d) => optional($d->monthlyPension)->total_pension ?? 0);
                $totalDeduction = $data->sum(fn($d) => optional($d->pensionerDeduction)->amount ?? 0);
                $totalIncomeTax = $data->sum(fn($d) => optional($d->pensionerDeduction)->income_tax ?? 0);
                $totalNet = $data->sum('net_pension');

                $sheet->setCellValue("A{$row}", 'TOTAL');
                $sheet->setCellValue("J{$row}", $totalPension);
                $sheet->setCellValue("K{$row}", $totalIncomeTax);
                $sheet->setCellValue("O{$row}", $totalDeduction);
                $sheet->setCellValueByColumnAndRow(count($this->headings()), $row, $totalNet);

                $sheet->getStyle("A{$row}:" . $sheet->getHighestColumn() . "{$row}")->applyFromArray([
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

                $sheet->getRowDimension($row)->setRowHeight(30);
            },
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
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach (range(2, $sheet->getHighestRow()) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(25);
        }

        return [];
    }
}

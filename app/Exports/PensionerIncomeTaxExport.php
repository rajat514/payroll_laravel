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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;

class PensionerIncomeTaxExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    private int $serial = 1;
    protected array $filters;
    protected static array $arrearTypes = [];
    protected static $netPensions;


    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        $query = NetPension::with([
            // 'pensioner.employee',
            // 'pensionerBank',
            'monthlyPension',
            'pensionerDeduction',
        ]);

        // Filter by pensioner_id if provided
        if (!empty($filters['pensioner_id'])) {
            $query->where('pensioner_id', $filters['pensioner_id']);
        }

        // Filter by start month/year
        if (!empty($filters['start_month']) && !empty($filters['start_year'])) {
            $query->where(function ($q) {
                $q->where('year', '>', $this->filters['start_year'])
                    ->orWhere(function ($q2) {
                        $q2->where('year', $this->filters['start_year'])
                            ->where('month', '>=', $this->filters['start_month']);
                    });
            });
        }

        // Filter by end month/year
        if (!empty($filters['end_month']) && !empty($filters['end_year'])) {
            $query->where(function ($q) {
                $q->where('year', '<', $this->filters['end_year'])
                    ->orWhere(function ($q2) {
                        $q2->where('year', $this->filters['end_year'])
                            ->where('month', '<=', $this->filters['end_month']);
                    });
            });
        }

        // Filter by pension type
        if (!empty($filters['pension_type'])) {
            $query->whereHas('pensionerRelation', function ($q) {
                $q->where('type_of_pension', $this->filters['pension_type']);
            });
        }

        // Default to current month/year if no filters
        if (empty($filters)) {
            $now = now();
            $query->where('month', $now->month)->where('year', $now->year);
        }

        self::$netPensions = $query->orderBy('year')->orderBy('month')->get();

        // Parse unique arrear types from JSON
        self::$arrearTypes = self::$netPensions->flatMap(function ($item) {
            $arrearsRaw = $item->monthlyPension->arrears ?? '[]';
            $arrears = is_string($arrearsRaw) ? json_decode($arrearsRaw, true) : ($arrearsRaw ?? []);
            return collect($arrears)->pluck('type');
        })->unique()->values()->all();
    }

    public function title(): string
    {
        return 'Income Tax';
    }

    public function collection()
    {
        return self::$netPensions;
    }

    private function safe($value)
    {
        return $value ?? '0';
    }

    public function headings(): array
    {
        $user = auth()->user();

        $fixed = [
            'S.No.',
            'PPO No. / पीपीओ नंबर',
            'Month / माह',
            'Pensioner Name / पेंशनधारक का नाम',
            'Pension Type / पेंशन का प्रकार',
            'Account Number / खाता संख्या',
            'Gross Pension / कुल पेंशन',
            'Income Tax / आयकर',
        ];

        $arrears = array_map(fn($type) => "{$type}", self::$arrearTypes);

        return array_merge($fixed);
    }

    public function map($row): array
    {
        $user = auth()->user();

        $pensioner = $row->pensioner;
        $monthly = $row->monthlyPension ?? (object)[];
        $deduction = $row->pensionerDeduction ?? (object)[];
        $bank = $row->pensioner_bank ?? (object)[];

        // Decode arrears from JSON
        $arrearsRaw = $monthly->arrears ?? '[]';
        $arrearsArray = is_string($arrearsRaw) ? json_decode($arrearsRaw, true) : ($arrearsRaw ?? []);
        $arrears = collect($arrearsArray);

        $arrearMap = collect(self::$arrearTypes)->map(function ($type) use ($arrears) {
            return $this->safe(optional($arrears->firstWhere('type', $type))['amount'] ?? 0);
        });

        return array_merge(
            [
                $this->serial++,
                $pensioner->ppo_no,
                Carbon::create()->month($row->month)->format('F') . ', ' . $row->year,
                $pensioner->name,
                $pensioner->type_of_pension,
                $bank->account_no ?? '',
                $this->safe($monthly->total_pension),
                $this->safe($deduction->income_tax),
            ],
        );
    }

    private function formatStatus($value)
    {
        if (is_null($value)) {
            return '';
        }
        return $value == 1 ? 'Yes' : 'No';
    }
    private function safeValue($value, $isDate = false, $format = 'd-m-Y H:i')
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if ($isDate) {
            try {
                return \Carbon\Carbon::parse($value)->format($format);
            } catch (\Exception $e) {
                return $value; // fallback if not a valid date
            }
        }

        return $value;
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
                $sheet->setCellValue("G{$row}", $totalPension);
                $sheet->setCellValue("H{$row}", $totalIncomeTax);
                // $sheet->setCellValue("O{$row}", $totalDeduction);
                // $sheet->setCellValueByColumnAndRow(count($this->headings()), $row, $totalNet);

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

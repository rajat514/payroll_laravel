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

class SpecialAllowanceSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    protected $filters;
    private int $serial = 1;
    protected static $netSalaries;
    protected static array $salaryArrearTypes = [];
    protected static array $deductionRecoveryTypes = [];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        if (!self::$netSalaries) {
            $this->prepareDynamicColumns();
        }
    }

    public function title(): string
    {
        return 'Special Allowance';
    }

    private function prepareDynamicColumns(): void
    {
        $user = auth()->user();
        $now = Carbon::now();

        $currentMonth = $now->month;
        $currentYear = $now->year;

        $query = NetSalary::query();

        // filters
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

        if (
            empty($this->filters['start_month']) && empty($this->filters['start_year']) &&
            empty($this->filters['end_month']) && empty($this->filters['end_year'])
        ) {
            $query->where('month', $currentMonth)->where('year', $currentYear);
        }

        self::$netSalaries = $query->with([
            'paySlip.salaryArrears',
            'deduction.deductionRecoveries',
        ])->when(
            $user->institute !== 'BOTH',
            fn($q) => $q->where('employee->institute', $user->institute)
        )->orderBy('year', 'asc')->orderBy('month', 'asc')->get();

        // collect unique dynamic fields
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

    public function map($netSalary): array
    {
        $employee = $netSalary->employee;
        $paySlip = optional($netSalary->paySlip);
        $deduction = optional($netSalary->deduction);

        $monthName = Carbon::create()->month($netSalary->month)->format('F');
        $monthYear = $monthName . ' ' . $netSalary->year;

        $arrears = collect($paySlip->salaryArrears ?? []);
        $recoveries = collect($deduction->deductionRecoveries ?? []);

        $arrearMap = collect(self::$salaryArrearTypes)->map(
            fn($type) => $this->safeValue($arrears->firstWhere('type', $type)?->amount)
        );

        $recoveryMap = collect(self::$deductionRecoveryTypes)->map(
            fn($type) => $this->safeValue($recoveries->firstWhere('type', $type)?->amount)
        );

        return array_merge([
            $this->serial++,
            $monthYear,
            $employee->employee_code,
            $employee->name,
            $employee->latest_employee_designation->designation ?? '',
        ], $arrearMap->toArray(), $recoveryMap->toArray());
    }

    public function headings(): array
    {
        $fixed = [
            'अनु क्रमांक/Sr.NO.',
            'माह/Month',
            'कर्मचारी कोड/Employee Code',
            'नाम/Name',
            'पद/Designation',
        ];

        $arrearHeadings = array_map(fn($type) => "{$type}", self::$salaryArrearTypes);
        $recoveryHeadings = array_map(fn($type) => "{$type}", self::$deductionRecoveryTypes);

        return array_merge($fixed, $arrearHeadings, $recoveryHeadings);
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

    public function registerEvents(): array
    {
        return [];
    }
}

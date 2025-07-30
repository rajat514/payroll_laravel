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

class SelectedPensionerSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    private int $serial = 1;
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Net Pension';
    }

    public function collection()
    {
        $query = NetPension::with([
            'pensioner.employee',
            'pensionerBank',
            'monthlyPension',
            'pensionerDeduction'
        ]);

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

        if (!empty($this->filters['pension_type'])) {
            $query->whereHas('pensioner', function ($q) {
                $q->where('type_of_pension', $this->filters['pension_type']);
            });
        }

        if (empty($this->filters)) {
            $now = now();
            $query->where('month', $now->month)->where('year', $now->year);
        }

        return $query->where('pensioner_id', $this->filters['pensioner_id'])->orderBy('year')->orderBy('month')->get();
    }

    private function safe($value)
    {
        return $value ?? '0';
    }

    public function map($row): array
    {
        $pensioner = $row->pensioner;
        $employee = $pensioner->employee ?? null;
        $bank = $row->pensionerBank ?? (object) [];
        $monthly = $row->monthlyPension ?? (object) [];
        $deduction = $row->pensionerDeduction ?? (object) [];

        $monthYear = Carbon::create()->month($row->month)->format('F') . ', ' . $row->year;

        return [
            $this->serial++,
            $pensioner->ppo_no,
            $monthYear,
            $pensioner->name,
            $pensioner->type_of_pension,
            $bank->account_no ?? '',
            $this->safe($monthly->basic_pension ?? 0),
            $this->safe($monthly->dr_amount ?? 0),
            $this->safe($monthly->medical_allowance ?? 0),
            $this->safe($monthly->total_pension ?? 0),
            $this->safe($deduction->income_tax ?? 0),
            $this->safe($deduction->commutation_amount ?? 0),
            $this->safe($deduction->recovery ?? 0),
            $this->safe($deduction->other ?? 0),
            $this->safe($deduction->amount ?? 0),
            $this->safe($row->net_pension),
        ];
    }

    public function headings(): array
    {
        return [
            'S.No. / क्रम संख्या',
            'PPO No. / पीपीओ नंबर',
            'Month / माह',
            'Pensioner Name / पेंशनधारक का नाम',
            'Pension Type / पेंशन का प्रकार',
            'Account Number / खाता संख्या',
            'Basic Pension / मूल पेंशन',
            'Dearness Relief / महंगाई राहत',
            'Medical Allowance / चिकित्सा भत्ता',
            'Gross Pension / कुल पेंशन',
            'Income Tax / आयकर',
            'Commutation Amount / समर्पण राशि',
            'Recovery / वसूली',
            'Other Deduction / अन्य कटौती',
            'Total Deduction / कुल कटौती',
            'Net Pension / शुद्ध पेंशन'
        ];
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
                $sheet->setCellValue("P{$row}", $totalNet);

                $sheet->getStyle("A{$row}:P{$row}")->applyFromArray([
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
            }
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

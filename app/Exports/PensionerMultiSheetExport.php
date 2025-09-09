<?php

namespace App\Exports;

use Illuminate\Http\Client\Request;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

class PensionerMultiSheetExport implements WithMultipleSheets
{

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new SelectedPensionerSheet($this->filters),
            new PensionerIncomeTaxExport($this->filters),
        ];
    }
}

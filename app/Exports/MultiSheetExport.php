<?php

namespace App\Exports;

use Illuminate\Http\Client\Request;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

class MultiSheetExport implements WithMultipleSheets
{

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new EmployeesSheetExport($this->filters),
            new SalariesSheetExport(),
            // new DeductionSheetExport(),
            new GPFSheet($this->filters),
            new NewPensionSchemeSheet($this->filters),
            new LoanSheet(),
            new LICSheet($this->filters),
            new GISSheet($this->filters),
            new ITSheetExport($this->filters),
            new ProfessionalTaxSheet($this->filters),
            new BankSheet($this->filters),
            new specialAllowanceSheetExport($this->filters),
            new ComputerAdvanceSheet($this->filters),
            new LicenseFeeSheet($this->filters),
            new DonationNFCHSheetExport($this->filters),
            // new NetPensionSheet($this->filters),
        ];
    }
}

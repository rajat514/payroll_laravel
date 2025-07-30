<?php

namespace App\Exports;

use App\Models\NetPension;
use App\Models\NetSalary;
use Maatwebsite\Excel\Concerns\FromCollection;

class SalariesSheetExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //
        return NetSalary::all();
    }
}

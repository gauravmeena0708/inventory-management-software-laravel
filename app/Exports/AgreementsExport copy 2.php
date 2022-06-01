<?php

namespace App\Exports;
use App\Models\Agreement;
use Maatwebsite\Excel\Concerns\FromCollection;

class AgreementsExport implements FromCollection
{
    public function collection()
    {
        return Agreement::all();
    }
}

?>

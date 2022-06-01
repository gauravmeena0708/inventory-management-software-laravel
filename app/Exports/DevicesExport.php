<?php

namespace App\Exports;
use App\Models\Device;
use Maatwebsite\Excel\Concerns\FromCollection;

class DevicesExport implements FromCollection
{
    public function collection()
    {
        return Device::all();
    }
}

?>

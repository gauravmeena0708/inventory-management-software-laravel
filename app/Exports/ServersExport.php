<?php

namespace App\Exports;
use App\Models\Server;
use Maatwebsite\Excel\Concerns\FromCollection;

class ServersExport implements FromCollection
{
    public function collection()
    {
        return Server::all();
    }
}

?>

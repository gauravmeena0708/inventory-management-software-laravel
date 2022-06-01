<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class File extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;

    protected $fillable = [
        'name',
        'efile',
        'physical',
        'pfile',
        'subject'
    ];
}

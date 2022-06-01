<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Agreement extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;

    protected $fillable = [
        'name',
        'agency',
        'file_id',
        'type',
        'expiry',
        'annual_cost',
        'frequency',
        'paid_till',
        'remarks'
    ];

    public function file() {   
        return $this->belongsTo(File::class);
    }
}

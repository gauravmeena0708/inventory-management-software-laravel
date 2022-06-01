<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;

    protected $fillable = [
        'name',
        'file_id',
        'agreement_id',
        'due_date',
        'remark',
        'pending'
    ];

    public function agreement() {
        
        return $this->belongsTo(Agreement::class);
    }

    
}

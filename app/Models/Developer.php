<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Developer extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;

    public function reporting() {
        
        return $this->belongsTo(User::class);
    }

    public function category() {
        
        return $this->belongsTo(Devcat::class);
    }
}

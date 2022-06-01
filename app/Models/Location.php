<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Location extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;

    public function officials() {   
        return $this->hasMany(Official::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Laptop extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;
    
}

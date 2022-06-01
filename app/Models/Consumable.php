<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Consumable extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;

    protected $fillable = [
        'name',
        'max_quantity',
        'min_quantity',
        'in_stock'
    ];

    

    public function latestentry() {
        $item = $this->hasMany(Entry::class)->latest();
        return $item;
    }

    public function entries() {
        $item = $this->hasMany(Entry::class);
        return $item;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Entry extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logFillable = true;
    
    protected $fillable = [
        'consumable_id',
        'type',
        'amount',
        'stock',
        'issuer_id'
    ];

    public function consumable() {
        
        return $this->belongsTo(Consumable::class);
    }

    public function issuer() {
        
        return $this->belongsTo(Official::class);
    }
}

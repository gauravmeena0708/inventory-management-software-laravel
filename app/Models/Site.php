<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'altitude',
        'geofence_geojson',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'altitude' => 'decimal:2',
        'geofence_geojson' => 'array',
        'is_active' => 'boolean',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function organizationalUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class, 'organizational_unit_site');
    }
}

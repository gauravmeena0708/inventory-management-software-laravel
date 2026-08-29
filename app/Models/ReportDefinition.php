<?php

namespace App\Models;

use App\Enums\ReportCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'generator_class',
        'default_format',
        'allowed_formats',
        'is_certifiable',
        'supports_as_on_date',
        'supports_period',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => ReportCategory::class,
            'allowed_formats' => 'array',
            'is_certifiable' => 'boolean',
            'supports_as_on_date' => 'boolean',
            'supports_period' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class, 'report_definition_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory(Builder $query, ReportCategory|string $category): Builder
    {
        $val = $category instanceof ReportCategory ? $category->value : $category;

        return $query->where('category', $val);
    }
}

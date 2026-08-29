<?php

namespace App\Http\Requests;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('location') ?? Location::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'location_type' => ['sometimes', Rule::enum(LocationType::class)],
            'level_number' => ['nullable', 'string', 'max:255'],
            'sublocation' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'seat' => ['nullable', 'string', 'max:50'],
            'point' => ['nullable', 'string', 'max:50'],
            'pin' => ['nullable', 'string', 'max:50'],
            'is_restricted' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

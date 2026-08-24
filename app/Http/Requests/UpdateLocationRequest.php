<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sublocation' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'seat' => ['nullable', 'string', 'max:50'],
            'point' => ['nullable', 'string', 'max:50'],
            'pin' => ['nullable', 'string', 'max:50'],
        ];
    }
}

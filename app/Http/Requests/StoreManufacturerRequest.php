<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Manufacturer;
use Illuminate\Foundation\Http\FormRequest;

class StoreManufacturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'support_contact' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pin' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'link1' => ['nullable', 'string', 'max:255'],
            'link2' => ['nullable', 'string', 'max:255'],
            'link3' => ['nullable', 'string', 'max:255'],
        ];
    }
}

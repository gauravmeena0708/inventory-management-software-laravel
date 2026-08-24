<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\FileRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreFileRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'efile_number' => ['nullable', 'string', 'max:100'],
            'physical_name' => ['nullable', 'string', 'max:255'],
            'physical_number' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:100'],
            'opened_at' => ['nullable', 'date'],
        ];
    }
}

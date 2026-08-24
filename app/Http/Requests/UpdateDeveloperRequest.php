<?php

namespace App\Http\Requests;

use App\Models\Developer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeveloperRequest extends FormRequest
{
    public function authorize(): bool
    {
        $developer = $this->route('developer');
        return $this->user()?->can('update', $developer ?? Developer::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'reporting_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:devcats,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'salary' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agreement = $this->route('agreement');
        return $this->user()?->can('update', $agreement ?? Agreement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'agency' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'max:100'],
            'annual_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_interval_months' => ['nullable', 'integer', 'min:1', 'max:12'],
            'billing_anchor_date' => ['nullable', 'date'],
            'expiry' => ['nullable', 'date'],
            'file_id' => ['nullable', 'exists:files,id'],
            'paid_till' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

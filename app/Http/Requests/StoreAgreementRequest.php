<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Agreement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'agency' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'annual_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_interval_months' => ['required', 'integer', 'min:1', 'max:12'],
            'billing_anchor_date' => ['required', 'date'],
            'expiry' => ['required', 'date', 'after_or_equal:billing_anchor_date'],
            'file_id' => ['nullable', 'exists:files,id'],
            'paid_till' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

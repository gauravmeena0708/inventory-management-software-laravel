<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'organizational_unit_id' => [
                'sometimes',
                'nullable',
                Rule::exists('organizational_units', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('organizational_unit_id')) {
                return;
            }

            $unitId = $this->integer('organizational_unit_id');
            if (! $unitId || ! app(OrganizationalContext::class)->canWrite($this->user(), $unitId)) {
                $validator->errors()->add('organizational_unit_id', 'You do not have write access to this organizational unit.');
            }
        });
    }
}

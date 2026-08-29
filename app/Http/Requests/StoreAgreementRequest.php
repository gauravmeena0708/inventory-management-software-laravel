<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'organizational_unit_id' => [
                'nullable',
                Rule::exists('organizational_units', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('organizational_unit_id') && $this->user()) {
            $this->merge([
                'organizational_unit_id' => app(OrganizationalContext::class)
                    ->getActiveContext($this->user())?->id,
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unitId = $this->integer('organizational_unit_id');
            if ($unitId && ! app(OrganizationalContext::class)->canWrite($this->user(), $unitId)) {
                $validator->errors()->add('organizational_unit_id', 'You do not have write access to this organizational unit.');
            }
        });
    }
}

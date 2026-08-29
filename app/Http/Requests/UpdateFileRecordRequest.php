<?php

namespace App\Http\Requests;

use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFileRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('file')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'efile_number' => ['nullable', 'string', 'max:100'],
            'physical_name' => ['nullable', 'string', 'max:255'],
            'physical_number' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:100'],
            'opened_at' => ['nullable', 'date'],
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

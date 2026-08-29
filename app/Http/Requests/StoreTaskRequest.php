<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'file_id' => ['nullable', 'exists:files,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
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

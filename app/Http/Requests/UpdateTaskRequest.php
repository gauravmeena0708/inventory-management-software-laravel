<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $this->user()?->can('update', $task ?? Task::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'file_id' => ['nullable', 'exists:files,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
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

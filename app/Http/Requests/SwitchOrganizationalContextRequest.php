<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SwitchOrganizationalContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'organizational_unit_id' => ['required', 'integer'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $unitId = (int) $this->input('organizational_unit_id');
            $allowed = $this->user()?->activeOrganizationalUnits()
                ->whereKey($unitId)
                ->exists() ?? false;

            if (! $allowed) {
                $validator->errors()->add(
                    'organizational_unit_id',
                    'Select an active organizational unit membership.'
                );
            }
        }];
    }
}

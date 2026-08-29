<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');
        return $this->user()?->can('assign', $asset ?? Asset::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'official_id' => ['required', 'exists:officials,id'],
            'remarks' => ['nullable', 'string'],
            'condition_out' => ['nullable', 'string'],
            'assigned_at' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Consumable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consumable = $this->route('consumable');
        return $this->user()?->can('update', $consumable ?? Consumable::class) ?? false;
    }

    public function rules(): array
    {
        $consumable = $this->route('consumable');
        $consumableId = $consumable instanceof Consumable ? $consumable->id : $consumable;

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('consumables', 'sku')->ignore($consumableId)],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

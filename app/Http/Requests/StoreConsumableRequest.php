<?php

namespace App\Http\Requests;

use App\Models\Consumable;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Consumable::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:consumables,sku'],
            'unit' => ['nullable', 'string', 'max:50'],
            'in_stock' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

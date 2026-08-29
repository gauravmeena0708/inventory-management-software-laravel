<?php

namespace App\Http\Requests;

use App\Enums\StockEntryType;
use App\Models\Consumable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('postEntry', Consumable::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'consumable_id' => ['sometimes', 'nullable', 'exists:consumables,id'],
            'type' => ['required', Rule::enum(StockEntryType::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'recipient_official_id' => [
                Rule::requiredIf(function () {
                    $type = $this->input('type');
                    return $type === StockEntryType::ISSUE->value || $type === 'issue' || $type === StockEntryType::ISSUE;
                }),
                'nullable',
                'exists:officials,id',
            ],
            'remarks' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
        ];
    }
}

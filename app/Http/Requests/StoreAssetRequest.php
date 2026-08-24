<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_type' => ['required', Rule::enum(AssetType::class)],
            'asset_tag' => ['nullable', 'string', 'max:100', 'unique:assets,asset_tag'],
            'serial_number' => ['nullable', 'string', 'max:191'],
            'model_number' => ['nullable', 'string', 'max:191'],
            'part_code' => ['nullable', 'string', 'max:191'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'assigned_official_id' => ['nullable', 'exists:officials,id'],
            'status' => ['nullable', Rule::enum(AssetStatus::class)],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:45'],
            'operating_system' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'array'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'warranty_expiry' => ['nullable', 'date'],
            'end_of_sale' => ['nullable', 'date'],
            'end_of_support' => ['nullable', 'date'],
            'amc_start' => ['nullable', 'date'],
            'amc_end' => ['nullable', 'date'],
            'amc_cost' => ['nullable', 'numeric', 'min:0'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'contract_reference' => ['nullable', 'string', 'max:255'],
            'file_id' => ['nullable', 'exists:files,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

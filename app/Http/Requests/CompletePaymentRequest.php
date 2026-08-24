<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class CompletePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');
        return $this->user()?->can('complete', $payment ?? Payment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:100'],
            'paid_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

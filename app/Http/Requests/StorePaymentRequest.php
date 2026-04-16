<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cheque = $this->input('cheque_number');
        $advanceDirection = $this->input('advance_direction');

        $this->merge([
            'cheque_number' => filled($cheque) ? trim((string) $cheque) : null,
            'payment_kind' => $this->filled('payment_kind') ? trim((string) $this->input('payment_kind')) : null,
            'advance_direction' => filled($advanceDirection) ? trim((string) $advanceDirection) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cheque_number' => ['nullable', 'string', 'max:50'],
            'payment_kind' => ['required', 'in:receivable,payable,advance'],
            'advance_direction' => ['nullable', 'in:paid,received', 'required_if:payment_kind,advance'],
        ];
    }
}

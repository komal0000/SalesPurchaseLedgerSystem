<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->all();

        $payload['items'] = collect($payload['items'] ?? [])
            ->map(function ($item) {
                $item = is_array($item) ? $item : [];

                return [
                    'particular' => trim((string) ($item['particular'] ?? '')),
                    'qty' => $item['qty'] ?? null,
                    'price' => $item['price'] ?? null,
                ];
            })
            ->filter(fn (array $item) => $item['particular'] !== '' || filled($item['qty']) || filled($item['price']))
            ->values()
            ->all();

        $payload['payments'] = collect($payload['payments'] ?? [])
            ->map(function ($payment) {
                $payment = is_array($payment) ? $payment : [];

                return [
                    'account_id' => $payment['account_id'] ?? null,
                    'amount' => $payment['amount'] ?? null,
                    'cheque_number' => filled($payment['cheque_number'] ?? null)
                        ? trim((string) $payment['cheque_number'])
                        : null,
                ];
            })
            ->filter(fn (array $payment) => filled($payment['account_id']) || filled($payment['amount']) || filled($payment['cheque_number']))
            ->values()
            ->all();

        $this->replace($payload);
    }

    public function rules(): array
    {
        return [
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.particular' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.cheque_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}

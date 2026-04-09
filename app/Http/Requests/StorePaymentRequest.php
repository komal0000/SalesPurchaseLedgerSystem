<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cheque = $this->input('cheque_number');

        $this->merge([
            'cheque_number' => filled($cheque) ? trim((string) $cheque) : null,
            'sale_id' => $this->filled('sale_id') ? $this->input('sale_id') : null,
            'purchase_id' => $this->filled('purchase_id') ? $this->input('purchase_id') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cheque_number' => ['nullable', 'string', 'max:50'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $saleId = $this->input('sale_id');
            $purchaseId = $this->input('purchase_id');
            $partyId = (int) $this->input('party_id');

            if (!empty($saleId) && !empty($purchaseId)) {
                $validator->errors()->add('sale_id', 'A payment can be linked to a sale or purchase, not both.');
                $validator->errors()->add('purchase_id', 'A payment can be linked to a sale or purchase, not both.');

                return;
            }

            if (!empty($saleId)) {
                $isPartyMatched = Sale::query()
                    ->whereKey($saleId)
                    ->where('party_id', $partyId)
                    ->exists();

                if (!$isPartyMatched) {
                    $validator->errors()->add('sale_id', 'The selected sale does not belong to the chosen party.');
                }
            }

            if (!empty($purchaseId)) {
                $isPartyMatched = Purchase::query()
                    ->whereKey($purchaseId)
                    ->where('party_id', $partyId)
                    ->exists();

                if (!$isPartyMatched) {
                    $validator->errors()->add('purchase_id', 'The selected purchase does not belong to the chosen party.');
                }
            }
        });
    }
}

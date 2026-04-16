<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $saleId = $data['sale_id'] ?? null;
            $purchaseId = $data['purchase_id'] ?? null;

            if (!empty($saleId) && !empty($purchaseId)) {
                throw new InvalidArgumentException('Payment cannot link to both a sale and a purchase.');
            }

            if (!empty($saleId)) {
                $isPartyMatched = Sale::query()
                    ->whereKey($saleId)
                    ->where('party_id', $data['party_id'])
                    ->exists();

                if (!$isPartyMatched) {
                    throw new InvalidArgumentException('The selected sale does not belong to the chosen party.');
                }
            }

            if (!empty($purchaseId)) {
                $isPartyMatched = Purchase::query()
                    ->whereKey($purchaseId)
                    ->where('party_id', $data['party_id'])
                    ->exists();

                if (!$isPartyMatched) {
                    throw new InvalidArgumentException('The selected purchase does not belong to the chosen party.');
                }
            }

            $payment = Payment::query()->create([
                'party_id' => $data['party_id'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'payment_kind' => $data['payment_kind'] ?? null,
                'advance_direction' => $data['advance_direction'] ?? null,
                'account_id' => $data['account_id'],
                'cheque_number' => $data['cheque_number'] ?? null,
                'sale_id' => $saleId ?: null,
                'purchase_id' => $purchaseId ?: null,
            ]);

            $this->ledger->recordPayment($payment);

            return $payment->load(['party', 'account', 'sale', 'purchase']);
        });
    }

    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $this->ledger->reversePayment($payment);
            $payment->delete();
        });
    }
}

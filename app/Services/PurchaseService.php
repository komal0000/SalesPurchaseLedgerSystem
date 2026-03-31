<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::query()->create([
                'party_id' => $data['party_id'],
                'total' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $purchase->items()->create([
                    'particular' => $item['particular'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);
            }

            $purchase->update([
                'total' => $purchase->items()->sum('total'),
            ]);

            $this->ledger->recordPurchase($purchase->fresh());

            foreach ($data['payments'] ?? [] as $paymentData) {
                $payment = $purchase->payments()->create([
                    'party_id' => $purchase->party_id,
                    'amount' => $paymentData['amount'],
                    'type' => 'given',
                    'account_id' => $paymentData['account_id'],
                    'cheque_number' => $paymentData['cheque_number'] ?? null,
                    'sale_id' => null,
                ]);

                $this->ledger->recordPayment($payment);
            }

            return $purchase->load(['party', 'items', 'payments.account']);
        });
    }

    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->delete();
        });
    }
}

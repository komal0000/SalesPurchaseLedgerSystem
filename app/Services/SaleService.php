<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::query()->create([
                'party_id' => $data['party_id'],
                'total' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $sale->items()->create([
                    'particular' => $item['particular'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);
            }

            $sale->update([
                'total' => $sale->items()->sum('total'),
            ]);

            $this->ledger->recordSale($sale->fresh());

            foreach ($data['payments'] ?? [] as $paymentData) {
                $payment = $sale->payments()->create([
                    'party_id' => $sale->party_id,
                    'amount' => $paymentData['amount'],
                    'type' => 'received',
                    'account_id' => $paymentData['account_id'],
                    'cheque_number' => $paymentData['cheque_number'] ?? null,
                    'purchase_id' => null,
                ]);

                $this->ledger->recordPayment($payment);
            }

            return $sale->load(['party', 'items', 'payments.account']);
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $sale->delete();
        });
    }
}

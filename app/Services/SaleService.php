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

            return $sale->load(['party', 'items']);
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $sale->delete();
        });
    }
}

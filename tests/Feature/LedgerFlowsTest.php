<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Party;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_ledger_flows_balance_correctly(): void
    {
        $ledger = app(LedgerService::class);
        $saleService = app(SaleService::class);
        $purchaseService = app(PurchaseService::class);
        $paymentService = app(PaymentService::class);

        $cash = Account::query()->create([
            'name' => 'Cash',
            'type' => 'cash',
        ]);

        $ram = Party::query()->create([
            'name' => 'Ram Traders',
            'phone' => null,
        ]);

        $sita = Party::query()->create([
            'name' => 'Sita Suppliers',
            'phone' => null,
        ]);

        $saleService->create([
            'party_id' => $ram->id,
            'items' => [
                [
                    'particular' => 'Goods',
                    'qty' => 1,
                    'price' => 1000,
                ],
            ],
        ]);

        $this->assertSame(1000.0, $ledger->partyBalance($ram->id));

        $paymentService->create([
            'party_id' => $ram->id,
            'amount' => 400,
            'type' => 'received',
            'account_id' => $cash->id,
            'sale_id' => null,
            'purchase_id' => null,
        ]);

        $this->assertSame(600.0, $ledger->partyBalance($ram->id));
        $this->assertSame(400.0, $ledger->accountBalance($cash->id));

        $purchaseService->create([
            'party_id' => $sita->id,
            'items' => [
                [
                    'particular' => 'Materials',
                    'qty' => 1,
                    'price' => 500,
                ],
            ],
        ]);

        $this->assertSame(-500.0, $ledger->partyBalance($sita->id));

        $paymentService->create([
            'party_id' => $sita->id,
            'amount' => 200,
            'type' => 'given',
            'account_id' => $cash->id,
            'sale_id' => null,
            'purchase_id' => null,
        ]);

        $this->assertSame(-300.0, $ledger->partyBalance($sita->id));
        $this->assertSame(200.0, $ledger->accountBalance($cash->id));
    }
}

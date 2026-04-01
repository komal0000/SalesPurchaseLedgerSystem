<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Ledger;
use App\Models\Party;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
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

    public function test_payment_delete_is_soft_delete_and_reverses_ledger(): void
    {
        $ledger = app(LedgerService::class);
        $paymentService = app(PaymentService::class);

        $cash = Account::query()->create([
            'name' => 'Cash',
            'type' => 'cash',
        ]);

        $party = Party::query()->create([
            'name' => 'Ram Traders',
            'phone' => null,
        ]);

        $payment = $paymentService->create([
            'party_id' => $party->id,
            'amount' => 400,
            'type' => 'received',
            'account_id' => $cash->id,
            'sale_id' => null,
            'purchase_id' => null,
        ]);

        $this->assertSame(-400.0, $ledger->partyBalance($party->id));
        $this->assertSame(400.0, $ledger->accountBalance($cash->id));

        $paymentService->delete($payment);

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertSame(0.0, $ledger->partyBalance($party->id));
        $this->assertSame(0.0, $ledger->accountBalance($cash->id));
        $this->assertSame(4, Ledger::query()->count());
    }

    public function test_ledger_entries_cannot_be_updated_or_deleted(): void
    {
        $entry = Ledger::query()->create([
            'party_id' => Party::query()->create([
                'name' => 'Immutable Party',
                'phone' => null,
            ])->id,
            'account_id' => null,
            'dr_amount' => 100,
            'cr_amount' => 0,
            'type' => 'sale',
            'ref_id' => 111111,
            'ref_table' => 'sales',
        ]);

        $this->expectException(LogicException::class);
        $entry->update(['dr_amount' => 200]);
    }

    public function test_ledger_entries_cannot_be_deleted(): void
    {
        $entry = Ledger::query()->create([
            'party_id' => Party::query()->create([
                'name' => 'Protected Party',
                'phone' => null,
            ])->id,
            'account_id' => null,
            'dr_amount' => 100,
            'cr_amount' => 0,
            'type' => 'sale',
            'ref_id' => 222222,
            'ref_table' => 'sales',
        ]);

        $this->expectException(LogicException::class);
        $entry->delete();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Party;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentKindFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_bill_payment_requires_kind_selection(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $party = Party::query()->create([
            'name' => 'Kind Required Party',
            'phone' => '9800000041',
        ]);

        $account = Account::query()->create([
            'name' => 'Cash',
            'type' => 'cash',
        ]);

        $response = $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), [
                'party_id' => $party->id,
                'account_id' => $account->id,
                'amount' => 100,
            ]);

        $response
            ->assertRedirect(route('payments.create'))
            ->assertSessionHasErrors(['payment_kind']);
    }

    public function test_advance_payment_requires_advance_direction(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $party = Party::query()->create([
            'name' => 'Advance Direction Party',
            'phone' => '9800000044',
        ]);

        $account = Account::query()->create([
            'name' => 'Cash',
            'type' => 'cash',
        ]);

        $response = $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), [
                'party_id' => $party->id,
                'account_id' => $account->id,
                'amount' => 100,
                'payment_kind' => 'advance',
            ]);

        $response
            ->assertRedirect(route('payments.create'))
            ->assertSessionHasErrors(['advance_direction']);
    }

    public function test_store_payment_ignores_sale_and_purchase_inputs_from_manual_flow(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $party = Party::query()->create([
            'name' => 'Manual Payment Party',
            'phone' => '9800000042',
        ]);

        $account = Account::query()->create([
            'name' => 'Bank',
            'type' => 'bank',
        ]);

        $this->actingAs($user)
            ->post(route('payments.store'), [
                'party_id' => $party->id,
                'account_id' => $account->id,
                'amount' => 200,
                'payment_kind' => 'payable',
                'sale_id' => 9999,
                'purchase_id' => 8888,
            ])
            ->assertRedirect();

        $payment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame('payable', $payment->payment_kind);
        $this->assertSame('given', $payment->type);
        $this->assertNull($payment->sale_id);
        $this->assertNull($payment->purchase_id);
    }

    public function test_non_bill_kind_maps_to_expected_ledger_direction_type(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $party = Party::query()->create([
            'name' => 'Direct Kind Party',
            'phone' => '9800000043',
        ]);

        $account = Account::query()->create([
            'name' => 'Cash',
            'type' => 'cash',
        ]);

        $this->actingAs($user)
            ->post(route('payments.store'), [
                'party_id' => $party->id,
                'account_id' => $account->id,
                'amount' => 320,
                'payment_kind' => 'payable',
            ])
            ->assertRedirect();

        $payablePayment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame('payable', $payablePayment->payment_kind);
        $this->assertSame('given', $payablePayment->type);

        $this->actingAs($user)
            ->post(route('payments.store'), [
                'party_id' => $party->id,
                'account_id' => $account->id,
                'amount' => 140,
                'payment_kind' => 'advance',
                'advance_direction' => 'received',
            ])
            ->assertRedirect();

        $advancePayment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame('advance', $advancePayment->payment_kind);
        $this->assertSame('received', $advancePayment->advance_direction);
        $this->assertSame('received', $advancePayment->type);

        $this->actingAs($user)
            ->post(route('payments.store'), [
                'party_id' => $party->id,
                'account_id' => $account->id,
                'amount' => 90,
                'payment_kind' => 'advance',
                'advance_direction' => 'paid',
            ])
            ->assertRedirect();

        $advancePaidPayment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame('advance', $advancePaidPayment->payment_kind);
        $this->assertSame('paid', $advancePaidPayment->advance_direction);
        $this->assertSame('given', $advancePaidPayment->type);
    }
}

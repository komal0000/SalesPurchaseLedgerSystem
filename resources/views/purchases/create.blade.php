@extends('layouts.app')

@section('content')
    @php
        $initialItems = collect(old('items', []))
            ->map(fn ($item) => [
                'particular' => (string) ($item['particular'] ?? ''),
                'qty' => (float) ($item['qty'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
            ])
            ->filter(fn (array $item) => $item['particular'] !== '' || $item['qty'] > 0 || $item['price'] > 0)
            ->values()
            ->all();

        $initialPayments = collect(old('payments', []))
            ->map(fn ($payment) => [
                'account_id' => (string) ($payment['account_id'] ?? ''),
                'amount' => (float) ($payment['amount'] ?? 0),
                'cheque_number' => (string) ($payment['cheque_number'] ?? ''),
            ])
            ->filter(fn (array $payment) => $payment['account_id'] !== '' || $payment['amount'] > 0 || $payment['cheque_number'] !== '')
            ->values()
            ->all();
    @endphp

    <div class="space-y-6" x-data="transactionEntryForm({
        partyId: @js(old('party_id', '')),
        items: @js($initialItems),
        payments: @js($initialPayments),
        defaultCashAccountId: @js($defaultCashAccountId),
    })">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Purchase Entry</h1>
            <p class="text-sm text-gray-500">Enter to move field-to-field. Add item and payment rows inline on this page.</p>
        </div>

        <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6" @submit="validateBeforeSubmit($event)">
            @csrf

            <div class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-8">
                        <label for="party_id" class="text-sm font-semibold text-gray-700">Party</label>
                        <select id="party_id" name="party_id" x-model="partyId" x-ref="party" @keydown.enter.prevent="focus('itemParticular')" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" required>
                            <option value="">Select party</option>
                            @foreach ($parties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Bill Total</p>
                        <p class="mt-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-right font-mono text-lg font-semibold text-indigo-700" x-text="currency(grandTotal())"></p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-300 bg-white shadow-sm">
                <div class="border-b border-gray-300 px-4 py-3">
                    <h2 class="font-semibold text-gray-900">Items</h2>
                </div>

                <div class="grid grid-cols-12 gap-2 border-b border-gray-300 bg-gray-50 px-4 py-3">
                    <div class="col-span-12 md:col-span-5">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Particular</label>
                        <input x-model="draftItem.particular" x-ref="itemParticular" @keydown.enter.prevent="focus('itemQty')" enterkeyhint="next" type="text" class="mt-1 w-full rounded border border-gray-300 px-2 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="Item/Particular">
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Qty</label>
                        <input x-model.number="draftItem.qty" x-ref="itemQty" @input="updateDraftItemTotal" @keydown.enter.prevent="focus('itemPrice')" enterkeyhint="next" type="number" step="0.01" min="0.01" class="mt-1 w-full rounded border border-gray-300 px-2 py-2 text-right text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Rate</label>
                        <input x-model.number="draftItem.price" x-ref="itemPrice" @input="updateDraftItemTotal" @keydown.enter.prevent="commitItem" enterkeyhint="done" type="number" step="0.01" min="0" class="mt-1 w-full rounded border border-gray-300 px-2 py-2 text-right text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Total</label>
                        <input :value="currency(draftItem.total)" type="text" readonly class="mt-1 w-full rounded border border-gray-300 bg-gray-100 px-2 py-2 text-right text-sm font-mono text-gray-700">
                    </div>
                    <div class="col-span-12 md:col-span-1 md:flex md:items-end">
                        <button type="button" @click="commitItem" class="mt-1 w-full rounded bg-indigo-600 px-2 py-2 text-sm font-semibold text-white hover:bg-indigo-700">ADD</button>
                    </div>
                </div>

                <div class="overflow-x-auto px-4 py-3">
                    <table class="min-w-full border border-gray-300 text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="border border-gray-300 px-2 py-1 text-left">Item</th>
                                <th class="border border-gray-300 px-2 py-1 text-right">Rate</th>
                                <th class="border border-gray-300 px-2 py-1 text-right">Qty</th>
                                <th class="border border-gray-300 px-2 py-1 text-right">Total</th>
                                <th class="border border-gray-300 px-2 py-1 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="5" class="border border-gray-300 px-2 py-3 text-center text-gray-500">No items added yet.</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in items" :key="`item-${index}`">
                                <tr>
                                    <td class="border border-gray-300 px-2 py-1" x-text="item.particular"></td>
                                    <td class="border border-gray-300 px-2 py-1 text-right font-mono" x-text="currency(item.price)"></td>
                                    <td class="border border-gray-300 px-2 py-1 text-right font-mono" x-text="number(item.qty)"></td>
                                    <td class="border border-gray-300 px-2 py-1 text-right font-mono" x-text="currency(item.total)"></td>
                                    <td class="border border-gray-300 px-2 py-1 text-center">
                                        <button type="button" @click="removeItem(index)" class="rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-300 bg-white shadow-sm">
                <div class="border-b border-gray-300 px-4 py-3">
                    <h2 class="font-semibold text-gray-900">Payment</h2>
                </div>

                <div class="grid grid-cols-12 gap-2 border-b border-gray-300 bg-gray-50 px-4 py-3">
                    <div class="col-span-6 md:col-span-4">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Payment Via</label>
                        <select x-model="draftPayment.account_id" x-ref="paymentAccount" @keydown.enter.prevent="focus('paymentAmount')" class="mt-1 w-full rounded border border-gray-300 px-2 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            @if (! $defaultCashAccountId)
                                <option value="">Select account</option>
                            @endif
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst($account->type) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-6 md:col-span-3">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Payment Amount</label>
                        <input x-model.number="draftPayment.amount" x-ref="paymentAmount" @keydown.enter.prevent="focus('paymentCheque')" enterkeyhint="next" type="number" step="0.01" min="0.01" class="mt-1 w-full rounded border border-gray-300 px-2 py-2 text-right text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="0.00">
                    </div>
                    <div class="col-span-8 md:col-span-3">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">Cheque Number</label>
                        <input x-model="draftPayment.cheque_number" x-ref="paymentCheque" @keydown.enter.prevent="commitPayment" enterkeyhint="done" type="text" maxlength="50" class="mt-1 w-full rounded border border-gray-300 px-2 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="Optional">
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-transparent">Action</label>
                        <button type="button" @click="commitPayment" class="mt-1 w-full rounded bg-indigo-600 px-2 py-2 text-sm font-semibold text-white hover:bg-indigo-700">ADD</button>
                    </div>
                </div>

                <div class="overflow-x-auto px-4 py-3">
                    <table class="min-w-full border border-gray-300 text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="border border-gray-300 px-2 py-1 text-left">Account</th>
                                <th class="border border-gray-300 px-2 py-1 text-right">Amount</th>
                                <th class="border border-gray-300 px-2 py-1 text-left">Cheque Number</th>
                                <th class="border border-gray-300 px-2 py-1 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="payments.length === 0">
                                <tr>
                                    <td colspan="4" class="border border-gray-300 px-2 py-3 text-center text-gray-500">No payments added.</td>
                                </tr>
                            </template>
                            <template x-for="(payment, index) in payments" :key="`payment-${index}`">
                                <tr>
                                    <td class="border border-gray-300 px-2 py-1" x-text="accountName(payment.account_id)"></td>
                                    <td class="border border-gray-300 px-2 py-1 text-right font-mono" x-text="currency(payment.amount)"></td>
                                    <td class="border border-gray-300 px-2 py-1" x-text="payment.cheque_number || '-' "></td>
                                    <td class="border border-gray-300 px-2 py-1 text-center">
                                        <button type="button" @click="removePayment(index)" class="rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold text-gray-800">
                            <tr>
                                <td class="border border-gray-300 px-2 py-1 text-right">Paid</td>
                                <td class="border border-gray-300 px-2 py-1 text-right font-mono" x-text="currency(paymentTotal())"></td>
                                <td class="border border-gray-300 px-2 py-1"></td>
                                <td class="border border-gray-300 px-2 py-1"></td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-2 py-1 text-right">Due</td>
                                <td class="border border-gray-300 px-2 py-1 text-right font-mono" x-text="currency(dueTotal())"></td>
                                <td class="border border-gray-300 px-2 py-1"></td>
                                <td class="border border-gray-300 px-2 py-1"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <template x-for="(item, index) in items" :key="`item-hidden-${index}`">
                <div>
                    <input type="hidden" :name="`items[${index}][particular]`" :value="item.particular">
                    <input type="hidden" :name="`items[${index}][qty]`" :value="number(item.qty)">
                    <input type="hidden" :name="`items[${index}][price]`" :value="number(item.price)">
                </div>
            </template>

            <template x-for="(payment, index) in payments" :key="`payment-hidden-${index}`">
                <div>
                    <input type="hidden" :name="`payments[${index}][account_id]`" :value="payment.account_id">
                    <input type="hidden" :name="`payments[${index}][amount]`" :value="number(payment.amount)">
                    <input type="hidden" :name="`payments[${index}][cheque_number]`" :value="payment.cheque_number">
                </div>
            </template>

            <p x-show="message" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700" x-text="message"></p>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('purchases.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Purchase</button>
            </div>
        </form>
    </div>

    <script>
        function transactionEntryForm(initialState) {
            return {
                partyId: String(initialState.partyId || ''),
                items: [],
                payments: [],
                defaultCashAccountId: String(initialState.defaultCashAccountId || ''),
                message: '',
                draftItem: {
                    particular: '',
                    qty: 1,
                    price: 0,
                    total: 0,
                },
                draftPayment: {
                    account_id: String(initialState.defaultCashAccountId || ''),
                    amount: '',
                    cheque_number: '',
                },
                accountLookup: @js($accounts->mapWithKeys(fn ($account) => [$account->id => $account->name])->all()),
                init() {
                    this.items = (initialState.items || []).map((item) => {
                        const qty = this.toNumber(item.qty);
                        const price = this.toNumber(item.price);

                        return {
                            particular: String(item.particular || ''),
                            qty,
                            price,
                            total: qty * price,
                        };
                    });

                    this.payments = (initialState.payments || []).map((payment) => ({
                        account_id: String(payment.account_id || ''),
                        amount: this.toNumber(payment.amount),
                        cheque_number: String(payment.cheque_number || ''),
                    }));

                    if (window.jQuery && this.$refs.party) {
                        const $party = window.jQuery(this.$refs.party);
                        $party.val(this.partyId).trigger('change.select2');
                        $party.on('change', (event) => {
                            this.partyId = String(event.target.value || '');
                        });
                    }

                    this.updateDraftItemTotal();
                },
                toNumber(value) {
                    const number = Number(value);

                    return Number.isFinite(number) ? number : 0;
                },
                number(value) {
                    return this.toNumber(value).toFixed(2);
                },
                currency(value) {
                    return this.toNumber(value).toFixed(2);
                },
                focus(refName) {
                    this.$nextTick(() => {
                        if (this.$refs[refName]) {
                            this.$refs[refName].focus();
                        }
                    });
                },
                updateDraftItemTotal() {
                    this.draftItem.total = this.toNumber(this.draftItem.qty) * this.toNumber(this.draftItem.price);
                },
                commitItem() {
                    this.message = '';

                    if (!this.partyId) {
                        this.message = 'Select party before adding items.';
                        this.focus('party');

                        return;
                    }

                    const particular = String(this.draftItem.particular || '').trim();
                    const qty = this.toNumber(this.draftItem.qty);
                    const price = this.toNumber(this.draftItem.price);

                    if (!particular || qty <= 0 || price < 0) {
                        this.message = 'Enter particular, qty, and rate to add item.';

                        return;
                    }

                    this.items.push({
                        particular,
                        qty,
                        price,
                        total: qty * price,
                    });

                    this.draftItem = {
                        particular: '',
                        qty: 1,
                        price: 0,
                        total: 0,
                    };

                    this.focus('itemParticular');
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                commitPayment() {
                    this.message = '';

                    if (!this.partyId) {
                        this.message = 'Select party before adding payment.';
                        this.focus('party');

                        return;
                    }

                    const accountId = String(this.draftPayment.account_id || '');
                    const amount = this.toNumber(this.draftPayment.amount);
                    const chequeNumber = String(this.draftPayment.cheque_number || '').trim();

                    if (!accountId || amount <= 0) {
                        this.message = 'Select payment account and amount.';

                        return;
                    }

                    const nextPaymentTotal = this.paymentTotal() + amount;
                    if (nextPaymentTotal > this.grandTotal()) {
                        this.message = 'Payment cannot exceed bill total.';

                        return;
                    }

                    this.payments.push({
                        account_id: accountId,
                        amount,
                        cheque_number: chequeNumber,
                    });

                    this.draftPayment = {
                        account_id: this.defaultCashAccountId,
                        amount: '',
                        cheque_number: '',
                    };

                    this.focus('paymentAccount');
                },
                removePayment(index) {
                    this.payments.splice(index, 1);
                },
                grandTotal() {
                    return this.items.reduce((sum, item) => sum + this.toNumber(item.total), 0);
                },
                paymentTotal() {
                    return this.payments.reduce((sum, payment) => sum + this.toNumber(payment.amount), 0);
                },
                dueTotal() {
                    return this.grandTotal() - this.paymentTotal();
                },
                accountName(accountId) {
                    return this.accountLookup[accountId] || 'Unknown account';
                },
                validateBeforeSubmit(event) {
                    this.message = '';

                    if (!this.partyId) {
                        event.preventDefault();
                        this.message = 'Party is required.';
                        this.focus('party');

                        return;
                    }

                    if (this.items.length === 0) {
                        event.preventDefault();
                        this.message = 'Add at least one item before saving.';
                        this.focus('itemParticular');

                        return;
                    }

                    if (this.paymentTotal() > this.grandTotal()) {
                        event.preventDefault();
                        this.message = 'Payment total cannot exceed bill total.';
                    }
                },
            };
        }
    </script>
@endsection




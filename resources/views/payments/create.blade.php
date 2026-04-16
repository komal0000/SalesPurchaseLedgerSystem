@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Create Payment</h1>
        <form action="{{ route('payments.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="flex items-center justify-between">
                        <label for="payment-party-select" class="block text-sm font-medium text-gray-700">Party</label>
                        <button type="button" data-open-quick-party-entry data-party-select-id="payment-party-select" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">+ Quick Add</button>
                    </div>
                    <select id="payment-party-select" name="party_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="">Select a party</option>
                        @foreach ($parties as $party)
                            <option value="{{ $party->id }}" @selected($selectedPartyId === $party->id)>{{ $party->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="account_id" class="block text-sm font-medium text-gray-700">Account</label>
                    <select id="account_id" name="account_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected($selectedAccountId === $account->id)>{{ $account->name }} ({{ ucfirst($account->type) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                    <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label for="payment_kind" class="block text-sm font-medium text-gray-700">Payment Kind</label>
                    <select id="payment_kind" name="payment_kind" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="receivable" @selected(($selectedPaymentKind ?? 'advance') === 'receivable')>Receivable</option>
                        <option value="payable" @selected(($selectedPaymentKind ?? 'advance') === 'payable')>Payable</option>
                        <option value="advance" @selected(($selectedPaymentKind ?? 'advance') === 'advance')>Advance</option>
                    </select>
                </div>
                <div id="advance_direction_container">
                    <label for="advance_direction" class="block text-sm font-medium text-gray-700">Advance Direction</label>
                    <select id="advance_direction" name="advance_direction" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="paid" @selected(old('advance_direction', $selectedAdvanceDirection ?? 'paid') === 'paid')>Advance Paid</option>
                        <option value="received" @selected(old('advance_direction', $selectedAdvanceDirection ?? 'paid') === 'received')>Advance Received</option>
                    </select>
                </div>
                <div>
                    <label for="cheque_number" class="block text-sm font-medium text-gray-700">Cheque Number</label>
                    <input id="cheque_number" name="cheque_number" type="text" maxlength="50" value="{{ old('cheque_number') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="Optional">
                </div>
            </div>
            <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">Select payment kind manually. Use Payable for outgoing payment, Receivable for incoming payment. For Advance, choose Advance Paid or Advance Received.</div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('payments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Payment</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const kindSelect = document.getElementById('payment_kind');
            const advanceDirectionContainer = document.getElementById('advance_direction_container');
            const advanceDirectionSelect = document.getElementById('advance_direction');

            const syncAdvanceDirection = () => {
                const isAdvance = kindSelect?.value === 'advance';

                if (advanceDirectionContainer) {
                    advanceDirectionContainer.classList.toggle('hidden', !isAdvance);
                }

                if (advanceDirectionSelect) {
                    advanceDirectionSelect.required = !!isAdvance;
                }
            };

            kindSelect?.addEventListener('change', syncAdvanceDirection);
            syncAdvanceDirection();
        })();
    </script>
@endsection

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
                    <label for="cheque_number" class="block text-sm font-medium text-gray-700">Cheque Number</label>
                    <input id="cheque_number" name="cheque_number" type="text" maxlength="50" value="{{ old('cheque_number') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="Optional">
                </div>
                <div>
                    <label for="sale_id" class="block text-sm font-medium text-gray-700">Linked Sale</label>
                    <select id="sale_id" name="sale_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="">None</option>
                        @foreach ($sales as $sale)
                            <option value="{{ $sale->id }}" @selected($selectedSaleId === $sale->id)>{{ $sale->party->name }} / {{ number_format($sale->total, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="purchase_id" class="block text-sm font-medium text-gray-700">Linked Purchase</label>
                    <select id="purchase_id" name="purchase_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="">None</option>
                        @foreach ($purchases as $purchase)
                            <option value="{{ $purchase->id }}" @selected($selectedPurchaseId === $purchase->id)>{{ $purchase->party->name }} / {{ number_format($purchase->total, 2) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">Link either a sale or a purchase. Payment type is auto-calculated from the selected bill. If neither is selected, it is saved as received.</div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('payments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Payment</button>
            </div>
        </form>
    </div>
@endsection

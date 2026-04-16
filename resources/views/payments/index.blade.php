@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">Payments</h1>
                <p class="text-sm text-gray-500">Search payments first to load results quickly for large datasets.</p>
            </div>
            <a href="{{ route('payments.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">New Payment</a>
        </div>

        <form method="GET" action="{{ route('payments.index') }}" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-7 md:items-end">
            <div>
                <div class="flex items-center justify-between">
                    <label for="payments-filter-party-select" class="block text-sm font-medium text-gray-700">Party</label>
                    <button type="button" data-open-quick-party-entry data-party-select-id="payments-filter-party-select" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">+ Quick Add</button>
                </div>
                <select id="payments-filter-party-select" name="party_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">All parties</option>
                    @foreach ($parties as $party)
                        <option value="{{ $party->id }}" @selected(($filters['party_id'] ?? null) === $party->id)>{{ $party->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Account</label>
                <select name="account_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">All accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['account_id'] ?? null) === $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Payment Kind</label>
                <select name="payment_kind" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="receivable" @selected(($filters['payment_kind'] ?? null) === 'receivable')>Receivable</option>
                    <option value="payable" @selected(($filters['payment_kind'] ?? null) === 'payable')>Payable</option>
                    <option value="advance" @selected(($filters['payment_kind'] ?? null) === 'advance')>Advance</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Advance Direction</label>
                <select name="advance_direction" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="paid" @selected(($filters['advance_direction'] ?? null) === 'paid')>Advance Paid</option>
                    <option value="received" @selected(($filters['advance_direction'] ?? null) === 'received')>Advance Received</option>
                </select>
            </div>
            @include('partials.bs-date-selector', ['name' => 'from_date_bs', 'label' => 'From BS Date', 'value' => $filters['from_date_bs'] ?? null])
            @include('partials.bs-date-selector', ['name' => 'to_date_bs', 'label' => 'To BS Date', 'value' => $filters['to_date_bs'] ?? null])
            <div>
                <label class="block text-sm font-medium text-gray-700">Keyword</label>
                <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Cheque / party / account" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            </div>
            <div class="md:col-span-6 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Search</button>
                <a href="{{ route('payments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>

        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-4 text-left">Party</th>
                            <th class="px-5 py-4 text-right">Amount</th>
                            <th class="px-5 py-4 text-left">Kind</th>
                            <th class="px-5 py-4 text-left">Account</th>
                            <th class="px-5 py-4 text-left">Cheque Number</th>
                            <th class="px-5 py-4 text-left">Linked Bill</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            @php
                                $paymentKind = $payment->resolvedPaymentKind();
                                $advanceDirection = $payment->resolvedAdvanceDirection();
                                $kindBadge = $paymentKind === 'receivable'
                                    ? 'bg-green-100 text-green-700'
                                    : ($paymentKind === 'payable' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                $kindLabel = ucfirst($paymentKind);
                                if ($paymentKind === 'advance' && $advanceDirection) {
                                    $kindLabel = 'Advance ' . ucfirst($advanceDirection);
                                }
                            @endphp
                            <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-medium text-gray-900">
                                    <a href="{{ route('payments.show', $payment) }}" class="hover:text-indigo-600">{{ $payment->party->name }}</a>
                                </td>
                                <td class="px-5 py-4 text-right font-mono font-semibold text-indigo-700">{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $kindBadge }}">
                                        {{ $kindLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-500">{{ $payment->account->name }}</td>
                                <td class="px-5 py-4 text-gray-500">{{ $payment->cheque_number ?: '-' }}</td>
                                <td class="px-5 py-4 text-gray-500">
                                    @if ($payment->sale)
                                        Sale / {{ number_format($payment->sale->total, 2) }}
                                    @elseif ($payment->purchase)
                                        Purchase / {{ number_format($payment->purchase->total, 2) }}
                                    @else
                                        {{ $paymentKind === 'advance' ? 'Advance' : 'Direct Entry' }}
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('payments.show', $payment) }}" class="text-sm text-indigo-600 hover:text-indigo-700">View</a>
                                        <form action="{{ route('payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Delete this payment? This will reverse the ledger entries.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-500 hover:text-red-700">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-500">
                                    {{ $hasSearched ? 'No payments found.' : 'Use filters and click Search to load payments.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($payments as $payment)
                @php
                    $paymentKind = $payment->resolvedPaymentKind();
                    $advanceDirection = $payment->resolvedAdvanceDirection();
                    $kindBadge = $paymentKind === 'receivable'
                        ? 'bg-green-100 text-green-700'
                        : ($paymentKind === 'payable' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                    $kindLabel = ucfirst($paymentKind);
                    if ($paymentKind === 'advance' && $advanceDirection) {
                        $kindLabel = 'Advance ' . ucfirst($advanceDirection);
                    }
                @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('payments.show', $payment) }}" class="font-semibold text-gray-900">{{ $payment->party->name }}</a>
                            <p class="mt-1 text-sm text-gray-500">{{ $payment->account->name }}</p>
                            <p class="text-xs text-gray-500">Cheque: {{ $payment->cheque_number ?: '-' }}</p>
                        </div>
                        <span class="font-mono text-sm font-semibold text-indigo-700">{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $kindBadge }}">
                            {{ $kindLabel }}
                        </span>
                        <span class="text-xs text-gray-500">
                            @if ($payment->sale)
                                Sale Linked
                            @elseif ($payment->purchase)
                                Purchase Linked
                            @else
                                {{ $paymentKind === 'advance' ? 'Advance' : 'Direct Entry' }}
                            @endif
                        </span>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                        <a href="{{ route('payments.show', $payment) }}" class="text-sm font-medium text-indigo-600">View Detail</a>
                        <form action="{{ route('payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Delete this payment? This will reverse the ledger entries.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-500">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                    {{ $hasSearched ? 'No payments found.' : 'Use filters and tap Search to load payments.' }}
                </div>
            @endforelse
        </div>

        @if ($hasSearched)
            {{ $payments->links() }}
        @endif
    </div>
@endsection

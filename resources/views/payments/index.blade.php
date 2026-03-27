@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">Payments</h1>
                <p class="text-sm text-gray-500">Deleting a payment creates reverse ledger entries before removing the payment row.</p>
            </div>
            <a href="{{ route('payments.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">New Payment</a>
        </div>

        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-4 text-left">Party</th>
                            <th class="px-5 py-4 text-right">Amount</th>
                            <th class="px-5 py-4 text-left">Type</th>
                            <th class="px-5 py-4 text-left">Account</th>
                            <th class="px-5 py-4 text-left">Linked Bill</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-medium text-gray-900">
                                    <a href="{{ route('payments.show', $payment) }}" class="hover:text-indigo-600">{{ $payment->party->name }}</a>
                                </td>
                                <td class="px-5 py-4 text-right font-mono font-semibold text-indigo-700">{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->type === 'received' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($payment->type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-500">{{ $payment->account->name }}</td>
                                <td class="px-5 py-4 text-gray-500">
                                    @if ($payment->sale)
                                        Sale / {{ number_format($payment->sale->total, 2) }}
                                    @elseif ($payment->purchase)
                                        Purchase / {{ number_format($payment->purchase->total, 2) }}
                                    @else
                                        Advance
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
                                <td colspan="6" class="px-5 py-12 text-center text-gray-500">No payments created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($payments as $payment)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('payments.show', $payment) }}" class="font-semibold text-gray-900">{{ $payment->party->name }}</a>
                            <p class="mt-1 text-sm text-gray-500">{{ $payment->account->name }}</p>
                        </div>
                        <span class="font-mono text-sm font-semibold text-indigo-700">{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->type === 'received' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($payment->type) }}
                        </span>
                        <span class="text-xs text-gray-500">
                            @if ($payment->sale)
                                Sale Linked
                            @elseif ($payment->purchase)
                                Purchase Linked
                            @else
                                Advance
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
                    No payments created yet.
                </div>
            @endforelse
        </div>

        {{ $payments->links() }}
    </div>
@endsection

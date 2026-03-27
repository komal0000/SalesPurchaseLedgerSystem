@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $account->name }} Ledger</h1>
                <p class="text-sm text-gray-500">Every cash and bank movement is calculated from ledger rows.</p>
            </div>
            <a href="{{ route('accounts.show', $account) }}" class="text-sm text-indigo-600 hover:text-indigo-700">Back to account</a>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[720px] text-sm font-mono">
                <thead class="bg-gray-100 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Reference</th>
                        <th class="px-4 py-3 text-right text-blue-600">DR</th>
                        <th class="px-4 py-3 text-right text-orange-500">CR</th>
                        <th class="px-4 py-3 text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $running = 0; @endphp
                    @forelse ($ledgerRows as $row)
                        @php $running += ((float) $row->dr_amount - (float) $row->cr_amount); @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 text-gray-500">{{ $row->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 capitalize">{{ $row->type }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $row->ref_table }} / {{ $row->ref_id }}</td>
                            <td class="px-4 py-3 text-right text-blue-600">{{ $row->dr_amount > 0 ? number_format($row->dr_amount, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right text-orange-500">{{ $row->cr_amount > 0 ? number_format($row->cr_amount, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $running >= 0 ? 'text-green-600' : 'text-red-500' }}">{{ number_format(abs($running), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center font-sans text-gray-500">No ledger rows found for this account.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection




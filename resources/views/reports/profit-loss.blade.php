@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Profit / Loss</h1>
            <p class="text-sm text-gray-500">Summary for a selected date range.</p>
        </div>

        <form method="GET" action="{{ route('reports.profit-loss') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    @include('partials.bs-date-selector', ['name' => 'from_date_bs', 'label' => 'From BS Date', 'value' => $filters['from_date_bs'] ?? null])
                </div>
                <div>
                    @include('partials.bs-date-selector', ['name' => 'to_date_bs', 'label' => 'To BS Date', 'value' => $filters['to_date_bs'] ?? null])
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Apply</button>
                    <a href="{{ route('reports.profit-loss') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Sales</p>
                <p class="mt-2 font-mono text-2xl font-semibold text-green-600">{{ number_format($salesTotal, 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Purchases</p>
                <p class="mt-2 font-mono text-2xl font-semibold text-red-500">{{ number_format($purchaseTotal, 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Result</p>
                <p class="mt-2 font-mono text-2xl font-semibold {{ $profitLoss >= 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ number_format(abs($profitLoss), 2) }}
                </p>
                <p class="mt-1 text-xs font-semibold {{ $profitLoss >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ $profitLoss >= 0 ? 'Profit' : 'Loss' }}
                </p>
            </div>
        </div>
    </div>
@endsection

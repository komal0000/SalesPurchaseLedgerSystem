@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $account->name }}</h1>
                    <p class="mt-1 text-sm capitalize text-gray-500">Type: {{ $account->type }}</p>
                </div>
                @include('partials.balance-badge', ['balance' => $balance])
            </div>
            <div class="mt-6 rounded-lg bg-gray-50 p-4">
                <p class="text-sm text-gray-500">Payments Recorded</p>
                <p class="mt-1 text-xl font-semibold">{{ $account->payments_count }}</p>
            </div>
            <div class="mt-6">
                <a href="{{ route('accounts.ledger', $account) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">View Ledger Statement</a>
            </div>
        </div>
    </div>
@endsection


@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Edit Party</h1>

        <form method="POST" action="{{ route('parties.update', $party) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $party->name) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $party->phone) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" placeholder="Optional">
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                <input id="address" name="address" type="text" value="{{ old('address', $party->address) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Optional">
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-gray-700">Opening Balance</label>
                    <input id="opening_balance" name="opening_balance" type="number" min="0" step="0.01" value="{{ old('opening_balance', $party->opening_balance ?? 0) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label for="opening_balance_side" class="block text-sm font-medium text-gray-700">Side</label>
                    <select id="opening_balance_side" name="opening_balance_side" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="dr" @selected(old('opening_balance_side', $party->opening_balance_side ?? 'dr') === 'dr')>Receivable</option>
                        <option value="cr" @selected(old('opening_balance_side', $party->opening_balance_side ?? 'dr') === 'cr')>Payable</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('parties.show', $party) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Update Party</button>
            </div>
        </form>
    </div>
@endsection

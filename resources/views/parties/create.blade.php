@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Create Party</h1>
        <form action="{{ route('parties.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-gray-700">Opening Balance</label>
                    <input id="opening_balance" name="opening_balance" type="number" min="0" step="0.01" value="{{ old('opening_balance', '0') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label for="opening_balance_side" class="block text-sm font-medium text-gray-700">Side</label>
                    <select id="opening_balance_side" name="opening_balance_side" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        <option value="dr" @selected(old('opening_balance_side', 'dr') === 'dr')>DR</option>
                        <option value="cr" @selected(old('opening_balance_side') === 'cr')>CR</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('parties.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Party</button>
            </div>
        </form>
    </div>
@endsection



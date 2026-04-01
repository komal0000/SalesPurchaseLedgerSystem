@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Create Employee</h1>

        <form method="POST" action="{{ route('employees.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="party_id" class="block text-sm font-medium text-gray-700">Party</label>
                <select id="party_id" name="party_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">Select party (employee)</option>
                    @foreach ($parties as $party)
                        <option value="{{ $party->id }}" @selected(old('party_id') === $party->id)>
                            {{ $party->name }}{{ $party->phone ? ' • ' . $party->phone : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="salary" class="block text-sm font-medium text-gray-700">Base Salary</label>
                <input id="salary" name="salary" type="number" step="0.01" min="0" value="{{ old('salary', '0') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('employees.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Employee</button>
            </div>
        </form>
    </div>
@endsection

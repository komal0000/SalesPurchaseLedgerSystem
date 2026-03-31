@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">New Employee Salary</h1>

        <form method="POST" action="{{ route('employee-salaries.store') }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="employee_name" class="block text-sm font-medium text-gray-700">Employee Name</label>
                    <input id="employee_name" name="employee_name" type="text" value="{{ old('employee_name') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label for="employee_code" class="block text-sm font-medium text-gray-700">Employee Code</label>
                    <input id="employee_code" name="employee_code" type="text" value="{{ old('employee_code') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    @include('partials.bs-date-selector', ['name' => 'salary_date_bs', 'label' => 'Salary Date (BS)', 'value' => old('salary_date_bs', $todayBs)])
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-600">
                    Salary month is auto-calculated from selected BS date.
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="basic_salary" class="block text-sm font-medium text-gray-700">Basic Salary</label>
                    <input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0" value="{{ old('basic_salary', '0') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label for="allowance" class="block text-sm font-medium text-gray-700">Allowance</label>
                    <input id="allowance" name="allowance" type="number" step="0.01" min="0" value="{{ old('allowance', '0') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label for="deduction" class="block text-sm font-medium text-gray-700">Deduction</label>
                    <input id="deduction" name="deduction" type="number" step="0.01" min="0" value="{{ old('deduction', '0') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </div>
            </div>

            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">{{ old('remarks') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('employee-salaries.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Salary</button>
            </div>
        </form>
    </div>
@endsection

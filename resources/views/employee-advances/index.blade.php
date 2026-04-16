@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">Employee Salary Advances</h1>
                <p class="text-sm text-gray-500">Record individual salary advance before month end. Amount is deducted from month-end salary sheet.</p>
            </div>
            <a href="{{ route('employee-salaries.create') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Go To Salary Sheet</a>
        </div>

        <form method="GET" action="{{ route('employee-advances.index') }}" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_minmax(0,220px)_auto] md:items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Employee</label>
                <select id="employee-advance-filter-employee" name="employee_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                    <option value="">All employees</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $employee->id)>
                            {{ $employee->party?->name ?? '-' }}
                            @if ($employee->party?->phone)
                                ({{ $employee->party->phone }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="salary_month_bs" class="block text-sm font-medium text-gray-700">Salary Month (BS)</label>
                <input id="salary_month_bs" name="salary_month_bs" type="text" value="{{ $filters['salary_month_bs'] }}" placeholder="YYYY-MM" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            </div>
            <div class="flex items-center gap-3 md:pb-0.5">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Search</button>
                <a href="{{ route('employee-advances.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>

        <form method="POST" action="{{ route('employee-advances.store') }}" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-2">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Employee</label>
                <select id="employee-advance-create-employee" name="employee_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" required>
                    <option value="">Select employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) old('employee_id', $filters['employee_id'] ?? '') === (string) $employee->id)>
                            {{ $employee->party?->name ?? '-' }}
                            @if ($employee->party?->phone)
                                ({{ $employee->party->phone }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Account</label>
                <select name="account_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" required>
                    <option value="">Select account</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('account_id', $selectedAccountId) === (string) $account->id)>
                            {{ ucfirst($account->type) }} - {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                @include('partials.bs-date-selector', ['name' => 'advance_date_bs', 'label' => 'Advance Date (BS)', 'value' => $defaultAdvanceDateBs])
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Advance Amount</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" required>
            </div>
            <div class="md:col-span-2">
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="remarks" name="remarks" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Optional note">{{ old('remarks') }}</textarea>
            </div>
            <div class="md:col-span-2 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Monthly cap rule: total employee advances in a salary month cannot exceed that employee base salary.
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Advance</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-gray-100 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Employee</th>
                            <th class="px-4 py-3 text-left">Month</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-left">Account</th>
                            <th class="px-4 py-3 text-left">Payment</th>
                            <th class="px-4 py-3 text-left">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($advances as $advance)
                            <tr class="border-t border-gray-100 hover:bg-gray-50/70">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ $advance->employee?->party?->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $advance->employee?->party?->phone ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $advance->salary_month }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $advance->advance_date_bs }} ({{ $advance->advance_date?->format('d M Y') }})</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-indigo-700">{{ number_format((float) $advance->amount, 2) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $advance->account?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($advance->payment)
                                        <a href="{{ route('payments.show', $advance->payment) }}" class="text-indigo-600 hover:text-indigo-700">#{{ $advance->payment->id }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $advance->remarks ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                    {{ $hasSearched ? 'No advances found for selected filters.' : 'Use filters and click Search to load employee advances.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($hasSearched)
            {{ $advances->links() }}
        @endif
    </div>
@endsection

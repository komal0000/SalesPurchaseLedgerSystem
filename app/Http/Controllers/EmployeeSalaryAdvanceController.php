<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeSalaryAdvance;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeSalaryAdvanceController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'salary_month_bs' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $todayBs = DateHelper::getCurrentBS();
        $selectedSalaryMonth = $filters['salary_month_bs'] ?? substr($todayBs, 0, 7);
        $hasSearched = filled($filters['employee_id'] ?? null) || filled($filters['salary_month_bs'] ?? null);

        $employees = Employee::query()
            ->select('employees.*')
            ->join('parties', 'parties.id', '=', 'employees.party_id')
            ->with('party:id,name,phone')
            ->orderBy('parties.name')
            ->get();

        $accounts = Account::query()
            ->orderByRaw("case when type = 'cash' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        if ($hasSearched) {
            $advances = EmployeeSalaryAdvance::query()
                ->with(['employee.party', 'account', 'payment'])
                ->where('salary_month', $selectedSalaryMonth)
                ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
                ->orderByDesc('advance_date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        } else {
            $advances = new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 20,
                currentPage: 1,
                options: ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('employee-advances.index', [
            'employees' => $employees,
            'accounts' => $accounts,
            'advances' => $advances,
            'filters' => [
                'employee_id' => $filters['employee_id'] ?? null,
                'salary_month_bs' => $selectedSalaryMonth,
            ],
            'selectedAccountId' => old('account_id', $accounts->firstWhere('type', 'cash')?->id),
            'defaultAdvanceDateBs' => old('advance_date_bs', $todayBs),
            'hasSearched' => $hasSearched,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'advance_date_bs' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $advanceDateAd = DateHelper::bsToAd($validated['advance_date_bs']);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'advance_date_bs' => $exception->getMessage(),
            ]);
        }

        $salaryMonth = substr($validated['advance_date_bs'], 0, 7);

        $employee = Employee::query()
            ->with('party:id,name')
            ->findOrFail($validated['employee_id']);

        if (!$employee->party_id) {
            throw ValidationException::withMessages([
                'employee_id' => 'Selected employee is not linked to any party.',
            ]);
        }

        $totalAdvancedThisMonth = (float) EmployeeSalaryAdvance::query()
            ->where('employee_id', $employee->id)
            ->where('salary_month', $salaryMonth)
            ->sum('amount');

        $maxAllowed = (float) $employee->salary;
        $requested = (float) $validated['amount'];

        if (($totalAdvancedThisMonth + $requested) > $maxAllowed) {
            $remaining = max(0, $maxAllowed - $totalAdvancedThisMonth);

            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Advance exceeds employee monthly salary cap. Remaining allowed for %s is %.2f.',
                    $salaryMonth,
                    $remaining
                ),
            ]);
        }

        DB::transaction(function () use ($validated, $employee, $advanceDateAd, $salaryMonth): void {
            $payment = $this->paymentService->create([
                'party_id' => $employee->party_id,
                'amount' => $validated['amount'],
                'type' => 'given',
                'payment_kind' => 'advance',
                'advance_direction' => 'paid',
                'account_id' => $validated['account_id'],
                'cheque_number' => null,
                'sale_id' => null,
                'purchase_id' => null,
            ]);

            EmployeeSalaryAdvance::query()->create([
                'employee_id' => $employee->id,
                'party_id' => $employee->party_id,
                'account_id' => $validated['account_id'],
                'payment_id' => $payment->id,
                'amount' => $validated['amount'],
                'advance_date' => $advanceDateAd,
                'advance_date_bs' => $validated['advance_date_bs'],
                'salary_month' => $salaryMonth,
                'remarks' => $validated['remarks'] ?? null,
            ]);
        });

        return redirect()
            ->route('employee-advances.index', [
                'employee_id' => $employee->id,
                'salary_month_bs' => $salaryMonth,
            ])
            ->with('success', 'Employee salary advance saved and payment posted successfully.');
    }
}

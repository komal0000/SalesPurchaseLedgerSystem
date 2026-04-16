<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Http\Requests\StoreEmployeeSalaryRequest;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLeaveOvertime;
use App\Models\EmployeeSalaryAdvance;
use App\Models\EmployeeSalary;
use App\Models\PayrollSetting;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class EmployeeSalaryController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'from_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'to_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        $employees = Employee::query()
            ->select('employees.*')
            ->join('parties', 'parties.id', '=', 'employees.party_id')
            ->with('party:id,name,phone')
            ->orderBy('parties.name')
            ->get();

        try {
            [$fromAd, $toAd] = DateHelper::getAdRangeFromBsFilters($filters['from_date_bs'] ?? null, $filters['to_date_bs'] ?? null);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'from_date_bs' => $exception->getMessage(),
                'to_date_bs' => $exception->getMessage(),
            ]);
        }

        $hasSearched = filled($filters['employee_id'] ?? null)
            || filled($filters['from_date_bs'] ?? null)
            || filled($filters['to_date_bs'] ?? null);

        if ($hasSearched) {
            $rows = EmployeeSalary::query()
                ->with('party')
                ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
                ->when($fromAd, fn ($query) => $query->whereDate('salary_date', '>=', $fromAd))
                ->when($toAd, fn ($query) => $query->whereDate('salary_date', '<=', $toAd))
                ->orderByDesc('salary_date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            $rows->through(function (EmployeeSalary $salary) {
                $salary->salary_date_bs = DateHelper::adToBs($salary->salary_date);

                return $salary;
            });
        } else {
            $rows = new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 20,
                currentPage: 1,
                options: ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('employee-salaries.index', [
            'salaries' => $rows,
            'employees' => $employees,
            'filters' => [
                'employee_id' => $filters['employee_id'] ?? null,
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
            'hasSearched' => $hasSearched,
        ]);
    }

    public function create(Request $request): View
    {
        $filters = $request->validate([
            'salary_month_bs' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $salaryMonthBs = $filters['salary_month_bs'] ?? substr(DateHelper::getCurrentBS(), 0, 7);

        try {
            [$bsYear, $bsMonth] = $this->parseBsMonth($salaryMonthBs);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'salary_month_bs' => $exception->getMessage(),
            ]);
        }

        $employees = Employee::query()
            ->select('employees.*')
            ->join('parties', 'parties.id', '=', 'employees.party_id')
            ->with('party')
            ->orderBy('parties.name')
            ->get();

        $leaveRows = EmployeeLeaveOvertime::query()
            ->where('bs_year', $bsYear)
            ->where('bs_month', $bsMonth)
            ->get()
            ->keyBy('employee_id');

        $accounts = Account::query()
            ->orderByRaw("case when type = 'cash' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        $payrollSetting = PayrollSetting::query()->firstOrCreate([], [
            'leave_fine_per_day' => 0,
            'overtime_money_per_day' => 0,
        ]);

        $advanceTotals = EmployeeSalaryAdvance::query()
            ->where('salary_month', $salaryMonthBs)
            ->selectRaw('employee_id, COALESCE(SUM(amount), 0) as total_advance')
            ->groupBy('employee_id')
            ->pluck('total_advance', 'employee_id')
            ->all();

        $salaryDateBs = DateHelper::formattedDate($bsYear, $bsMonth, DateHelper::getDaysInMonth($bsYear, $bsMonth));

        return view('employee-salaries.create', [
            'salaryMonthBs' => $salaryMonthBs,
            'salaryDateBs' => $salaryDateBs,
            'sheetRows' => $this->buildSheetRows($employees, $leaveRows, $advanceTotals, (float) $payrollSetting->leave_fine_per_day, (float) $payrollSetting->overtime_money_per_day),
            'accounts' => $accounts,
            'selectedAccountId' => old('account_id', $accounts->firstWhere('type', 'cash')?->id),
            'payrollSetting' => $payrollSetting,
        ]);
    }

    public function store(StoreEmployeeSalaryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            [$bsYear, $bsMonth] = $this->parseBsMonth($validated['salary_month_bs']);
            $salaryDateAd = DateHelper::bsToAd($validated['salary_date_bs']);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'salary_month_bs' => $exception->getMessage(),
                'salary_date_bs' => $exception->getMessage(),
            ]);
        }

        if (!str_starts_with($validated['salary_date_bs'], $validated['salary_month_bs'] . '-')) {
            throw ValidationException::withMessages([
                'salary_date_bs' => 'Salary date must be inside the selected BS month.',
            ]);
        }

        $selectedSalaryDay = (int) substr($validated['salary_date_bs'], -2);
        $lastDayOfMonth = DateHelper::getDaysInMonth($bsYear, $bsMonth);

        if ($selectedSalaryDay !== $lastDayOfMonth) {
            throw ValidationException::withMessages([
                'salary_date_bs' => sprintf('Salary date must be the last BS day of selected month (%02d).', $lastDayOfMonth),
            ]);
        }

        $saveAsExpense = (bool) ($validated['save_as_expense'] ?? true);

        $advanceTotals = EmployeeSalaryAdvance::query()
            ->where('salary_month', $validated['salary_month_bs'])
            ->selectRaw('employee_id, COALESCE(SUM(amount), 0) as total_advance')
            ->groupBy('employee_id')
            ->pluck('total_advance', 'employee_id')
            ->all();

        $payrollSetting = PayrollSetting::query()->firstOrCreate([], [
            'leave_fine_per_day' => 0,
            'overtime_money_per_day' => 0,
        ]);

        $employees = Employee::query()
            ->select('employees.*')
            ->join('parties', 'parties.id', '=', 'employees.party_id')
            ->with('party')
            ->orderBy('parties.name')
            ->get();

        if ($employees->isEmpty()) {
            throw ValidationException::withMessages([
                'salary_month_bs' => 'Please create employees before saving a salary sheet.',
            ]);
        }

        if ($saveAsExpense) {
            $alreadyPosted = EmployeeSalary::query()
                ->where('salary_month', $validated['salary_month_bs'])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->whereNotNull('expense_payment_id')
                ->pluck('employee_name')
                ->all();

            if (!empty($alreadyPosted)) {
                throw ValidationException::withMessages([
                    'salary_month_bs' => 'Expense already posted for this month: ' . implode(', ', $alreadyPosted),
                ]);
            }
        }

        $result = DB::transaction(function () use ($validated, $salaryDateAd, $bsYear, $bsMonth, $employees, $saveAsExpense, $advanceTotals, $payrollSetting) {
            $savedSalaries = collect();
            $totalNet = 0.0;

            foreach ($employees as $employee) {
                $leaveDays = (float) ($validated['leaves'][$employee->id] ?? 0);
                $overtimeDays = (float) ($validated['overtimes'][$employee->id] ?? 0);
                $advanceDeduction = (float) ($advanceTotals[$employee->id] ?? 0);

                EmployeeLeaveOvertime::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'bs_year' => $bsYear,
                        'bs_month' => $bsMonth,
                    ],
                    [
                        'leave_days' => $leaveDays,
                        'overtime_days' => $overtimeDays,
                    ]
                );

                $amounts = $this->calculateSalary(
                    (float) $employee->salary,
                    $leaveDays,
                    $overtimeDays,
                    (float) $payrollSetting->leave_fine_per_day,
                    (float) $payrollSetting->overtime_money_per_day,
                    $advanceDeduction,
                );

                $salary = EmployeeSalary::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'salary_month' => $validated['salary_month_bs'],
                    ],
                    [
                        'employee_name' => $employee->party?->name,
                        'employee_code' => $employee->party?->phone,
                        'party_id' => $employee->party_id,
                        'account_id' => $validated['account_id'],
                        'salary_date' => $salaryDateAd,
                        'basic_salary' => $amounts['basic_salary'],
                        'allowance' => $amounts['allowance'],
                        'deduction' => $amounts['deduction'],
                        'advance_deduction_amount' => $amounts['advance_deduction'],
                        'leave_days' => $leaveDays,
                        'overtime_days' => $overtimeDays,
                        'net_salary' => $amounts['net_salary'],
                        'remarks' => $validated['remarks'] ?? null,
                    ]
                );

                if ($saveAsExpense) {
                    $payment = $this->paymentService->create([
                        'party_id' => $employee->party_id,
                        'amount' => $amounts['net_salary'],
                        'type' => 'given',
                        'payment_kind' => 'payable',
                        'advance_direction' => null,
                        'account_id' => $validated['account_id'],
                        'cheque_number' => null,
                        'sale_id' => null,
                        'purchase_id' => null,
                    ]);

                    $salary->update([
                        'expense_payment_id' => $payment->id,
                        'expense_saved_at' => now(),
                    ]);
                }

                $savedSalaries->push($salary);
                $totalNet += (float) $amounts['net_salary'];
            }

            return [
                'firstSalary' => $savedSalaries->first(),
                'savedCount' => $savedSalaries->count(),
                'totalNet' => $totalNet,
            ];
        });

        $success = $saveAsExpense
            ? sprintf('Salary sheet saved for %d employees and posted as expense. Total %.2f.', $result['savedCount'], $result['totalNet'])
            : sprintf('Salary sheet saved for %d employees. Total %.2f.', $result['savedCount'], $result['totalNet']);

        return redirect()
            ->route('employee-salaries.show', ['employee_salary' => $result['firstSalary']])
            ->with('success', $success);
    }

    public function show(EmployeeSalary $employeeSalary): View
    {
        $employeeSalary->load(['employee', 'party', 'account', 'expensePayment.account']);
        $employeeSalary->salary_date_bs = DateHelper::adToBs($employeeSalary->salary_date);

        return view('employee-salaries.show', [
            'salary' => $employeeSalary,
        ]);
    }

    public function print(EmployeeSalary $employeeSalary): View
    {
        $employeeSalary->load(['employee', 'party', 'account', 'expensePayment.account']);
        $employeeSalary->salary_date_bs = DateHelper::adToBs($employeeSalary->salary_date);

        return view('employee-salaries.print', [
            'salary' => $employeeSalary,
        ]);
    }

    private function parseBsMonth(string $salaryMonthBs): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $salaryMonthBs)) {
            throw new InvalidArgumentException('Salary month must be in YYYY-MM format.');
        }

        [$year, $month] = array_map('intval', explode('-', $salaryMonthBs));

        if ($year < DateHelper::MIN_YEAR_BS || $year > DateHelper::MAX_YEAR_BS) {
            throw new InvalidArgumentException('Salary month year is out of supported BS range.');
        }

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Salary month must be between 01 and 12.');
        }

        return [$year, $month];
    }

    private function buildSheetRows(Collection $employees, Collection $leaveRows, array $advanceTotals, float $leaveFinePerDay, float $overtimeMoneyPerDay): Collection
    {
        return $employees->map(function (Employee $employee) use ($leaveRows, $advanceTotals, $leaveFinePerDay, $overtimeMoneyPerDay) {
            $leaveDays = (float) ($leaveRows->get($employee->id)?->leave_days ?? 0);
            $overtimeDays = (float) ($leaveRows->get($employee->id)?->overtime_days ?? 0);
            $advanceDeduction = (float) ($advanceTotals[$employee->id] ?? 0);
            $amounts = $this->calculateSalary((float) $employee->salary, $leaveDays, $overtimeDays, $leaveFinePerDay, $overtimeMoneyPerDay, $advanceDeduction);

            return [
                'employee' => $employee,
                'leave_days' => $leaveDays,
                'overtime_days' => $overtimeDays,
                'advance_deduction' => $amounts['advance_deduction'],
                'daily_rate' => $amounts['daily_rate'],
                'leave_fine_per_day' => $leaveFinePerDay,
                'overtime_money_per_day' => $overtimeMoneyPerDay,
                'basic_salary' => $amounts['basic_salary'],
                'allowance' => $amounts['allowance'],
                'leave_deduction' => $amounts['leave_deduction'],
                'deduction' => $amounts['deduction'],
                'net_salary' => $amounts['net_salary'],
            ];
        });
    }

    private function calculateSalary(float $basicSalary, float $leaveDays, float $overtimeDays, float $leaveFinePerDay, float $overtimeMoneyPerDay, float $advanceDeduction = 0): array
    {
        $dailyRate = round($basicSalary / 30, 2);
        $allowance = round($overtimeMoneyPerDay * $overtimeDays, 2);
        $leaveDeduction = round($leaveFinePerDay * $leaveDays, 2);
        $deduction = round($leaveDeduction + $advanceDeduction, 2);

        return [
            'daily_rate' => $dailyRate,
            'basic_salary' => $basicSalary,
            'allowance' => $allowance,
            'leave_deduction' => $leaveDeduction,
            'advance_deduction' => round($advanceDeduction, 2),
            'deduction' => $deduction,
            'net_salary' => round($basicSalary + $allowance - $deduction, 2),
        ];
    }
}

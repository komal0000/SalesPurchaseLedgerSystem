<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\EmployeeSalary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'employee_name' => ['nullable', 'string', 'max:255'],
            'from_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'to_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        try {
            [$fromAd, $toAd] = DateHelper::getAdRangeFromBsFilters($filters['from_date_bs'] ?? null, $filters['to_date_bs'] ?? null);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'from_date_bs' => $exception->getMessage(),
                'to_date_bs' => $exception->getMessage(),
            ]);
        }

        $hasSearched = filled($filters['employee_name'] ?? null)
            || filled($filters['from_date_bs'] ?? null)
            || filled($filters['to_date_bs'] ?? null);

        if ($hasSearched) {
            $rows = EmployeeSalary::query()
                ->when($filters['employee_name'] ?? null, fn ($query, $name) => $query->where('employee_name', 'like', '%' . trim((string) $name) . '%'))
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
            'filters' => [
                'employee_name' => $filters['employee_name'] ?? null,
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
            'hasSearched' => $hasSearched,
        ]);
    }

    public function create(): View
    {
        return view('employee-salaries.create', [
            'todayBs' => DateHelper::getCurrentBS(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:100'],
            'salary_date_bs' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $validated['salary_date'] = DateHelper::bsToAd($validated['salary_date_bs']);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'salary_date_bs' => $exception->getMessage(),
            ]);
        }

        $validated['salary_month'] = substr($validated['salary_date_bs'], 0, 7);

        $basic = (float) $validated['basic_salary'];
        $allowance = (float) ($validated['allowance'] ?? 0);
        $deduction = (float) ($validated['deduction'] ?? 0);

        $validated['allowance'] = $allowance;
        $validated['deduction'] = $deduction;
        $validated['net_salary'] = $basic + $allowance - $deduction;

        unset($validated['salary_date_bs']);

        $salary = EmployeeSalary::query()->create($validated);

        return redirect()
            ->route('employee-salaries.show', $salary)
            ->with('success', 'Employee salary saved successfully.');
    }

    public function show(EmployeeSalary $employeeSalary): View
    {
        $employeeSalary->salary_date_bs = DateHelper::adToBs($employeeSalary->salary_date);

        return view('employee-salaries.show', [
            'salary' => $employeeSalary,
        ]);
    }

    public function print(EmployeeSalary $employeeSalary): View
    {
        $employeeSalary->salary_date_bs = DateHelper::adToBs($employeeSalary->salary_date);

        return view('employee-salaries.print', [
            'salary' => $employeeSalary,
        ]);
    }
}

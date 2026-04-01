<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Party;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $employees = Employee::query()
            ->select('employees.*')
            ->join('parties', 'parties.id', '=', 'employees.party_id')
            ->with('party')
            ->when($filters['keyword'] ?? null, function ($query, $keyword) {
                $term = '%' . trim((string) $keyword) . '%';

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->whereHas('party', function ($partyQuery) use ($term) {
                            $partyQuery
                                ->where('name', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->orderBy('parties.name')
            ->paginate(20)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'filters' => [
                'keyword' => $filters['keyword'] ?? null,
            ],
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'parties' => Party::query()
                ->whereDoesntHave('employees')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id', 'unique:employees,party_id'],
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

        $employee = Employee::query()->create($validated);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): View
    {
        return view('employees.show', [
            'employee' => $employee->load('party'),
        ]);
    }
}

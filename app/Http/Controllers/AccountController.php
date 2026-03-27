<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Ledger;
use App\Services\LedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function index(): View
    {
        $accounts = Account::query()
            ->latest()
            ->paginate(20)
            ->through(function (Account $account) {
                $account->balance = $this->ledger->accountBalance($account->id);

                return $account;
            });

        return view('accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank'],
        ]);

        $account = Account::query()->create($validated);

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account): View
    {
        return view('accounts.show', [
            'account' => $account->loadCount('payments'),
            'balance' => $this->ledger->accountBalance($account->id),
        ]);
    }

    public function ledgerStatement(Account $account): View
    {
        return view('accounts.ledger', [
            'account' => $account,
            'ledgerRows' => Ledger::query()
                ->where('account_id', $account->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\LedgerService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(LedgerService $ledger): View
    {
        $partyBalances = Party::query()
            ->pluck('id')
            ->map(fn ($partyId) => $ledger->partyBalance((string) $partyId));

        $accounts = Account::query()
            ->orderBy('name')
            ->get()
            ->each(fn (Account $account) => $account->balance = $ledger->accountBalance($account->id));

        return view('dashboard', [
            'totalReceivable' => (float) $partyBalances->filter(fn ($balance) => $balance > 0)->sum(),
            'totalPayable' => abs((float) $partyBalances->filter(fn ($balance) => $balance < 0)->sum()),
            'accounts' => $accounts,
            'recentSales' => Sale::query()->with('party')->latest()->limit(5)->get(),
            'recentPurchases' => Purchase::query()->with('party')->latest()->limit(5)->get(),
            'recentPayments' => Payment::query()->with(['party', 'account'])->latest()->limit(5)->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\LedgerService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(LedgerService $ledger): View
    {
        $partyBalances = Ledger::query()
            ->whereNotNull('party_id')
            ->groupBy('party_id')
            ->selectRaw('party_id, COALESCE(SUM(dr_amount) - SUM(cr_amount), 0) as balance')
            ->pluck('balance');

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

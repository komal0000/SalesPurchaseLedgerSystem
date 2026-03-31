<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportController extends Controller
{
    public function cashbook(Request $request): View
    {
        $filters = $request->validate([
            'account_id' => ['nullable', 'uuid', 'exists:accounts,id'],
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

        $cashAccounts = Account::query()
            ->where('type', 'cash')
            ->orderBy('name')
            ->get();

        $selectedAccountId = $filters['account_id'] ?? null;

        $query = Ledger::query()
            ->whereNotNull('account_id')
            ->whereIn('account_id', $cashAccounts->pluck('id'));

        if ($selectedAccountId) {
            $query->where('account_id', $selectedAccountId);
        }

        $openingBalance = (clone $query)
            ->when($fromAd, fn ($builder) => $builder->whereDate('created_at', '<', $fromAd))
            ->selectRaw('COALESCE(SUM(dr_amount) - SUM(cr_amount), 0) as balance')
            ->value('balance') ?? 0;

        $ledgerRows = (clone $query)
            ->when($fromAd, fn ($builder) => $builder->whereDate('created_at', '>=', $fromAd))
            ->when($toAd, fn ($builder) => $builder->whereDate('created_at', '<=', $toAd))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $paymentMap = Payment::query()
            ->with('party:id,name')
            ->whereIn('id', $ledgerRows->where('ref_table', 'payments')->pluck('ref_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($ledgerRows as $row) {
            $payment = $paymentMap->get($row->ref_id);
            $row->reference_text = $payment
                ? 'Payment / ' . ($payment->party?->name ?? 'Unknown Party')
                : (ucfirst($row->ref_table) . ' / ' . $row->ref_id);
        }

        $periodDebit = (float) $ledgerRows->sum(fn (Ledger $row) => (float) $row->dr_amount);
        $periodCredit = (float) $ledgerRows->sum(fn (Ledger $row) => (float) $row->cr_amount);

        return view('reports.cashbook', [
            'cashAccounts' => $cashAccounts,
            'ledgerRows' => $ledgerRows,
            'openingBalance' => (float) $openingBalance,
            'periodDebit' => $periodDebit,
            'periodCredit' => $periodCredit,
            'filters' => [
                'account_id' => $selectedAccountId,
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
        ]);
    }

    public function profitLoss(Request $request): View
    {
        $todayBs = DateHelper::getCurrentBS();
        $startBs = substr($todayBs, 0, 8) . '01';

        $filters = $request->validate([
            'from_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'to_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        $fromBs = $filters['from_date_bs'] ?? $startBs;
        $toBs = $filters['to_date_bs'] ?? $todayBs;

        try {
            [$fromAd, $toAd] = DateHelper::getAdRangeFromBsFilters($fromBs, $toBs);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'from_date_bs' => $exception->getMessage(),
                'to_date_bs' => $exception->getMessage(),
            ]);
        }

        $salesTotal = (float) Sale::query()
            ->whereDate('created_at', '>=', $fromAd)
            ->whereDate('created_at', '<=', $toAd)
            ->sum('total');

        $purchaseTotal = (float) Purchase::query()
            ->whereDate('created_at', '>=', $fromAd)
            ->whereDate('created_at', '<=', $toAd)
            ->sum('total');

        $profitLoss = $salesTotal - $purchaseTotal;

        return view('reports.profit-loss', [
            'filters' => [
                'from_date_bs' => $fromBs,
                'to_date_bs' => $toBs,
            ],
            'salesTotal' => $salesTotal,
            'purchaseTotal' => $purchaseTotal,
            'profitLoss' => $profitLoss,
        ]);
    }
}

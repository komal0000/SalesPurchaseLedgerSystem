<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Purchase;
use App\Services\PartyCacheService;
use App\Services\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $service,
        private readonly PartyCacheService $partyCache,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
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

        $hasSearched = filled($filters['party_id'] ?? null)
            || filled($filters['from_date_bs'] ?? null)
            || filled($filters['to_date_bs'] ?? null);

        if ($hasSearched) {
            $purchases = Purchase::query()
                ->with(['party', 'payments'])
                ->when($filters['party_id'] ?? null, fn ($query, $partyId) => $query->where('party_id', $partyId))
                ->when($fromAd, fn ($query) => $query->whereDate('created_at', '>=', $fromAd))
                ->when($toAd, fn ($query) => $query->whereDate('created_at', '<=', $toAd))
                ->latest()
                ->paginate(20)
                ->withQueryString();

            $purchases->through(function (Purchase $purchase) {
                $purchase->created_at_bs = DateHelper::adToBs($purchase->created_at);
                $purchase->paid_amount = (float) $purchase->payments->where('type', 'given')->sum('amount');

                return $purchase;
            });
        } else {
            $purchases = new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 20,
                currentPage: 1,
                options: ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('purchases.index', [
            'purchases' => $purchases,
            'parties' => $this->partyCache->all(),
            'filters' => [
                'party_id' => $filters['party_id'] ?? null,
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
            'hasSearched' => $hasSearched,
            'currentBsDateInt' => DateHelper::currentBsInt(),
        ]);
    }

    public function create(): View
    {
        $accounts = Account::query()
            ->orderByRaw("case when type = 'cash' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return view('purchases.create', [
            'parties' => $this->partyCache->all(),
            'accounts' => $accounts,
            'defaultCashAccountId' => $accounts->firstWhere('type', 'cash')?->id,
            'currentBsDateInt' => DateHelper::currentBsInt(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->all();

        $payload['items'] = collect($payload['items'] ?? [])
            ->map(function ($item) {
                $item = is_array($item) ? $item : [];

                return [
                    'particular' => trim((string) ($item['particular'] ?? '')),
                    'qty' => $item['qty'] ?? null,
                    'price' => $item['price'] ?? null,
                ];
            })
            ->filter(fn (array $item) => $item['particular'] !== '' || filled($item['qty']) || filled($item['price']))
            ->values()
            ->all();

        $payload['payments'] = collect($payload['payments'] ?? [])
            ->map(function ($payment) {
                $payment = is_array($payment) ? $payment : [];

                return [
                    'account_id' => $payment['account_id'] ?? null,
                    'amount' => $payment['amount'] ?? null,
                    'cheque_number' => trim((string) ($payment['cheque_number'] ?? '')),
                ];
            })
            ->filter(fn (array $payment) => filled($payment['account_id']) || filled($payment['amount']) || filled($payment['cheque_number']))
            ->values()
            ->all();

        $validated = validator($payload, [
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.particular' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.cheque_number' => ['nullable', 'string', 'max:50'],
        ])->validate();

        $itemTotal = collect($validated['items'])->sum(fn (array $item) => (float) $item['qty'] * (float) $item['price']);
        $paymentTotal = collect($validated['payments'] ?? [])->sum(fn (array $payment) => (float) $payment['amount']);

        if ($paymentTotal > $itemTotal) {
            throw ValidationException::withMessages([
                'payments' => 'Payment total cannot be greater than bill total.',
            ]);
        }

        $purchase = $this->service->create($validated);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase created successfully.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['party', 'items', 'payments.account']);
        $purchase->created_at_bs = DateHelper::adToBs($purchase->created_at);
        $purchase->paid_amount = (float) $purchase->payments->where('type', 'given')->sum('amount');
        $purchase->remaining_amount = max(0, (float) $purchase->total - $purchase->paid_amount);

        return view('purchases.show', [
            'purchase' => $purchase,
            'linkedPayments' => $purchase->payments()->with('account')->latest()->get(),
        ]);
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->service->delete($purchase);

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PartyCacheService;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $service,
        private readonly PartyCacheService $partyCache,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'type' => ['nullable', 'in:received,given'],
            'keyword' => ['nullable', 'string', 'max:80'],
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
            || filled($filters['account_id'] ?? null)
            || filled($filters['type'] ?? null)
            || filled($filters['keyword'] ?? null)
            || filled($filters['from_date_bs'] ?? null)
            || filled($filters['to_date_bs'] ?? null);

        if ($hasSearched) {
            $payments = Payment::query()
                ->with(['party', 'account', 'sale', 'purchase'])
                ->when($filters['party_id'] ?? null, fn ($query, $partyId) => $query->where('party_id', $partyId))
                ->when($filters['account_id'] ?? null, fn ($query, $accountId) => $query->where('account_id', $accountId))
                ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
                ->when($fromAd, fn ($query) => $query->whereDate('created_at', '>=', $fromAd))
                ->when($toAd, fn ($query) => $query->whereDate('created_at', '<=', $toAd))
                ->when($filters['keyword'] ?? null, function ($query, $keyword) {
                    $term = '%' . trim((string) $keyword) . '%';

                    $query->where(function ($subQuery) use ($term) {
                        $subQuery
                            ->where('cheque_number', 'like', $term)
                            ->orWhereHas('party', fn ($partyQuery) => $partyQuery->where('name', 'like', $term))
                            ->orWhereHas('account', fn ($accountQuery) => $accountQuery->where('name', 'like', $term));
                    });
                })
                ->latest()
                ->paginate(20)
                ->withQueryString();

            $payments->through(function (Payment $payment) {
                $payment->created_at_bs = DateHelper::adToBs($payment->created_at);

                return $payment;
            });
        } else {
            $payments = new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 20,
                currentPage: 1,
                options: ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('payments.index', [
            'payments' => $payments,
            'parties' => $this->partyCache->all(),
            'accounts' => Account::query()->orderByRaw("case when type = 'cash' then 0 else 1 end")->orderBy('name')->get(),
            'filters' => [
                'party_id' => $filters['party_id'] ?? null,
                'account_id' => $filters['account_id'] ?? null,
                'type' => $filters['type'] ?? null,
                'keyword' => $filters['keyword'] ?? null,
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
            'hasSearched' => $hasSearched,
        ]);
    }

    public function create(Request $request): View
    {
        $sale = $request->filled('sale_id') ? Sale::query()->with('party')->find($request->string('sale_id')) : null;
        $purchase = $request->filled('purchase_id') ? Purchase::query()->with('party')->find($request->string('purchase_id')) : null;
        $selectedPartyId = old('party_id', $request->string('party_id')->toString() ?: ($sale?->party_id ?? $purchase?->party_id));
        $accounts = Account::query()
            ->orderByRaw("case when type = 'cash' then 0 else 1 end")
            ->orderBy('name')
            ->get();
        $defaultCashAccountId = $accounts->firstWhere('type', 'cash')?->id;

        return view('payments.create', [
            'parties' => $this->partyCache->all(),
            'accounts' => $accounts,
            'sales' => Sale::query()->with('party')->latest()->get(),
            'purchases' => Purchase::query()->with('party')->latest()->get(),
            'selectedPartyId' => $selectedPartyId,
            'selectedAccountId' => old('account_id', $defaultCashAccountId),
            'selectedSaleId' => old('sale_id', $sale?->id),
            'selectedPurchaseId' => old('purchase_id', $purchase?->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cheque_number' => ['nullable', 'string', 'max:50'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
        ]);

        if (!empty($validated['sale_id']) && !empty($validated['purchase_id'])) {
            throw ValidationException::withMessages([
                'sale_id' => 'A payment can be linked to a sale or purchase, not both.',
                'purchase_id' => 'A payment can be linked to a sale or purchase, not both.',
            ]);
        }

        if (!empty($validated['sale_id'])) {
            $sale = Sale::query()->findOrFail($validated['sale_id']);
            if ($sale->party_id !== $validated['party_id']) {
                throw ValidationException::withMessages([
                    'sale_id' => 'The selected sale does not belong to the chosen party.',
                ]);
            }
        }

        if (!empty($validated['purchase_id'])) {
            $purchase = Purchase::query()->findOrFail($validated['purchase_id']);
            if ($purchase->party_id !== $validated['party_id']) {
                throw ValidationException::withMessages([
                    'purchase_id' => 'The selected purchase does not belong to the chosen party.',
                ]);
            }
        }

        $validated['type'] = !empty($validated['sale_id'])
            ? 'received'
            : (!empty($validated['purchase_id']) ? 'given' : 'received');

        $payment = $this->service->create($validated);

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Payment created successfully.');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['party', 'account', 'sale', 'purchase']);
        $payment->created_at_bs = DateHelper::adToBs($payment->created_at);

        return view('payments.show', [
            'payment' => $payment,
        ]);
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->service->delete($payment);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment deleted successfully and ledger reversed.');
    }
}

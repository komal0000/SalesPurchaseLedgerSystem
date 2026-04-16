<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyOpeningBalanceRequest;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\LedgerService;
use App\Services\PartyCacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class PartyController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly PartyCacheService $partyCache,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:employee,ordinary'],
        ]);

        $keyword = trim((string) ($filters['keyword'] ?? ''));

        $parties = Party::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $term = '%' . $keyword . '%';

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->when(($filters['category'] ?? null) === 'employee', fn ($query) => $query->whereHas('employees'))
            ->when(($filters['category'] ?? null) === 'ordinary', fn ($query) => $query->whereDoesntHave('employees'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (Party $party) {
                $party->balance = $this->ledger->partyBalance($party->id);

                return $party;
            });

        $hasActiveFilters = $keyword !== '' || filled($filters['category'] ?? null);

        return view('parties.index', [
            'parties' => $parties,
            'filters' => [
                'keyword' => $keyword,
                'category' => $filters['category'] ?? null,
            ],
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }

    public function store(StorePartyRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $validated['opening_balance'] = (float) ($validated['opening_balance'] ?? 0);
        $validated['opening_balance_side'] = $validated['opening_balance_side'] ?? 'dr';

        $party = Party::query()->create($validated);

        $this->partyCache->refreshAll();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Party created successfully.',
                'party' => [
                    'id' => $party->id,
                    'name' => $party->name,
                    'phone' => $party->phone,
                    'address' => $party->address,
                    'opening_balance' => (float) ($party->opening_balance ?? 0),
                    'opening_balance_side' => $party->opening_balance_side,
                ],
            ], 201);
        }

        return redirect()
            ->route('parties.show', $party)
            ->with('success', 'Party created successfully.');
    }

    public function show(Party $party): View
    {
        return view('parties.show', [
            'party' => $party->loadCount(['sales', 'purchases', 'payments']),
            'balance' => $this->ledger->partyBalance($party->id),
            'openingBalanceSigned' => $this->openingSigned((float) ($party->opening_balance ?? 0), $party->opening_balance_side ?? 'dr'),
        ]);
    }

    public function edit(Party $party): View
    {
        return view('parties.edit', [
            'party' => $party,
        ]);
    }

    public function update(StorePartyRequest $request, Party $party): RedirectResponse
    {
        $validated = $request->validated();

        $validated['opening_balance'] = (float) ($validated['opening_balance'] ?? 0);
        $validated['opening_balance_side'] = $validated['opening_balance_side'] ?? 'dr';

        $party->update($validated);
        $this->partyCache->refreshAll();

        return redirect()
            ->route('parties.show', $party)
            ->with('success', 'Party updated successfully.');
    }

    public function ledgerStatement(Request $request, Party $party): View
    {
        $filters = $request->validate([
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

        $query = Ledger::query()->where('party_id', $party->id);

        $openingBase = $this->openingSigned((float) ($party->opening_balance ?? 0), $party->opening_balance_side ?? 'dr');

        $openingBalance = $openingBase + ((clone $query)
            ->when($fromAd, fn ($builder) => $builder->whereDate('created_at', '<', $fromAd))
            ->selectRaw('COALESCE(SUM(dr_amount) - SUM(cr_amount), 0) as balance')
            ->value('balance') ?? 0);

        $ledgerRows = (clone $query)
            ->when($fromAd, fn ($builder) => $builder->whereDate('created_at', '>=', $fromAd))
            ->when($toAd, fn ($builder) => $builder->whereDate('created_at', '<=', $toAd))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->attachReferenceText($ledgerRows);

        return view('parties.ledger', [
            'party' => $party,
            'ledgerRows' => $ledgerRows,
            'openingBalance' => (float) $openingBalance,
            'filters' => [
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
        ]);
    }

    public function updateOpeningBalance(UpdatePartyOpeningBalanceRequest $request, Party $party): RedirectResponse
    {
        $validated = $request->validated();

        $party->update([
            'opening_balance' => (float) $validated['opening_balance'],
            'opening_balance_side' => $validated['opening_balance_side'],
        ]);

        return redirect()
            ->route('parties.show', $party)
            ->with('success', 'Opening balance updated successfully.');
    }

    private function attachReferenceText(Collection $ledgerRows): void
    {
        $saleMap = Sale::query()
            ->with('items:id,sale_id,particular,qty,price')
            ->whereIn('id', $ledgerRows->where('ref_table', 'sales')->pluck('ref_id')->unique())
            ->get()
            ->keyBy('id');

        $purchaseMap = Purchase::query()
            ->with('items:id,purchase_id,particular,qty,price')
            ->whereIn('id', $ledgerRows->where('ref_table', 'purchases')->pluck('ref_id')->unique())
            ->get()
            ->keyBy('id');

        $paymentMap = Payment::query()
            ->with('account:id,name')
            ->whereIn('id', $ledgerRows->where('ref_table', 'payments')->pluck('ref_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($ledgerRows as $row) {
            if ($row->ref_table === 'sales') {
                $sale = $saleMap->get($row->ref_id);
                $row->reference_text = $sale
                    ? 'Sale / ' . $this->itemSummary($sale->items)
                    : 'Sale / ' . $row->ref_id;
                continue;
            }

            if ($row->ref_table === 'purchases') {
                $purchase = $purchaseMap->get($row->ref_id);
                $row->reference_text = $purchase
                    ? 'Purchase / ' . $this->itemSummary($purchase->items)
                    : 'Purchase / ' . $row->ref_id;
                continue;
            }

            if ($row->ref_table === 'payments') {
                $payment = $paymentMap->get($row->ref_id);
                $row->reference_text = $payment
                    ? 'Payment / ' . ($payment->account?->name ?? 'Unknown Account')
                    : 'Payment / ' . $row->ref_id;
                continue;
            }

            $row->reference_text = ucfirst($row->ref_table) . ' / ' . $row->ref_id;
        }
    }

    private function itemSummary(Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'No items';
        }

        return $items
            ->map(fn ($item) => sprintf(
                '%s @ %s * %s',
                $item->particular,
                number_format((float) $item->price, 2),
                number_format((float) $item->qty, 2)
            ))
            ->implode(', ');
    }

    private function openingSigned(float $amount, string $side): float
    {
        return $side === 'cr' ? -$amount : $amount;
    }

    public function destroy(Party $party): RedirectResponse
    {
        $blockingRecords = collect([
            'employees' => $party->employees()->count(),
            'sales' => $party->sales()->withTrashed()->count(),
            'purchases' => $party->purchases()->withTrashed()->count(),
            'payments' => $party->payments()->withTrashed()->count(),
            'ledger entries' => $party->ledgerEntries()->count(),
        ])->filter(fn (int $count): bool => $count > 0);

        if ($blockingRecords->isNotEmpty()) {
            $details = $blockingRecords
                ->map(fn (int $count, string $label): string => sprintf('%s (%d)', ucfirst($label), $count))
                ->implode(', ');

            return redirect()
                ->route('parties.index')
                ->with('error', 'Party cannot be deleted because it is linked to: ' . $details . '.');
        }

        $party->delete();

        $this->partyCache->refreshAll();

        return redirect()
            ->route('parties.index')
            ->with('success', 'Party deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\LedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class PartyController extends Controller
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function index(): View
    {
        $parties = Party::query()
            ->latest()
            ->paginate(20)
            ->through(function (Party $party) {
                $party->balance = $this->ledger->partyBalance($party->id);

                return $party;
            });

        return view('parties.index', compact('parties'));
    }

    public function create(): View
    {
        return view('parties.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_side' => ['nullable', 'in:dr,cr'],
        ]);

        $validated['opening_balance'] = (float) ($validated['opening_balance'] ?? 0);
        $validated['opening_balance_side'] = $validated['opening_balance_side'] ?? 'dr';

        $party = Party::query()->create($validated);

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

    public function updateOpeningBalance(Request $request, Party $party): RedirectResponse
    {
        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_balance_side' => ['required', 'in:dr,cr'],
        ]);

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
        $party->delete();

        return redirect()
            ->route('parties.index')
            ->with('success', 'Party deleted successfully.');
    }
}

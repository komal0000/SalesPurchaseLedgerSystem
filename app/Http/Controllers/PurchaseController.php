<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Party;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'party_id' => ['nullable', 'uuid', 'exists:parties,id'],
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

        return view('purchases.index', [
            'purchases' => $purchases,
            'parties' => Party::query()->orderBy('name')->get(),
            'filters' => [
                'party_id' => $filters['party_id'] ?? null,
                'from_date_bs' => $filters['from_date_bs'] ?? null,
                'to_date_bs' => $filters['to_date_bs'] ?? null,
            ],
            'currentBsDateInt' => DateHelper::currentBsInt(),
        ]);
    }

    public function create(): View
    {
        return view('purchases.create', [
            'parties' => Party::query()->orderBy('name')->get(),
            'currentBsDateInt' => DateHelper::currentBsInt(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'party_id' => ['required', 'uuid', 'exists:parties,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.particular' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = $this->service->create($validated);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase created successfully.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['party', 'items', 'payments.account']);
        $purchase->created_at_bs = DateHelper::adToBs($purchase->created_at);

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

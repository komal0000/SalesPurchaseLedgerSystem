<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Party;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service) {}

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

        $sales = Sale::query()
            ->with(['party', 'payments'])
            ->when($filters['party_id'] ?? null, fn ($query, $partyId) => $query->where('party_id', $partyId))
            ->when($fromAd, fn ($query) => $query->whereDate('created_at', '>=', $fromAd))
            ->when($toAd, fn ($query) => $query->whereDate('created_at', '<=', $toAd))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $sales->through(function (Sale $sale) {
            $sale->created_at_bs = DateHelper::adToBs($sale->created_at);
            $sale->received_amount = (float) $sale->payments->where('type', 'received')->sum('amount');
            return $sale;
        });

        return view('sales.index', [
            'sales' => $sales,
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
        return view('sales.create', [
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

        $sale = $this->service->create($validated);

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Sale created successfully.');
    }

    public function show(Sale $sale): View
    {
        $sale->load(['party', 'items', 'payments.account']);
        $sale->created_at_bs = DateHelper::adToBs($sale->created_at);

        return view('sales.show', [
            'sale' => $sale,
            'linkedPayments' => $sale->payments()->with('account')->latest()->get(),
        ]);
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->service->delete($sale);

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale deleted successfully.');
    }
}

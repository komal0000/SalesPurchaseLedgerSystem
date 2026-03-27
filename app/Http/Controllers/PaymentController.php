<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    public function index(): View
    {
        $payments = Payment::query()
            ->with(['party', 'account', 'sale', 'purchase'])
            ->latest()
            ->paginate(20);

        $payments->through(function (Payment $payment) {
            $payment->created_at_bs = DateHelper::adToBs($payment->created_at);
            return $payment;
        });

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $sale = $request->filled('sale_id') ? Sale::query()->with('party')->find($request->string('sale_id')) : null;
        $purchase = $request->filled('purchase_id') ? Purchase::query()->with('party')->find($request->string('purchase_id')) : null;
        $selectedPartyId = old('party_id', $request->string('party_id')->toString() ?: ($sale?->party_id ?? $purchase?->party_id));

        return view('payments.create', [
            'parties' => Party::query()->orderBy('name')->get(),
            'accounts' => Account::query()->orderBy('name')->get(),
            'sales' => Sale::query()->with('party')->latest()->get(),
            'purchases' => Purchase::query()->with('party')->latest()->get(),
            'selectedPartyId' => $selectedPartyId,
            'selectedSaleId' => old('sale_id', $sale?->id),
            'selectedPurchaseId' => old('purchase_id', $purchase?->id),
            'selectedType' => old('type', $sale ? 'received' : ($purchase ? 'given' : 'received')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'party_id' => ['required', 'uuid', 'exists:parties,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:received,given'],
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'sale_id' => ['nullable', 'uuid', 'exists:sales,id'],
            'purchase_id' => ['nullable', 'uuid', 'exists:purchases,id'],
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

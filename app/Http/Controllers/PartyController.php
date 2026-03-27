<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Party;
use App\Services\LedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        ]);

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
        ]);
    }

    public function ledgerStatement(Party $party): View
    {
        return view('parties.ledger', [
            'party' => $party,
            'ledgerRows' => Ledger::query()
                ->where('party_id', $party->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function destroy(Party $party): RedirectResponse
    {
        $party->delete();

        return redirect()
            ->route('parties.index')
            ->with('success', 'Party deleted successfully.');
    }
}

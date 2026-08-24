<?php

namespace App\Http\Controllers;

use App\Enums\StockEntryType;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreEntryRequest;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Official;
use App\Services\Inventory\PostStockEntryAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntryController extends Controller
{
    /**
     * Display a listing of the stock ledger entries.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Consumable::class);

        $query = Entry::query()->with(['consumable', 'recipient', 'recorder']);

        if ($request->filled('consumable_id')) {
            $query->where('consumable_id', $request->input('consumable_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $entries = $query->latest('id')->paginate(25)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($entries);
        }

        return view('stock.index', [
            'entries' => $entries,
            'consumables' => Consumable::orderBy('name')->get(),
            'officials' => Official::orderBy('name')->get(),
            'filters' => $request->only(['consumable_id', 'type']),
        ]);
    }

    /**
     * Store a new stock ledger entry (purchase, issue, adjustment).
     */
    public function store(
        StoreEntryRequest $request,
        PostStockEntryAction $action,
        ?Consumable $consumable = null
    ): RedirectResponse|JsonResponse {
        $this->authorize('postEntry', Consumable::class);

        $consumableId = $consumable?->id ?? $request->input('consumable_id');
        if (!$consumableId) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'A valid consumable ID is required.'], 422);
            }
            return back()->withErrors(['consumable_id' => 'A valid consumable ID is required.']);
        }

        /** @var Consumable $targetConsumable */
        $targetConsumable = Consumable::findOrFail($consumableId);

        $rawType = $request->input('type');
        $type = $rawType instanceof StockEntryType ? $rawType : StockEntryType::from($rawType);

        $quantity = (int) $request->input('quantity');
        $recipient = $request->filled('recipient_official_id')
            ? Official::find($request->input('recipient_official_id'))
            : null;

        try {
            $entry = $action->execute(
                consumable: $targetConsumable,
                type: $type,
                quantity: $quantity,
                recipient: $recipient,
                user: $request->user(),
                remarks: $request->input('remarks'),
                idempotencyKey: $request->input('idempotency_key')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Stock entry recorded successfully.',
                    'entry' => $entry->fresh(['consumable', 'recipient', 'recorder']),
                    'stock_after' => $entry->stock_after,
                ], 201);
            }

            return redirect()->route('consumables.show', $targetConsumable)
                ->with('success', 'Stock entry successfully recorded.');
        } catch (InsufficientStockException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Insufficient stock for this operation.',
                    'message' => $e->getMessage(),
                    'available' => $e->getAvailable(),
                    'requested' => $e->getRequested(),
                ], 422);
            }

            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified stock entry.
     */
    public function show(Request $request, Entry $entry): View|JsonResponse
    {
        $this->authorize('viewAny', Consumable::class);

        $entry->load(['consumable', 'recipient', 'recorder']);

        if ($request->wantsJson()) {
            return response()->json($entry);
        }

        return view('stock.show', ['entry' => $entry]);
    }
}

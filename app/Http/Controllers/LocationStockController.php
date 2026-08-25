<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\Official;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Services\Stock\LocationStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LocationStockController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', StockBalance::class);

        $balances = StockBalance::visibleTo($request->user())
            ->with(['consumable', 'location.site'])
            ->when($request->integer('consumable_id'), fn ($query, $id) => $query->where('consumable_id', $id))
            ->when($request->integer('location_id'), fn ($query, $id) => $query->where('location_id', $id))
            ->orderBy('location_id')
            ->orderBy('consumable_id')
            ->paginate(25, ['*'], 'balances_page')
            ->withQueryString();

        $transactions = StockTransaction::visibleTo($request->user())
            ->with(['consumable', 'sourceLocation', 'destinationLocation', 'recipient', 'recorder', 'accepter'])
            ->when($request->integer('consumable_id'), fn ($query, $id) => $query->where('consumable_id', $id))
            ->latest('id')
            ->paginate(25, ['*'], 'transactions_page')
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(compact('balances', 'transactions'));
        }

        return view('stock.locations', compact('balances', 'transactions'));
    }

    public function purchase(Request $request, LocationStockService $service): RedirectResponse|JsonResponse
    {
        $this->authorize('postEntry', Consumable::class);
        $data = $this->validateCommon($request, ['destination_location_id' => ['required', 'integer', 'exists:locations,id']]);

        $transaction = $service->purchase(
            Consumable::findOrFail($data['consumable_id']),
            Location::findOrFail($data['destination_location_id']),
            $data['quantity'],
            $request->user(),
            $data['idempotency_key'],
            $data['remarks'] ?? null
        );

        return $this->createdResponse($request, $transaction);
    }

    public function issue(Request $request, LocationStockService $service): RedirectResponse|JsonResponse
    {
        $this->authorize('postEntry', Consumable::class);
        $data = $this->validateCommon($request, [
            'source_location_id' => ['required', 'integer', 'exists:locations,id'],
            'recipient_official_id' => ['nullable', 'integer', 'exists:officials,id'],
        ]);

        try {
            $transaction = $service->issue(
                Consumable::findOrFail($data['consumable_id']),
                Location::findOrFail($data['source_location_id']),
                $data['quantity'],
                $request->user(),
                $data['idempotency_key'],
                isset($data['recipient_official_id']) ? Official::findOrFail($data['recipient_official_id']) : null,
                $data['remarks'] ?? null
            );
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return $this->createdResponse($request, $transaction);
    }

    public function adjust(Request $request, LocationStockService $service): RedirectResponse|JsonResponse
    {
        $this->authorize('postEntry', Consumable::class);
        $data = $request->validate([
            'consumable_id' => ['required', 'integer', 'exists:consumables,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'idempotency_key' => ['required', 'string', 'max:191'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $transaction = $service->adjust(
                Consumable::findOrFail($data['consumable_id']),
                Location::findOrFail($data['location_id']),
                $data['quantity_delta'],
                $request->user(),
                $data['idempotency_key'],
                $data['remarks'] ?? null
            );
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['quantity_delta' => $exception->getMessage()]);
        }

        return $this->createdResponse($request, $transaction);
    }

    public function transfer(Request $request, LocationStockService $service): RedirectResponse|JsonResponse
    {
        $this->authorize('postEntry', Consumable::class);
        $data = $this->validateCommon($request, [
            'source_location_id' => ['required', 'integer', 'different:destination_location_id', 'exists:locations,id'],
            'destination_location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        try {
            // Atomic self-acceptance is deliberately limited to users writable at both locations.
            // Two-user transfers call the same service from an approval workflow with its accepter.
            $transaction = $service->transfer(
                Consumable::findOrFail($data['consumable_id']),
                Location::findOrFail($data['source_location_id']),
                Location::findOrFail($data['destination_location_id']),
                $data['quantity'],
                $request->user(),
                $request->user(),
                $data['idempotency_key'],
                $data['remarks'] ?? null
            );
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return $this->createdResponse($request, $transaction);
    }

    /** @param array<string, array<int, string>> $additionalRules */
    private function validateCommon(Request $request, array $additionalRules): array
    {
        return $request->validate(array_merge([
            'consumable_id' => ['required', 'integer', 'exists:consumables,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:191'],
            'remarks' => ['nullable', 'string'],
        ], $additionalRules));
    }

    private function createdResponse(Request $request, StockTransaction $transaction): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Location stock transaction recorded.',
                'transaction' => $transaction->fresh(),
            ], 201);
        }

        return redirect()->route('stock.locations.index')
            ->with('success', 'Location stock transaction recorded.');
    }
}

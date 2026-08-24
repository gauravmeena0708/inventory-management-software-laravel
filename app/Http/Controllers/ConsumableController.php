<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsumableRequest;
use App\Http\Requests\UpdateConsumableRequest;
use App\Models\Consumable;
use App\Models\Official;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumableController extends Controller
{
    /**
     * Display a listing of the consumables.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Consumable::class);

        $query = Consumable::query()->withCount('entries');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $consumables = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($consumables);
        }

        return view('consumables.index', [
            'consumables' => $consumables,
            'filters' => $request->only(['search', 'low_stock']),
        ]);
    }

    /**
     * Show the form for creating a new consumable.
     */
    public function create(): View
    {
        $this->authorize('create', Consumable::class);

        return view('consumables.create');
    }

    /**
     * Store a newly created consumable in storage.
     */
    public function store(StoreConsumableRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        if (!isset($validated['in_stock'])) {
            $validated['in_stock'] = 0;
        }

        $consumable = Consumable::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Consumable created successfully.',
                'consumable' => $consumable,
            ], 201);
        }

        return redirect()->route('consumables.show', $consumable)
            ->with('success', 'Consumable item created successfully.');
    }

    /**
     * Display the specified consumable with its stock entries.
     */
    public function show(Request $request, Consumable $consumable): View|JsonResponse
    {
        $this->authorize('view', $consumable);

        $consumable->load([
            'entries' => fn ($q) => $q->with(['recipient', 'recorder'])->latest('id')->take(50),
        ]);

        if ($request->wantsJson()) {
            return response()->json($consumable);
        }

        return view('consumables.show', [
            'consumable' => $consumable,
            'officials' => Official::orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified consumable.
     */
    public function edit(Consumable $consumable): View
    {
        $this->authorize('update', $consumable);

        return view('consumables.edit', ['consumable' => $consumable]);
    }

    /**
     * Update the specified consumable in storage.
     */
    public function update(UpdateConsumableRequest $request, Consumable $consumable): RedirectResponse|JsonResponse
    {
        $consumable->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Consumable updated successfully.',
                'consumable' => $consumable->fresh(),
            ]);
        }

        return redirect()->route('consumables.show', $consumable)
            ->with('success', 'Consumable updated successfully.');
    }

    /**
     * Remove the specified consumable from storage.
     */
    public function destroy(Request $request, Consumable $consumable): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $consumable);

        $consumable->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Consumable deleted successfully.']);
        }

        return redirect()->route('consumables.index')
            ->with('success', 'Consumable deleted successfully.');
    }
}

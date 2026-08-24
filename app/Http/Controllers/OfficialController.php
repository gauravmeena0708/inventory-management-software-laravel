<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficialRequest;
use App\Http\Requests\UpdateOfficialRequest;
use App\Models\Location;
use App\Models\Official;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficialController extends Controller
{
    /**
     * Display a listing of officials.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Official::class);

        $query = Official::query()->with('location')->withCount('assets');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('designation', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        $officials = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($officials);
        }

        return view('officials.index', [
            'officials' => $officials,
            'locations' => Location::orderBy('name')->get(),
            'filters' => $request->only(['search', 'location_id']),
        ]);
    }

    /**
     * Show the form for creating a new official.
     */
    public function create(): View
    {
        $this->authorize('create', Official::class);

        return view('officials.create', [
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created official in storage.
     */
    public function store(StoreOfficialRequest $request): RedirectResponse|JsonResponse
    {
        $official = Official::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Official created successfully.',
                'official' => $official->fresh('location'),
            ], 201);
        }

        return redirect()->route('officials.show', $official)
            ->with('success', 'Official created successfully.');
    }

    /**
     * Display the specified official.
     */
    public function show(Request $request, Official $official): View|JsonResponse
    {
        $this->authorize('view', $official);

        $official->load([
            'location',
            'assets' => fn ($q) => $q->with(['manufacturer', 'location']),
            'assignments' => fn ($q) => $q->with('asset')->latest('assigned_at')->take(20),
            'entries' => fn ($q) => $q->with('consumable')->latest('id')->take(20),
        ]);

        if ($request->wantsJson()) {
            return response()->json($official);
        }

        return view('officials.show', [
            'official' => $official,
            'canViewSensitive' => $request->user()?->can('viewSensitive', $official) ?? false,
        ]);
    }

    /**
     * Show the form for editing the specified official.
     */
    public function edit(Official $official): View
    {
        $this->authorize('update', $official);

        return view('officials.edit', [
            'official' => $official,
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified official in storage.
     */
    public function update(UpdateOfficialRequest $request, Official $official): RedirectResponse|JsonResponse
    {
        $official->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Official updated successfully.',
                'official' => $official->fresh('location'),
            ]);
        }

        return redirect()->route('officials.show', $official)
            ->with('success', 'Official updated successfully.');
    }

    /**
     * Remove the specified official from storage.
     */
    public function destroy(Request $request, Official $official): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $official);

        $official->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Official deleted successfully.']);
        }

        return redirect()->route('officials.index')
            ->with('success', 'Official deleted successfully.');
    }
}

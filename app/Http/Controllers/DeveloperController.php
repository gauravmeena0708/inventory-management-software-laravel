<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeveloperRequest;
use App\Http\Requests\UpdateDeveloperRequest;
use App\Models\Devcat;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    /**
     * Display a listing of developers.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Developer::class);

        $query = Developer::query()->with(['category', 'reportingUser']);

        $status = $request->input('status');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'discontinued' || $status === 'inactive') {
            $query->discontinued();
        } elseif ($request->filled('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('remarks', 'like', "%{$term}%");
            });
        }

        $developers = $query->orderBy('name')->paginate(20)->withQueryString();

        $canViewSensitive = $request->user()?->can('viewSensitive', Developer::class) ?? false;

        // If user cannot view sensitive details, redact them
        if (!$canViewSensitive) {
            $developers->getCollection()->transform(function (Developer $dev) {
                $dev->makeHidden(['salary', 'phone', 'email']);
                return $dev;
            });
        }

        if ($request->wantsJson()) {
            return response()->json($developers);
        }

        return view('developers.index', [
            'developers' => $developers,
            'categories' => Devcat::orderBy('name')->get(),
            'filters' => $request->only(['status', 'category_id', 'search']),
            'canViewSensitive' => $canViewSensitive,
        ]);
    }

    /**
     * Show the form for creating a new developer.
     */
    public function create(): View
    {
        $this->authorize('create', Developer::class);

        return view('developers.create', [
            'categories' => Devcat::orderBy('name')->get(),
            'managers' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created developer in storage.
     */
    public function store(StoreDeveloperRequest $request): RedirectResponse|JsonResponse
    {
        $developer = Developer::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Developer created successfully.',
                'developer' => $developer->fresh(['category', 'reportingUser']),
            ], 201);
        }

        return redirect()->route('developers.show', $developer)
            ->with('success', 'Developer created successfully.');
    }

    /**
     * Display the specified developer.
     */
    public function show(Request $request, Developer $developer): View|JsonResponse
    {
        $this->authorize('view', $developer);

        $developer->load(['category', 'reportingUser']);

        $canViewSensitive = $request->user()?->can('viewSensitive', $developer) ?? false;

        if (!$canViewSensitive) {
            $developer->makeHidden(['salary', 'phone', 'email']);
        }

        if ($request->wantsJson()) {
            return response()->json($developer);
        }

        return view('developers.show', [
            'developer' => $developer,
            'canViewSensitive' => $canViewSensitive,
        ]);
    }

    /**
     * Show the form for editing the specified developer.
     */
    public function edit(Developer $developer): View
    {
        $this->authorize('update', $developer);

        return view('developers.edit', [
            'developer' => $developer,
            'categories' => Devcat::orderBy('name')->get(),
            'managers' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified developer in storage.
     */
    public function update(UpdateDeveloperRequest $request, Developer $developer): RedirectResponse|JsonResponse
    {
        $developer->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Developer updated successfully.',
                'developer' => $developer->fresh(['category', 'reportingUser']),
            ]);
        }

        return redirect()->route('developers.show', $developer)
            ->with('success', 'Developer updated successfully.');
    }

    /**
     * Remove the specified developer from storage.
     */
    public function destroy(Request $request, Developer $developer): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $developer);

        $developer->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Developer deleted successfully.']);
        }

        return redirect()->route('developers.index')
            ->with('success', 'Developer deleted successfully.');
    }

    /**
     * Filter helper for active developers.
     */
    public function active(Request $request): View|JsonResponse
    {
        $request->merge(['status' => 'active']);
        return $this->index($request);
    }

    /**
     * Filter helper for discontinued developers.
     */
    public function discontinued(Request $request): View|JsonResponse
    {
        $request->merge(['status' => 'discontinued']);
        return $this->index($request);
    }
}

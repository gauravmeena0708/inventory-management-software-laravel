<?php

namespace App\Http\Controllers;

use App\Contracts\TabularExporter;
use App\Exports\AgreementsExport;
use App\Http\Requests\StoreAgreementRequest;
use App\Http\Requests\UpdateAgreementRequest;
use App\Models\Agreement;
use App\Models\FileRecord;
use App\Services\Agreements\GeneratePaymentScheduleAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgreementController extends Controller
{
    /**
     * Display a listing of agreements.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Agreement::class);

        $query = Agreement::query()->withCount('payments');

        if ($request->input('filter') === 'expired') {
            $query->expired();
        } elseif ($request->input('filter') === 'due' || $request->input('filter') === 'expiring_soon') {
            $query->expiringSoon(180);
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('agency', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%");
            });
        }

        $agreements = $query->latest('id')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($agreements);
        }

        return view('agreements.index', [
            'agreements' => $agreements,
            'filters' => $request->only(['filter', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new agreement.
     */
    public function create(): View
    {
        $this->authorize('create', Agreement::class);

        return view('agreements.create', [
            'files' => FileRecord::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created agreement and automatically generate its payment schedule.
     */
    public function store(
        StoreAgreementRequest $request,
        GeneratePaymentScheduleAction $scheduleAction
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $agreement = Agreement::create($validated);

        // Automatically generate idempotent milestone payment schedule if anchor date & interval are defined
        if ($agreement->billing_anchor_date && $agreement->billing_interval_months && $agreement->expiry) {
            $scheduleAction->execute($agreement);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Agreement created and payment schedule generated successfully.',
                'agreement' => $agreement->fresh(['payments']),
            ], 201);
        }

        return redirect()->route('agreements.show', $agreement)
            ->with('success', 'Agreement and payment schedule created successfully.');
    }

    /**
     * Display the specified agreement.
     */
    public function show(Request $request, Agreement $agreement): View|JsonResponse
    {
        $this->authorize('view', $agreement);

        $agreement->load([
            'payments' => fn ($q) => $q->orderBy('due_date'),
            'file',
            'attachments',
        ]);

        if ($request->wantsJson()) {
            return response()->json($agreement);
        }

        return view('agreements.show', ['agreement' => $agreement]);
    }

    /**
     * Show the form for editing the specified agreement.
     */
    public function edit(Agreement $agreement): View
    {
        $this->authorize('update', $agreement);

        return view('agreements.edit', [
            'agreement' => $agreement,
            'files' => FileRecord::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified agreement in storage.
     */
    public function update(UpdateAgreementRequest $request, Agreement $agreement): RedirectResponse|JsonResponse
    {
        $agreement->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Agreement updated successfully.',
                'agreement' => $agreement->fresh(),
            ]);
        }

        return redirect()->route('agreements.show', $agreement)
            ->with('success', 'Agreement updated successfully.');
    }

    /**
     * Remove the specified agreement from storage.
     */
    public function destroy(Request $request, Agreement $agreement): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $agreement);

        $agreement->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Agreement deleted successfully.']);
        }

        return redirect()->route('agreements.index')
            ->with('success', 'Agreement deleted successfully.');
    }

    /**
     * Export agreements to an Excel spreadsheet.
     */
    public function export(Request $request, TabularExporter $exporter): BinaryFileResponse
    {
        $this->authorize('export', Agreement::class);

        $query = Agreement::query();

        if ($request->input('filter') === 'expired') {
            $query->expired();
        } elseif ($request->input('filter') === 'due' || $request->input('filter') === 'expiring_soon') {
            $query->expiringSoon(180);
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('agency', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%");
            });
        }

        $filename = 'agreements-' . now()->format('Y-m-d-His') . '.xlsx';

        return $exporter->download(new AgreementsExport($query), $filename);
    }
}

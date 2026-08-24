<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreFileRecordRequest;
use App\Http\Requests\UpdateFileRecordRequest;
use App\Models\FileRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FileRecordController extends Controller
{
    /**
     * Display a listing of file records.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->canViewInventory()) {
            abort(403, 'Unauthorized to view file records.');
        }

        $query = FileRecord::query()->with('attachments');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('efile_number', 'like', "%{$term}%")
                    ->orWhere('physical_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('division')) {
            $query->where('division', $request->input('division'));
        }

        $files = $query->latest('id')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($files);
        }

        return view('files.index', [
            'files' => $files,
            'filters' => $request->only(['search', 'division']),
        ]);
    }

    /**
     * Show the form for creating a new file record.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR)) {
            abort(403, 'Unauthorized to create file records.');
        }

        return view('files.create');
    }

    /**
     * Store a newly created file record in storage.
     */
    public function store(StoreFileRecordRequest $request): RedirectResponse|JsonResponse
    {
        $fileRecord = FileRecord::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'File record created successfully.',
                'file' => $fileRecord,
            ], 201);
        }

        return redirect()->route('files.show', $fileRecord)
            ->with('success', 'File record created successfully.');
    }

    /**
     * Display the specified file record.
     */
    public function show(Request $request, FileRecord $file): View|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->canViewInventory()) {
            abort(403, 'Unauthorized to view file records.');
        }

        $file->load('attachments');

        if ($request->wantsJson()) {
            return response()->json($file);
        }

        return view('files.show', ['file' => $file]);
    }

    /**
     * Show the form for editing the specified file record.
     */
    public function edit(Request $request, FileRecord $file): View
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR)) {
            abort(403, 'Unauthorized to edit file records.');
        }

        return view('files.edit', ['file' => $file]);
    }

    /**
     * Update the specified file record in storage.
     */
    public function update(UpdateFileRecordRequest $request, FileRecord $file): RedirectResponse|JsonResponse
    {
        $file->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'File record updated successfully.',
                'file' => $file->fresh(),
            ]);
        }

        return redirect()->route('files.show', $file)
            ->with('success', 'File record updated successfully.');
    }

    /**
     * Remove the specified file record from storage.
     */
    public function destroy(Request $request, FileRecord $file): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to delete file records.');
        }

        $file->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'File record deleted successfully.']);
        }

        return redirect()->route('files.index')
            ->with('success', 'File record deleted successfully.');
    }
}

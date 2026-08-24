<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\FileRecord;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::query()->with(['assignedUser', 'file']);

        $status = $request->input('status');
        if ($status === 'pending') {
            $query->pending();
        } elseif ($status === 'completed') {
            $query->completed();
        } elseif ($request->filled('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $tasks = $query->latest('id')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($tasks);
        }

        return view('tasks.index', [
            'tasks' => $tasks,
            'users' => User::orderBy('name')->get(),
            'filters' => $request->only(['status', 'priority', 'assigned_to', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(): View
    {
        $this->authorize('create', Task::class);

        return view('tasks.create', [
            'users' => User::orderBy('name')->get(),
            'files' => FileRecord::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(StoreTaskRequest $request): RedirectResponse|JsonResponse
    {
        $task = Task::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Task created successfully.',
                'task' => $task->fresh(['assignedUser', 'file']),
            ], 201);
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, Task $task): View|JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['assignedUser', 'file']);

        if ($request->wantsJson()) {
            return response()->json($task);
        }

        return view('tasks.show', ['task' => $task]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task,
            'users' => User::orderBy('name')->get(),
            'files' => FileRecord::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse|JsonResponse
    {
        $task->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Task updated successfully.',
                'task' => $task->fresh(['assignedUser', 'file']),
            ]);
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Task deleted successfully.']);
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    /**
     * Filter helper for pending tasks.
     */
    public function pending(Request $request): View|JsonResponse
    {
        $request->merge(['status' => 'pending']);
        return $this->index($request);
    }

    /**
     * Filter helper for completed tasks.
     */
    public function completed(Request $request): View|JsonResponse
    {
        $request->merge(['status' => 'completed']);
        return $this->index($request);
    }
}

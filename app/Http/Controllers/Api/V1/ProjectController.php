<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionEnum::PROJECTS_VIEW->value)->only(['index', 'show']);
        $this->middleware('permission:'.PermissionEnum::PROJECTS_CREATE->value)->only(['store', 'storeTask']);
        $this->middleware('permission:'.PermissionEnum::PROJECTS_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::PROJECTS_DELETE->value)->only('destroy');
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Project::query()->with('tasks')->latest()->get(),
        ]);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(['data' => $project->load('tasks')]);
    }

    public function store(Request $request): JsonResponse
    {
        $project = Project::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['data' => $project], 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $project->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['data' => $project->refresh()]);
    }

    public function storeTask(Request $request, Project $project): JsonResponse
    {
        $task = $project->tasks()->create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'due_on' => ['nullable', 'date'],
        ]));

        return response()->json(['data' => $task], 201);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }
}

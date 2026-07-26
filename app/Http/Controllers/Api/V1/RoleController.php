<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService)
    {
        // Kept for reads that don't have a dedicated Form Request.
        $this->middleware('permission:' . PermissionEnum::ROLES_VIEW->value)->only(['index', 'show', 'permissionMatrix']);
        $this->middleware('permission:' . PermissionEnum::ROLES_DELETE->value)->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleService->list($request->string('search')->value() ?: null);

        return response()->json([
            'data' => RoleResource::collection($roles),
        ]);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions:id,name')->loadCount('users');

        return response()->json(['data' => new RoleResource($role)]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create(
            $request->validated('name'),
            $request->validated('permissions', [])
        );

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => new RoleResource($role),
        ], 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->roleService->update(
            $role,
            $request->validated('name'),
            $request->validated('permissions', $role->permissions->pluck('name')->all())
        );

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => new RoleResource($role),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->roleService->delete($role);

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    /**
     * Returns every permission grouped by module — powers the
     * checkbox-matrix UI when creating/editing a role.
     */
    public function permissionMatrix(): JsonResponse
    {
        return response()->json(['data' => $this->roleService->allPermissionsGrouped()]);
    }
}
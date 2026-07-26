<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserAccessController extends Controller
{
    public function __construct(private readonly UserAccessService $userAccessService)
    {
        $this->middleware('permission:' . PermissionEnum::ROLES_ASSIGN->value)->only('syncRoles');
        $this->middleware('permission:' . PermissionEnum::PERMISSIONS_ASSIGN->value)->only('syncDirectPermissions');
    }

    public function syncRoles(AssignRoleRequest $request, User $user): JsonResponse
    {
        $user = $this->userAccessService->syncRoles($user, $request->validated('roles'));

        return response()->json([
            'message' => 'Roles updated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    public function syncDirectPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $user = $this->userAccessService->syncDirectPermissions($user, $validated['permissions']);

        return response()->json([
            'message' => 'Permissions updated successfully.',
            'data' => new UserResource($user),
        ]);
    }
}
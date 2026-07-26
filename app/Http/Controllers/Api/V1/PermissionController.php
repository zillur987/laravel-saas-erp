<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:' . PermissionEnum::PERMISSIONS_VIEW->value);
    }

    public function index(): JsonResponse
    {
        $permissions = Permission::query()->orderBy('name')->get();

        return response()->json([
            'data' => PermissionResource::collection($permissions),
        ]);
    }
}
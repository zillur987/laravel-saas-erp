<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedUserController extends Controller
{
    /**
     * GET /api/v1/me
     * The frontend calls this once on app boot (and after login) to hydrate
     * the auth store with the user's roles + flattened permission list.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }
}
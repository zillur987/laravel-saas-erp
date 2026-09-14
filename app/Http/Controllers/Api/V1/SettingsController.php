<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionEnum::SETTINGS_VIEW->value)->only('show');
        $this->middleware('permission:'.PermissionEnum::SETTINGS_UPDATE->value)->only('update');
    }

    public function show(): JsonResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : request()->user()?->tenant;

        return response()->json([
            'data' => [
                'name' => $tenant?->name,
                'subdomain' => $tenant?->subdomain,
                'plan' => $tenant?->plan,
                'subscribed' => $tenant?->isSubscribed() ?? false,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : $request->user()?->tenant;
        abort_unless($tenant, 404, 'Tenant not found');

        $tenant->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'plan' => ['sometimes', 'string', 'max:50'],
        ]));

        return response()->json(['data' => $tenant->fresh()]);
    }
}

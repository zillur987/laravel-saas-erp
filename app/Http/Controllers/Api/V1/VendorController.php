<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionEnum::PURCHASE_VIEW->value)->only(['index', 'show']);
        $this->middleware('permission:'.PermissionEnum::PURCHASE_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::PURCHASE_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::PURCHASE_DELETE->value)->only('destroy');
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => Vendor::query()->latest()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = Vendor::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]));

        return response()->json(['message' => 'Vendor created.', 'data' => $vendor], 201);
    }

    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $vendor->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]));

        return response()->json(['data' => $vendor->refresh()]);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted.']);
    }
}

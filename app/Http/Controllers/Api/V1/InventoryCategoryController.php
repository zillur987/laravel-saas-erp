<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InventoryCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionEnum::INVENTORY_VIEW->value)->only('index');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_DELETE->value)->only('destroy');
    }

    public function index(): JsonResponse
    {
        $categories = InventoryCategory::query()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $category = InventoryCategory::create($this->validated($request));

        return response()->json(['message' => 'Category created.', 'data' => $category], 201);
    }

    public function update(Request $request, InventoryCategory $inventoryCategory): JsonResponse
    {
        $inventoryCategory->update($this->validated($request, $inventoryCategory));

        return response()->json(['message' => 'Category updated.', 'data' => $inventoryCategory->refresh()]);
    }

    public function destroy(InventoryCategory $inventoryCategory): JsonResponse
    {
        $inventoryCategory->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?InventoryCategory $category = null): array
    {
        $tenantId = app()->bound('tenant') ? app('tenant')->id : null;
        $name = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ])['name'];

        $slug = Str::slug($name);

        $request->merge(['slug' => $slug]);
        $request->validate([
            'slug' => [
                Rule::unique('inventory_categories', 'slug')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($category?->id),
            ],
        ]);

        return ['name' => $name, 'slug' => $slug];
    }
}

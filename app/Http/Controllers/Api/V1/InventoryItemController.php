<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryItemController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
        $this->middleware('permission:'.PermissionEnum::INVENTORY_VIEW->value)->only(['index', 'show', 'lookup', 'adjustments', 'lowStock']);
        $this->middleware('permission:'.PermissionEnum::INVENTORY_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_DELETE->value)->only('destroy');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_ADJUST_STOCK->value)->only('adjust');
        $this->middleware('permission:'.PermissionEnum::INVENTORY_EXPORT->value)->only('export');
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->inventoryService->paginate([
            'search' => $request->string('search')->value() ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'low_stock' => $request->boolean('low_stock'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], $request->integer('per_page') ?: 15);

        return response()->json(['data' => $items->items(), 'meta' => $this->meta($items)]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $query = $request->validate(['q' => ['required', 'string', 'max:64']])['q'];
        $item = $this->inventoryService->lookup($query);

        if (! $item) {
            return response()->json(['message' => 'Product not found.', 'data' => null], 404);
        }

        return response()->json(['data' => $item->load('category')]);
    }

    public function lowStock(): JsonResponse
    {
        $items = InventoryItem::query()->with('category:id,name')->lowStock()->orderBy('quantity')->get();

        return response()->json(['data' => $items]);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $rows = $this->inventoryService->adjustments(
            $request->integer('inventory_item_id') ?: null,
            $request->integer('per_page') ?: 20
        );

        return response()->json(['data' => $rows->items(), 'meta' => $this->meta($rows)]);
    }

    public function export(): StreamedResponse
    {
        return $this->inventoryService->exportCsv();
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json([
            'data' => $inventoryItem->load(['category', 'adjustments' => fn ($q) => $q->latest()->limit(50)]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $item = $this->inventoryService->create($request->validate($this->rules()), $request->user()?->id);

        return response()->json(['message' => 'Item created.', 'data' => $item], 201);
    }

    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $item = $this->inventoryService->update($inventoryItem, $request->validate($this->rules(false, $inventoryItem)));

        return response()->json(['message' => 'Item updated.', 'data' => $item]);
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $this->inventoryService->delete($inventoryItem);

        return response()->json(['message' => 'Item deleted.']);
    }

    public function adjust(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:adjustment,damage,count,return'],
        ]);

        $item = $this->inventoryService->adjustStock(
            $inventoryItem,
            (int) $validated['quantity_delta'],
            $validated['reason'] ?? null,
            $request->user()?->id,
            $validated['type'] ?? 'adjustment'
        );

        return response()->json(['message' => 'Stock adjusted.', 'data' => $item]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $creating = true, ?InventoryItem $item = null): array
    {
        $tenantId = app()->bound('tenant') ? app('tenant')->id : null;

        return [
            'sku' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:64',
                Rule::unique('inventory_items', 'sku')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($item?->id),
            ],
            'barcode' => ['nullable', 'string', 'max:64'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'quantity' => [$creating ? 'sometimes' : 'prohibited', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function meta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}

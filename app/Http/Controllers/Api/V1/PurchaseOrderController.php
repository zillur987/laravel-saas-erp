<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrderService)
    {
        $this->middleware('permission:'.PermissionEnum::PURCHASE_VIEW->value)->only(['index', 'show']);
        $this->middleware('permission:'.PermissionEnum::PURCHASE_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::PURCHASE_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::PURCHASE_DELETE->value)->only('destroy');
        $this->middleware('permission:'.PermissionEnum::PURCHASE_APPROVE->value)->only('approve');
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->purchaseOrderService->paginate($request->string('search')->value() ?: null);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(['data' => $purchaseOrder->load(['vendor', 'items'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $order = $this->purchaseOrderService->create($this->validated($request), $request->user()?->id);

        return response()->json(['message' => 'Purchase order created.', 'data' => $order], 201);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $order = $this->purchaseOrderService->update($purchaseOrder, $this->validated($request, false));

        return response()->json(['message' => 'Purchase order updated.', 'data' => $order]);
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json([
            'message' => 'Purchase order approved.',
            'data' => $this->purchaseOrderService->approve($purchaseOrder, $request->user()?->id),
        ]);
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->purchaseOrderService->delete($purchaseOrder);

        return response()->json(['message' => 'Purchase order deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'notes' => ['nullable', 'string'],
            'items' => [$creating ? 'required' : 'sometimes', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required_with:items', 'numeric', 'min:0'],
        ]);
    }
}

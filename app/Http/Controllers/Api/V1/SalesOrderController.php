<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(private readonly SalesOrderService $salesOrderService)
    {
        $this->middleware('permission:'.PermissionEnum::SALES_VIEW->value)->only(['index', 'show']);
        $this->middleware('permission:'.PermissionEnum::SALES_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::SALES_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::SALES_DELETE->value)->only('destroy');
        $this->middleware('permission:'.PermissionEnum::SALES_APPROVE->value)->only('approve');
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->salesOrderService->paginate($request->string('search')->value() ?: null);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(SalesOrder $salesOrder): JsonResponse
    {
        return response()->json(['data' => $salesOrder->load(['customer', 'items'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $order = $this->salesOrderService->create($this->validated($request), $request->user()?->id);

        return response()->json(['message' => 'Sales order created.', 'data' => $order], 201);
    }

    public function update(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $order = $this->salesOrderService->update($salesOrder, $this->validated($request, false));

        return response()->json(['message' => 'Sales order updated.', 'data' => $order]);
    }

    public function approve(SalesOrder $salesOrder): JsonResponse
    {
        return response()->json([
            'message' => 'Sales order approved.',
            'data' => $this->salesOrderService->approve($salesOrder),
        ]);
    }

    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $this->salesOrderService->delete($salesOrder);

        return response()->json(['message' => 'Sales order deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
            'items' => [$creating ? 'required' : 'sometimes', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ]);
    }
}

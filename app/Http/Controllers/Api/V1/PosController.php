<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Services\InventoryService;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        private readonly PosService $posService,
        private readonly InventoryService $inventoryService
    ) {
        $this->middleware('permission:'.PermissionEnum::POS_VIEW->value)->only(['catalog', 'index', 'show', 'summary']);
        $this->middleware('permission:'.PermissionEnum::POS_SELL->value)->only('checkout');
        $this->middleware('permission:'.PermissionEnum::POS_VOID->value)->only('void');
    }

    public function catalog(Request $request): JsonResponse
    {
        $items = $this->inventoryService->catalog(
            $request->string('search')->value() ?: null,
            $request->integer('category_id') ?: null
        );

        return response()->json(['data' => $items]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $sale = $this->posService->checkout($request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,card,mobile'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ]), $request->user()?->id);

        return response()->json(['message' => 'Sale completed.', 'data' => $sale], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $sales = $this->posService->paginate(
            $request->string('search')->value() ?: null,
            $request->string('status')->value() ?: null
        );

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    public function show(PosSale $posSale): JsonResponse
    {
        return response()->json([
            'data' => $posSale->load(['customer', 'cashier:id,name', 'items', 'payments']),
        ]);
    }

    public function void(PosSale $posSale, Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Sale voided and stock restored.',
            'data' => $this->posService->void($posSale, $request->user()?->id),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->posService->summary($validated['from'] ?? null, $validated['to'] ?? null),
        ]);
    }
}

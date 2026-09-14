<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Support\DocumentNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['vendor:id,name', 'items'])
            ->when($search, fn ($q) => $q->where('number', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, ?int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'] ?? [];
            $total = $this->totals($items);

            $order = PurchaseOrder::create([
                'vendor_id' => $data['vendor_id'] ?? null,
                'created_by' => $userId,
                'number' => DocumentNumber::next('PO', new PurchaseOrder),
                'status' => 'pending',
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($order, $items);

            return $order->load(['vendor', 'items']);
        });
    }

    public function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Approved purchase orders cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? $order->items->toArray();
            $total = $this->totals($items);

            $order->update([
                'vendor_id' => $data['vendor_id'] ?? $order->vendor_id,
                'notes' => $data['notes'] ?? $order->notes,
                'total' => $total,
            ]);

            if (isset($data['items'])) {
                $order->items()->delete();
                $this->syncItems($order, $data['items']);
            }

            return $order->load(['vendor', 'items']);
        });
    }

    public function approve(PurchaseOrder $order, ?int $userId = null): PurchaseOrder
    {
        if ($order->status === 'approved') {
            return $order->load(['vendor', 'items']);
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->load('items');

            foreach ($order->items as $line) {
                if ($line->inventory_item_id) {
                    $item = InventoryItem::query()->find($line->inventory_item_id);
                    if ($item) {
                        $this->inventoryService->receive(
                            $item,
                            (int) $line->quantity,
                            'purchase',
                            $userId,
                            $order->number
                        );
                    }
                }
            }

            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            return $order->refresh()->load(['vendor', 'items']);
        });
    }

    public function delete(PurchaseOrder $order): void
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Approved purchase orders cannot be deleted.',
            ]);
        }

        $order->delete();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncItems(PurchaseOrder $order, array $items): void
    {
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $cost = (float) ($item['unit_cost'] ?? 0);
            $order->items()->create([
                'inventory_item_id' => $item['inventory_item_id'] ?? null,
                'description' => $item['description'] ?? 'Item',
                'quantity' => $qty,
                'unit_cost' => $cost,
                'line_total' => $qty * $cost,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function totals(array $items): float
    {
        return collect($items)->reduce(function ($carry, $item) {
            return $carry + ((int) ($item['quantity'] ?? 1) * (float) ($item['unit_cost'] ?? 0));
        }, 0.0);
    }
}

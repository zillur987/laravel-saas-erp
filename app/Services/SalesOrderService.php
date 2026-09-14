<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SalesOrder;
use App\Support\DocumentNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderService
{
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return SalesOrder::query()
            ->with(['customer:id,name', 'items'])
            ->when($search, fn ($q) => $q->where('number', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, ?int $userId): SalesOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'] ?? [];
            $totals = $this->totals($items);

            $order = SalesOrder::create([
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => $userId,
                'number' => DocumentNumber::next('SO', new SalesOrder),
                'status' => 'pending',
                'subtotal' => $totals,
                'total' => $totals,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($order, $items);

            return $order->load(['customer', 'items']);
        });
    }

    public function update(SalesOrder $order, array $data): SalesOrder
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Approved orders cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? $order->items->toArray();
            $totals = $this->totals($items);

            $order->update([
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'notes' => $data['notes'] ?? $order->notes,
                'subtotal' => $totals,
                'total' => $totals,
            ]);

            if (isset($data['items'])) {
                $order->items()->delete();
                $this->syncItems($order, $data['items']);
            }

            return $order->load(['customer', 'items']);
        });
    }

    public function approve(SalesOrder $order): SalesOrder
    {
        if ($order->status === 'approved') {
            return $order;
        }

        $order->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return $order->refresh()->load(['customer', 'items']);
    }

    public function delete(SalesOrder $order): void
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Approved orders cannot be deleted.',
            ]);
        }

        $order->delete();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncItems(SalesOrder $order, array $items): void
    {
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $order->items()->create([
                'inventory_item_id' => $item['inventory_item_id'] ?? null,
                'description' => $item['description'] ?? 'Item',
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $qty * $price,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function totals(array $items): float
    {
        return collect($items)->reduce(function ($carry, $item) {
            return $carry + ((int) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0));
        }, 0.0);
    }
}

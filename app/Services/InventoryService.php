<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return InventoryItem::query()
            ->with('category:id,name,slug')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when(($filters['low_stock'] ?? null) === true || ($filters['low_stock'] ?? null) === '1', fn ($q) => $q->lowStock())
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->paginate($perPage);
    }

    public function catalog(?string $search = null, ?int $categoryId = null): Collection
    {
        return InventoryItem::query()
            ->with('category:id,name')
            ->active()
            ->when($search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', $search);
                });
            })
            ->when($categoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    public function lookup(string $query): ?InventoryItem
    {
        return InventoryItem::query()
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('sku', $query)->orWhere('barcode', $query);
            })
            ->first();
    }

    public function create(array $data, ?int $userId = null): InventoryItem
    {
        return DB::transaction(function () use ($data, $userId) {
            $item = InventoryItem::create($data);

            if (($item->quantity ?? 0) > 0) {
                $this->recordMovement($item, (int) $item->quantity, 'initial', 'Opening stock', $userId, $item->sku);
            }

            return $item->load('category');
        });
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        unset($data['quantity']);
        $item->update($data);

        return $item->refresh()->load('category');
    }

    public function delete(InventoryItem $item): void
    {
        $item->delete();
    }

    public function adjustStock(
        InventoryItem $item,
        int $delta,
        ?string $reason,
        ?int $userId,
        string $type = 'adjustment',
        ?string $reference = null
    ): InventoryItem {
        return $this->applyDelta($item, $delta, $type, $reason, $userId, $reference);
    }

    public function deduct(InventoryItem $item, int $quantity, string $type, ?int $userId, ?string $reference = null): InventoryItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        return $this->applyDelta($item, -$quantity, $type, ucfirst($type), $userId, $reference);
    }

    public function receive(InventoryItem $item, int $quantity, string $type, ?int $userId, ?string $reference = null): InventoryItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        return $this->applyDelta($item, $quantity, $type, ucfirst($type), $userId, $reference);
    }

    public function adjustments(?int $itemId = null, int $perPage = 20): LengthAwarePaginator
    {
        return StockAdjustment::query()
            ->with(['item:id,sku,name', 'user:id,name'])
            ->when($itemId, fn ($q, $id) => $q->where('inventory_item_id', $id))
            ->latest()
            ->paginate($perPage);
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'inventory-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Barcode', 'Name', 'Category', 'Quantity', 'Reorder Level', 'Unit Cost', 'Unit Price', 'Tax Rate', 'Active']);

            InventoryItem::query()->with('category:id,name')->orderBy('name')->chunk(200, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->sku,
                        $item->barcode,
                        $item->name,
                        $item->category?->name,
                        $item->quantity,
                        $item->reorder_level,
                        $item->unit_cost,
                        $item->unit_price,
                        $item->tax_rate,
                        $item->is_active ? 'yes' : 'no',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function applyDelta(
        InventoryItem $item,
        int $delta,
        string $type,
        ?string $reason,
        ?int $userId,
        ?string $reference
    ): InventoryItem {
        if ($delta === 0) {
            throw ValidationException::withMessages([
                'quantity_delta' => 'Quantity change cannot be zero.',
            ]);
        }

        return DB::transaction(function () use ($item, $delta, $type, $reason, $userId, $reference) {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            if ($locked->quantity + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock for {$locked->name} (available: {$locked->quantity}).",
                ]);
            }

            $locked->increment('quantity', $delta);
            $this->recordMovement($locked, $delta, $type, $reason, $userId, $reference);

            return $locked->refresh()->load('category');
        });
    }

    private function recordMovement(
        InventoryItem $item,
        int $delta,
        string $type,
        ?string $reason,
        ?int $userId,
        ?string $reference
    ): void {
        StockAdjustment::create([
            'inventory_item_id' => $item->id,
            'user_id' => $userId,
            'quantity_delta' => $delta,
            'type' => $type,
            'reason' => $reason,
            'reference' => $reference,
        ]);
    }
}

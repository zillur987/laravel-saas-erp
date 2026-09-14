<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PosSale;
use App\Support\DocumentNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function paginate(?string $search = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return PosSale::query()
            ->with(['customer:id,name', 'cashier:id,name', 'items', 'payments'])
            ->when($search, fn ($q, $search) => $q->where('number', 'like', "%{$search}%"))
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function checkout(array $data, ?int $userId): PosSale
    {
        return DB::transaction(function () use ($data, $userId) {
            $lines = $this->buildLines($data['items'] ?? []);
            $discountTotal = round((float) ($data['discount_total'] ?? 0), 2);
            $subtotal = collect($lines)->sum('net');
            $taxTotal = collect($lines)->sum('tax_amount');
            $total = max(0, round($subtotal + $taxTotal - $discountTotal, 2));

            $payments = $data['payments'] ?? [];
            $amountPaid = round(collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0)), 2);

            if ($amountPaid + 0.001 < $total) {
                throw ValidationException::withMessages([
                    'payments' => 'Payment is less than the sale total.',
                ]);
            }

            $sale = PosSale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'cashier_id' => $userId,
                'number' => DocumentNumber::next('POS', new PosSale),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'change_due' => max(0, round($amountPaid - $total, 2)),
                'notes' => $data['notes'] ?? null,
                'sold_at' => now(),
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'inventory_item_id' => $line['inventory_item_id'],
                    'sku' => $line['sku'],
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_rate' => $line['tax_rate'],
                    'discount' => $line['discount'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['line_total'],
                ]);

                $this->inventoryService->deduct(
                    $line['item'],
                    $line['quantity'],
                    'sale',
                    $userId,
                    $sale->number
                );
            }

            foreach ($payments as $payment) {
                $sale->payments()->create([
                    'method' => $payment['method'] ?? 'cash',
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                ]);
            }

            return $sale->load(['customer', 'cashier:id,name', 'items', 'payments']);
        });
    }

    public function void(PosSale $sale, ?int $userId): PosSale
    {
        if ($sale->status === 'voided') {
            throw ValidationException::withMessages(['status' => 'Sale is already voided.']);
        }

        return DB::transaction(function () use ($sale, $userId) {
            $sale->load('items.inventoryItem');

            foreach ($sale->items as $line) {
                if ($line->inventoryItem) {
                    $this->inventoryService->receive(
                        $line->inventoryItem,
                        (int) $line->quantity,
                        'void',
                        $userId,
                        $sale->number
                    );
                }
            }

            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
            ]);

            return $sale->refresh()->load(['customer', 'cashier:id,name', 'items', 'payments']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $query = PosSale::query()->where('status', 'completed');

        if ($from) {
            $query->whereDate('sold_at', '>=', $from);
        } else {
            $query->whereDate('sold_at', now()->toDateString());
        }

        if ($to) {
            $query->whereDate('sold_at', '<=', $to);
        }

        $sales = $query->with('payments')->get();

        return [
            'count' => $sales->count(),
            'subtotal' => round((float) $sales->sum('subtotal'), 2),
            'tax_total' => round((float) $sales->sum('tax_total'), 2),
            'discount_total' => round((float) $sales->sum('discount_total'), 2),
            'total' => round((float) $sales->sum('total'), 2),
            'by_method' => $sales->flatMap->payments
                ->groupBy('method')
                ->map(fn ($group) => round((float) $group->sum('amount'), 2)),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function buildLines(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one item is required.']);
        }

        $lines = [];

        foreach ($items as $index => $row) {
            /** @var InventoryItem $item */
            $item = InventoryItem::query()->lockForUpdate()->find($row['inventory_item_id'] ?? null);

            if (! $item || ! $item->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.inventory_item_id" => 'Product is not available.',
                ]);
            }

            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be at least 1.',
                ]);
            }

            if ($item->quantity < $qty) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => "Insufficient stock for {$item->name} (available: {$item->quantity}).",
                ]);
            }

            $unitPrice = isset($row['unit_price']) ? (float) $row['unit_price'] : (float) $item->unit_price;
            $discount = round((float) ($row['discount'] ?? 0), 2);
            $taxRate = isset($row['tax_rate']) ? (float) $row['tax_rate'] : (float) $item->tax_rate;
            $net = max(0, round(($qty * $unitPrice) - $discount, 2));
            $taxAmount = round($net * ($taxRate / 100), 2);

            $lines[] = [
                'item' => $item,
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount' => $discount,
                'net' => $net,
                'tax_amount' => $taxAmount,
                'line_total' => round($net + $taxAmount, 2),
            ];
        }

        return $lines;
    }
}

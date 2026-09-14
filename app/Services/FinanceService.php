<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\DocumentNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function paginateInvoices(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['customer:id,name', 'payments'])
            ->when($search, fn ($q) => $q->where('number', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            $total = collect($items)->reduce(
                fn ($carry, $item) => $carry + ((int) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0)),
                0.0
            );

            $invoice = Invoice::create([
                'customer_id' => $data['customer_id'] ?? null,
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'number' => DocumentNumber::next('INV', new Invoice),
                'status' => 'open',
                'total' => $total,
                'due_date' => $data['due_date'] ?? null,
            ]);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $invoice->items()->create([
                    'description' => $item['description'] ?? 'Line',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $qty * $price,
                ]);
            }

            return $invoice->load(['customer', 'items', 'payments']);
        });
    }

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'method' => $data['method'] ?? 'cash',
                'status' => $data['status'] ?? 'completed',
                'reference' => $data['reference'] ?? null,
            ]);

            $invoice->increment('amount_paid', (float) $data['amount']);
            $invoice->refresh();

            if ($invoice->amount_paid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            }

            return $payment;
        });
    }

    public function deleteInvoice(Invoice $invoice): void
    {
        $invoice->delete();
    }
}

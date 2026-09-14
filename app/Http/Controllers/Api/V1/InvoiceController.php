<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
        $this->middleware('permission:'.PermissionEnum::FINANCE_VIEW->value)->only(['index', 'show']);
        $this->middleware('permission:'.PermissionEnum::FINANCE_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::FINANCE_DELETE->value)->only('destroy');
        $this->middleware('permission:'.PermissionEnum::FINANCE_APPROVE_PAYMENT->value)->only('storePayment');
    }

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->financeService->paginateInvoices($request->string('search')->value() ?: null);

        return response()->json([
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => $invoice->load(['customer', 'items', 'payments'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $invoice = $this->financeService->createInvoice($request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'due_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]));

        return response()->json(['message' => 'Invoice created.', 'data' => $invoice], 201);
    }

    public function storePayment(Request $request, Invoice $invoice): JsonResponse
    {
        $payment = $this->financeService->recordPayment($invoice, $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['sometimes', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string'],
        ]));

        return response()->json(['message' => 'Payment recorded.', 'data' => $payment], 201);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->financeService->deleteInvoice($invoice);

        return response()->json(['message' => 'Invoice deleted.']);
    }
}

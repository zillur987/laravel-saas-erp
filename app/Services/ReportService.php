<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\SalesOrder;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'inventory_items' => InventoryItem::query()->count(),
            'low_stock' => InventoryItem::query()->whereColumn('quantity', '<=', 'reorder_level')->count(),
            'open_sales_orders' => SalesOrder::query()->where('status', '!=', 'approved')->count(),
            'sales_total' => (float) SalesOrder::query()->where('status', 'approved')->sum('total'),
            'unpaid_invoices' => Invoice::query()->where('status', '!=', 'paid')->count(),
            'customers' => Customer::query()->count(),
            'employees' => Employee::query()->count(),
            'active_projects' => Project::query()->where('status', 'active')->count(),
        ];
    }
}

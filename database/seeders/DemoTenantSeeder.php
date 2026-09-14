<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Module;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'demo'],
            ['name' => 'Demo Company', 'plan' => 'free']
        );

        app()->instance('tenant', $tenant);

        $admin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin',
                'password' => 'password',
            ]
        );

        if (! $admin->tenant_id) {
            $admin->update(['tenant_id' => $tenant->id]);
        }

        $admin->syncRoles([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);

        $modules = [
            ['name' => 'Inventory', 'slug' => 'inventory', 'route_prefix' => '/inventory', 'sort_order' => 1],
            ['name' => 'POS', 'slug' => 'pos', 'route_prefix' => '/pos', 'sort_order' => 2],
            ['name' => 'Sales', 'slug' => 'sales', 'route_prefix' => '/sales', 'sort_order' => 3],
            ['name' => 'Purchasing', 'slug' => 'purchase', 'route_prefix' => '/purchasing', 'sort_order' => 4],
            ['name' => 'Finance', 'slug' => 'finance', 'route_prefix' => '/finance', 'sort_order' => 5],
            ['name' => 'HR', 'slug' => 'hr', 'route_prefix' => '/hr', 'sort_order' => 6],
            ['name' => 'CRM', 'slug' => 'crm', 'route_prefix' => '/crm', 'sort_order' => 7],
            ['name' => 'Projects', 'slug' => 'projects', 'route_prefix' => '/projects', 'sort_order' => 8],
            ['name' => 'Reports', 'slug' => 'reports', 'route_prefix' => '/reports', 'sort_order' => 9],
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate(['slug' => $module['slug']], $module + ['status' => true]);
        }

        $furniture = InventoryCategory::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'furniture'],
            ['name' => 'Furniture']
        );
        $supplies = InventoryCategory::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'office-supplies'],
            ['name' => 'Office Supplies']
        );

        $products = [
            ['sku' => 'SKU-1001', 'barcode' => '1001001', 'name' => 'Office Chair', 'quantity' => 25, 'reorder_level' => 5, 'unit_cost' => 40, 'unit_price' => 75, 'category_id' => $furniture->id, 'tax_rate' => 5],
            ['sku' => 'SKU-1002', 'barcode' => '1001002', 'name' => 'Standing Desk', 'quantity' => 12, 'reorder_level' => 3, 'unit_cost' => 180, 'unit_price' => 320, 'category_id' => $furniture->id, 'tax_rate' => 5],
            ['sku' => 'SKU-2001', 'barcode' => '2001001', 'name' => 'A4 Paper Ream', 'quantity' => 80, 'reorder_level' => 20, 'unit_cost' => 4, 'unit_price' => 8.5, 'category_id' => $supplies->id, 'tax_rate' => 0],
            ['sku' => 'SKU-2002', 'barcode' => '2001002', 'name' => 'Ballpoint Pen Pack', 'quantity' => 4, 'reorder_level' => 10, 'unit_cost' => 1.2, 'unit_price' => 3.5, 'category_id' => $supplies->id, 'tax_rate' => 0],
        ];

        foreach ($products as $product) {
            InventoryItem::firstOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $product['sku']],
                $product + ['is_active' => true]
            );
        }

        Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'buyer@example.com'],
            ['name' => 'Acme Buyer', 'status' => 'active', 'company' => 'Acme Ltd']
        );

        Vendor::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'North Supply'],
            ['email' => 'vendor@example.com']
        );

        Employee::firstOrCreate(
            ['tenant_id' => $tenant->id, 'employee_number' => 'EMP-0001'],
            [
                'name' => 'Jordan Lee',
                'email' => 'jordan@example.com',
                'department' => 'Operations',
                'position' => 'Coordinator',
                'salary' => 2500,
            ]
        );

        Project::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'ERP Rollout'],
            ['status' => 'active', 'description' => 'Internal implementation project']
        );
    }
}

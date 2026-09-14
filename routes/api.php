<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\InventoryCategoryController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\UserAccessController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware(['auth:api', 'tenant.from_user'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::get('/roles/permission-matrix', [RoleController::class, 'permissionMatrix']);
        Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
        Route::get('/permissions', [PermissionController::class, 'index']);

        Route::apiResource('users', UserController::class)->except(['create', 'edit']);
        Route::post('/users/{user}/roles', [UserAccessController::class, 'syncRoles']);
        Route::post('/users/{user}/permissions', [UserAccessController::class, 'syncDirectPermissions']);

        Route::get('/inventory/categories', [InventoryCategoryController::class, 'index']);
        Route::post('/inventory/categories', [InventoryCategoryController::class, 'store']);
        Route::put('/inventory/categories/{inventoryCategory}', [InventoryCategoryController::class, 'update']);
        Route::delete('/inventory/categories/{inventoryCategory}', [InventoryCategoryController::class, 'destroy']);

        Route::get('/inventory/products', [InventoryItemController::class, 'index']);
        Route::get('/inventory/products/lookup', [InventoryItemController::class, 'lookup']);
        Route::get('/inventory/products/low-stock', [InventoryItemController::class, 'lowStock']);
        Route::get('/inventory/adjustments', [InventoryItemController::class, 'adjustments']);
        Route::get('/inventory/export', [InventoryItemController::class, 'export']);
        Route::post('/inventory/products', [InventoryItemController::class, 'store'])
            ->middleware('permission:'.PermissionEnum::INVENTORY_CREATE->value);
        Route::get('/inventory/products/{inventoryItem}', [InventoryItemController::class, 'show']);
        Route::put('/inventory/products/{inventoryItem}', [InventoryItemController::class, 'update']);
        Route::delete('/inventory/products/{inventoryItem}', [InventoryItemController::class, 'destroy']);
        Route::post('/inventory/products/{inventoryItem}/adjust', [InventoryItemController::class, 'adjust']);

        Route::get('/pos/catalog', [PosController::class, 'catalog']);
        Route::post('/pos/checkout', [PosController::class, 'checkout']);
        Route::get('/pos/summary', [PosController::class, 'summary']);
        Route::get('/pos/sales', [PosController::class, 'index']);
        Route::get('/pos/sales/{posSale}', [PosController::class, 'show']);
        Route::post('/pos/sales/{posSale}/void', [PosController::class, 'void']);

        Route::get('/crm/customers', [CustomerController::class, 'index']);
        Route::post('/crm/customers', [CustomerController::class, 'store']);
        Route::get('/crm/customers/{customer}', [CustomerController::class, 'show']);
        Route::put('/crm/customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('/crm/customers/{customer}', [CustomerController::class, 'destroy']);

        Route::apiResource('sales/orders', SalesOrderController::class)
            ->parameters(['orders' => 'salesOrder'])
            ->except(['create', 'edit']);
        Route::post('/sales/orders/{salesOrder}/approve', [SalesOrderController::class, 'approve']);

        Route::apiResource('purchase/vendors', VendorController::class)->except(['create', 'edit', 'show']);
        Route::apiResource('purchase/orders', PurchaseOrderController::class)
            ->parameters(['orders' => 'purchaseOrder'])
            ->except(['create', 'edit']);
        Route::post('/purchase/orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);

        Route::get('/finance/invoices', [InvoiceController::class, 'index']);
        Route::post('/finance/invoices', [InvoiceController::class, 'store']);
        Route::get('/finance/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::delete('/finance/invoices/{invoice}', [InvoiceController::class, 'destroy']);
        Route::post('/finance/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment']);

        Route::get('/hr/employees', [EmployeeController::class, 'index']);
        Route::post('/hr/employees', [EmployeeController::class, 'store']);
        Route::put('/hr/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/hr/employees/{employee}', [EmployeeController::class, 'destroy']);
        Route::get('/hr/leaves', [EmployeeController::class, 'leaves']);
        Route::post('/hr/leaves', [EmployeeController::class, 'storeLeave']);
        Route::post('/hr/leaves/{leaveRequest}/approve', [EmployeeController::class, 'approveLeave']);
        Route::post('/hr/payroll', [EmployeeController::class, 'processPayroll']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask']);

        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
    });
});

Route::apiResource('/posts', PostController::class);

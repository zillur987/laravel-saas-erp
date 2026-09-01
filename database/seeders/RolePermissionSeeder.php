<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Important when seeding repeatedly — Spatie caches permissions in memory.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createPermissions(): void
    {
        foreach (PermissionEnum::all() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    private function createRoles(): void
    {
        // super-admin: bypass is handled in AuthServiceProvider (Gate::before),
        // but we still attach every permission for consistency in UI/reporting.
        $superAdmin = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(PermissionEnum::all());

        $admin = Role::firstOrCreate(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
        $admin->syncPermissions(PermissionEnum::all());

        $manager = Role::firstOrCreate(['name' => RoleEnum::MANAGER->value, 'guard_name' => 'web']);
        $manager->syncPermissions([
            PermissionEnum::INVENTORY_VIEW->value, PermissionEnum::INVENTORY_CREATE->value, PermissionEnum::INVENTORY_UPDATE->value,
            PermissionEnum::SALES_VIEW->value, PermissionEnum::SALES_CREATE->value, PermissionEnum::SALES_UPDATE->value, PermissionEnum::SALES_APPROVE->value,
            PermissionEnum::PURCHASE_VIEW->value, PermissionEnum::PURCHASE_CREATE->value, PermissionEnum::PURCHASE_APPROVE->value,
            PermissionEnum::HR_VIEW->value, PermissionEnum::HR_LEAVE_APPROVE->value,
            PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_CREATE->value, PermissionEnum::CRM_UPDATE->value,
            PermissionEnum::PROJECTS_VIEW->value, PermissionEnum::PROJECTS_CREATE->value, PermissionEnum::PROJECTS_UPDATE->value,
            PermissionEnum::REPORTS_VIEW->value, PermissionEnum::REPORTS_EXPORT->value,
        ]);

        $accountant = Role::firstOrCreate(['name' => RoleEnum::ACCOUNTANT->value, 'guard_name' => 'web']);
        $accountant->syncPermissions([
            PermissionEnum::FINANCE_VIEW->value, PermissionEnum::FINANCE_CREATE->value, PermissionEnum::FINANCE_UPDATE->value,
            PermissionEnum::FINANCE_APPROVE_PAYMENT->value, PermissionEnum::FINANCE_VIEW_REPORTS->value,
            PermissionEnum::REPORTS_VIEW->value, PermissionEnum::REPORTS_EXPORT->value,
        ]);

        $salesStaff = Role::firstOrCreate(['name' => RoleEnum::SALES_STAFF->value, 'guard_name' => 'web']);
        $salesStaff->syncPermissions([
            PermissionEnum::SALES_VIEW->value, PermissionEnum::SALES_CREATE->value, PermissionEnum::SALES_UPDATE->value,
            PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_CREATE->value, PermissionEnum::CRM_UPDATE->value,
            PermissionEnum::INVENTORY_VIEW->value,
        ]);

        $purchaseStaff = Role::firstOrCreate(['name' => RoleEnum::PURCHASE_STAFF->value, 'guard_name' => 'web']);
        $purchaseStaff->syncPermissions([
            PermissionEnum::PURCHASE_VIEW->value, PermissionEnum::PURCHASE_CREATE->value, PermissionEnum::PURCHASE_UPDATE->value,
            PermissionEnum::INVENTORY_VIEW->value,
        ]);

        $inventoryStaff = Role::firstOrCreate(['name' => RoleEnum::INVENTORY_STAFF->value, 'guard_name' => 'web']);
        $inventoryStaff->syncPermissions([
            PermissionEnum::INVENTORY_VIEW->value, PermissionEnum::INVENTORY_CREATE->value,
            PermissionEnum::INVENTORY_UPDATE->value, PermissionEnum::INVENTORY_ADJUST_STOCK->value,
        ]);

        $hrStaff = Role::firstOrCreate(['name' => RoleEnum::HR_STAFF->value, 'guard_name' => 'web']);
        $hrStaff->syncPermissions([
            PermissionEnum::HR_VIEW->value, PermissionEnum::HR_CREATE->value, PermissionEnum::HR_UPDATE->value,
            PermissionEnum::HR_PAYROLL_PROCESS->value, PermissionEnum::HR_LEAVE_APPROVE->value,
        ]);

        // Baseline role every authenticated user gets — read-only own-profile type access.
        Role::firstOrCreate(['name' => RoleEnum::EMPLOYEE->value, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => RoleEnum::OWNER->value, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => RoleEnum::MEMBER->value, 'guard_name' => 'web']);
    }
}
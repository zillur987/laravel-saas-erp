<?php
 
declare(strict_types=1);
 
namespace App\Enums;
 
/**
 * Single source of truth for every permission string in the system.
 *
 * Naming convention: {module}.{action}
 * Keeping this as a backed enum gives us IDE autocomplete + refactor safety
 * instead of scattering raw strings ('inventory.view') across the codebase.
 */
enum PermissionEnum: string
{
    // Users & Access Control
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';
    case ROLES_VIEW = 'roles.view';
    case ROLES_CREATE = 'roles.create';
    case ROLES_UPDATE = 'roles.update';
    case ROLES_DELETE = 'roles.delete';
    case ROLES_ASSIGN = 'roles.assign';
    case PERMISSIONS_VIEW = 'permissions.view';
    case PERMISSIONS_ASSIGN = 'permissions.assign';
 
    // Inventory
    case INVENTORY_VIEW = 'inventory.view';
    case INVENTORY_CREATE = 'inventory.create';
    case INVENTORY_UPDATE = 'inventory.update';
    case INVENTORY_DELETE = 'inventory.delete';
    case INVENTORY_ADJUST_STOCK = 'inventory.adjust_stock';
    case INVENTORY_EXPORT = 'inventory.export';
 
    // Sales
    case SALES_VIEW = 'sales.view';
    case SALES_CREATE = 'sales.create';
    case SALES_UPDATE = 'sales.update';
    case SALES_DELETE = 'sales.delete';
    case SALES_APPROVE = 'sales.approve';
    case SALES_EXPORT = 'sales.export';
 
    // Purchasing
    case PURCHASE_VIEW = 'purchase.view';
    case PURCHASE_CREATE = 'purchase.create';
    case PURCHASE_UPDATE = 'purchase.update';
    case PURCHASE_DELETE = 'purchase.delete';
    case PURCHASE_APPROVE = 'purchase.approve';
 
    // Finance
    case FINANCE_VIEW = 'finance.view';
    case FINANCE_CREATE = 'finance.create';
    case FINANCE_UPDATE = 'finance.update';
    case FINANCE_DELETE = 'finance.delete';
    case FINANCE_APPROVE_PAYMENT = 'finance.approve_payment';
    case FINANCE_VIEW_REPORTS = 'finance.view_reports';
 
    // HR & Payroll
    case HR_VIEW = 'hr.view';
    case HR_CREATE = 'hr.create';
    case HR_UPDATE = 'hr.update';
    case HR_DELETE = 'hr.delete';
    case HR_PAYROLL_PROCESS = 'hr.payroll_process';
    case HR_LEAVE_APPROVE = 'hr.leave_approve';
 
    // CRM
    case CRM_VIEW = 'crm.view';
    case CRM_CREATE = 'crm.create';
    case CRM_UPDATE = 'crm.update';
    case CRM_DELETE = 'crm.delete';
 
    // Projects
    case PROJECTS_VIEW = 'projects.view';
    case PROJECTS_CREATE = 'projects.create';
    case PROJECTS_UPDATE = 'projects.update';
    case PROJECTS_DELETE = 'projects.delete';
 
    // Reports
    case REPORTS_VIEW = 'reports.view';
    case REPORTS_EXPORT = 'reports.export';
 
    // Settings
    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_UPDATE = 'settings.update';
 
    /**
     * Group all permissions by module — used by the seeder and by the
     * frontend permission-matrix UI (module => [permissions]).
     *
     * @return array<string, string[]>
     */
    public static function grouped(): array
    {
        $groups = [];
 
        foreach (self::cases() as $case) {
            [$module] = explode('.', $case->value, 2);
            $groups[$module][] = $case->value;
        }
 
        return $groups;
    }
 
    /**
     * Flat array of every permission string (for seeding).
     *
     * @return string[]
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Default system roles. Additional custom roles can still be created
 * dynamically at runtime — this enum only guarantees a stable set of
 * "baseline" roles that always exist after seeding.
 */
enum RoleEnum: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case ACCOUNTANT = 'accountant';
    case SALES_STAFF = 'sales-staff';
    case PURCHASE_STAFF = 'purchase-staff';
    case INVENTORY_STAFF = 'inventory-staff';
    case HR_STAFF = 'hr-staff';
    case EMPLOYEE = 'employee';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Add this trait to any model that belongs to a tenant (InventoryItem,
 * Invoice, etc.). It does two things automatically:
 *
 * 1. Every query is scoped to the current tenant (global scope) —
 *    this is what enforces data isolation.
 * 2. When creating a new record, tenant_id is auto-filled from the
 *    currently bound tenant, so you never have to remember to set it.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Automatically filter every query by the current tenant.
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant')) {
                $builder->where('tenant_id', app('tenant')->id);
            }
        });
 
        // Automatically set tenant_id when creating a new record.
        static::creating(function ($model) {
            if (app()->bound('tenant') && empty($model->tenant_id)) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
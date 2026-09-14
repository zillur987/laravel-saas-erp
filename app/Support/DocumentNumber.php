<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class DocumentNumber
{
    public static function next(string $prefix, Model $model, string $column = 'number'): string
    {
        $latest = $model->newQuery()->withoutGlobalScopes()->lockForUpdate()
            ->when(app()->bound('tenant'), fn ($q) => $q->where('tenant_id', app('tenant')->id))
            ->orderByDesc('id')
            ->value($column);

        $sequence = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}

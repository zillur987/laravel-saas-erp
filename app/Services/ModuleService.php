<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ModuleService
{
    /**
     * Paginate modules.
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        return Module::query()
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })
            )
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all active modules.
     */
    public function all(): Collection
    {
        return Module::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Create module.
     */
    public function store(array $data): Module
    {
        return DB::transaction(function () use ($data) {

            return Module::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'route_prefix' => $data['route_prefix'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

        });
    }

    /**
     * Update module.
     */
    public function update(
        Module $module,
        array $data
    ): Module {

        DB::transaction(function () use ($module, $data) {

            $module->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'route_prefix' => $data['route_prefix'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

        });

        return $module->refresh();
    }

    /**
     * Delete module.
     */
    public function destroy(Module $module): void
    {
        DB::transaction(function () use ($module) {
            $module->delete();
        });
    }
}
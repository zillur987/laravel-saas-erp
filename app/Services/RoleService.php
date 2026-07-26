<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Roles that can never be deleted or renamed because core system logic
     * (Gate::before, seeders, default assignment on registration) depends on them.
     *
     * @return string[]
     */
    public function protectedRoleNames(): array
    {
        return RoleEnum::all();
    }

    public function list(?string $search = null): Collection
    {
        return Role::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param string[] $permissionNames
     */
    public function create(string $name, array $permissionNames): Role
    {
        return DB::transaction(function () use ($name, $permissionNames) {
            $role = Role::create(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($permissionNames);

            return $role->load('permissions:id,name');
        });
    }

    /**
     * @param string[] $permissionNames
     */
    public function update(Role $role, ?string $name, array $permissionNames): Role
    {
        $this->guardAgainstProtectedRoleRename($role, $name);

        return DB::transaction(function () use ($role, $name, $permissionNames) {
            if ($name !== null && $name !== $role->name) {
                $role->update(['name' => $name]);
            }

            $role->syncPermissions($permissionNames);

            return $role->load('permissions:id,name');
        });
    }

    public function delete(Role $role): void
    {
        if (in_array($role->name, $this->protectedRoleNames(), true)) {
            throw ValidationException::withMessages([
                'role' => "The role '{$role->name}' is a protected system role and cannot be deleted.",
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'This role is still assigned to one or more users. Reassign them before deleting.',
            ]);
        }

        $role->delete();
    }

    public function allPermissionsGrouped(): array
    {
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);

        return $permissions->groupBy(function (Permission $permission) {
            return explode('.', $permission->name)[0];
        })->map(fn ($group) => $group->values())->toArray();
    }

    private function guardAgainstProtectedRoleRename(Role $role, ?string $newName): void
    {
        if ($newName === null || $newName === $role->name) {
            return;
        }

        if (in_array($role->name, $this->protectedRoleNames(), true)) {
            throw ValidationException::withMessages([
                'name' => "The role '{$role->name}' is a protected system role and cannot be renamed.",
            ]);
        }
    }
}
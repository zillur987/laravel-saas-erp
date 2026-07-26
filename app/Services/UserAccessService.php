<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    /**
     * @param string[] $roleNames
     */
    public function syncRoles(User $user, array $roleNames): User
    {
        $this->guardAgainstRemovingLastSuperAdmin($user, $roleNames);

        return DB::transaction(function () use ($user, $roleNames) {
            $user->syncRoles($roleNames);

            return $user->load('roles:id,name');
        });
    }

    /**
     * Direct, role-independent permissions granted to a specific user
     * (e.g. a one-off exception for a single employee).
     *
     * @param string[] $permissionNames
     */
    public function syncDirectPermissions(User $user, array $permissionNames): User
    {
        return DB::transaction(function () use ($user, $permissionNames) {
            $user->syncPermissions($permissionNames);

            return $user->load('permissions:id,name');
        });
    }

    /**
     * @param string[] $newRoleNames
     */
    private function guardAgainstRemovingLastSuperAdmin(User $user, array $newRoleNames): void
    {
        $wasSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $willRemainSuperAdmin = in_array(RoleEnum::SUPER_ADMIN->value, $newRoleNames, true);

        if (! $wasSuperAdmin || $willRemainSuperAdmin) {
            return;
        }

        $otherSuperAdmins = User::role(RoleEnum::SUPER_ADMIN->value)
            ->where('id', '!=', $user->id)
            ->exists();

        if (! $otherSuperAdmins) {
            throw ValidationException::withMessages([
                'roles' => 'Cannot remove the super-admin role — at least one super-admin must remain in the system.',
            ]);
        }
    }
}
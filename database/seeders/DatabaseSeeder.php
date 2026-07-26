<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            // ... your other seeders
        ]);

        $roles = $this->createRoles();
        $permissions = $this->createPermissions();
        $this->assignPermissionsToRoles($roles, $permissions);
        $users = $this->createUsers();
        $this->assignRolesToUsers($users, $roles);
    }

    private function createRoles(): array
    {
        return [
            'admin' => Role::firstOrCreate(['name' => 'admin']),
            'author' => Role::firstOrCreate(['name' => 'author']),
            'viewer' => Role::firstOrCreate(['name' => 'viewer']),
        ];
    }

    private function createPermissions(): Collection
    {
        $permissionNames = [
            'Read Post',
            'Create Post',
            'Edit Post',
            'Delete Post',
        ];

        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        return Permission::all();
    }

    private function assignPermissionsToRoles(array $roles, Collection $permissions): void
    {
        // Assign all permissions to author role
        $roles['author']->syncPermissions($permissions);

        // Assign only read permission to viewer role
        $roles['viewer']->syncPermissions(
            $permissions->firstWhere('name', 'Read Post')
        );
    }

    private function createUsers(): array
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => 'admin',
            ],
            [
                'name' => 'Author',
                'email' => 'author@example.com',
                'password' => 'password',
                'role' => 'author',
            ],
            [
                'name' => 'Author 1',
                'email' => 'author1@example.com',
                'password' => 'password',
                'role' => 'author',
            ],
            [
                'name' => 'Viewer',
                'email' => 'viewer@example.com',
                'password' => 'password',
                'role' => 'viewer',
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt($userData['password']),
            ]);
            // Store users in a nested array structure
            if (!isset($createdUsers[$userData['role']])) {
                $createdUsers[$userData['role']] = [];
            }
            $createdUsers[$userData['role']][] = $user;
        }

        return $createdUsers;
    }

    private function assignRolesToUsers(array $users, array $roles): void
    {
        foreach ($users as $role => $roleUsers) {
            // Handle multiple users per role
            foreach ($roleUsers as $user) {
                $user->assignRole($roles[$role]);
            }
        }
    }
}

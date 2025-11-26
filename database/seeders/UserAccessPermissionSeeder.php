<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class UserAccessPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===============================
        // 1. DAFTAR PERMISSION
        // ===============================
        $permissions = [
            // Roles
            'access.roles.view',
            'access.roles.create',
            'access.roles.update',
            'access.roles.delete',

            // Users
            'access.users.view',
            'access.users.create',
            'access.users.update',
            'access.users.delete',

            // Permission Setting (halaman ini)
            'access.permissions.view',
            'access.permissions.update',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                [
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]
            );
        }

        // ===============================
        // 2. ASSIGN KE ROLE
        //    Superadmin tetap bypass
        // ===============================
        $rolesConfig = [
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [], // full akses via bypass
            ],

            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    // roles
                    'access.roles.view',
                    'access.roles.create',
                    'access.roles.update',
                    'access.roles.delete',
                    // users
                    'access.users.view',
                    'access.users.create',
                    'access.users.update',
                    'access.users.delete',
                    // permission settings
                    'access.permissions.view',
                    'access.permissions.update',
                ],
            ],

            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    // hanya boleh lihat user & role, tidak boleh ubah
                    'access.roles.view',
                    'access.users.view',
                ],
            ],
        ];

        foreach ($rolesConfig as $roleName => $config) {
            /** @var \App\Models\Role $role */
            $role = Role::firstOrCreate(
                [
                    'name'       => $roleName,
                    'guard_name' => $config['guard_name'],
                ]
            );

            if (! empty($config['permissions'])) {
                $role->givePermissionTo($config['permissions']);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AccessUserPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'access.users.view',
            'access.users.create',
            'access.users.update',
            'access.users.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                [
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]
            );
        }

        $rolesMap = [
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [], // full akses via bypass di controller
            ],

            // Contoh: Manager boleh kelola semua user
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'access.users.view',
                    'access.users.create',
                    'access.users.update',
                    'access.users.delete',
                ],
            ],

            // Staff misal hanya boleh lihat daftar user
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'access.users.view',
                ],
            ],
        ];

        foreach ($rolesMap as $roleName => $config) {
            /** @var \App\Models\Role $role */
            $role = Role::firstOrCreate(
                [
                    'name'       => $roleName,
                    'guard_name' => $config['guard_name'],
                ]
            );

            if (!empty($config['permissions'])) {
                $role->givePermissionTo($config['permissions']);
            }
        }
    }
}

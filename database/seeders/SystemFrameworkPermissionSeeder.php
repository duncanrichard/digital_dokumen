<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class SystemFrameworkPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permission untuk halaman Framework System
        $permissions = [
            'system.framework.view',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                [
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]
            );
        }

        // 2. Role → Permission mapping
        $rolesMap = [
            // Superadmin bypass via controller
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [],
            ],

            // Manager: boleh lihat
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'system.framework.view',
                ],
            ],

            // Staff: boleh lihat juga (opsional)
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'system.framework.view',
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

            if (! empty($config['permissions'])) {
                $role->givePermissionTo($config['permissions']);
            }
        }
    }
}

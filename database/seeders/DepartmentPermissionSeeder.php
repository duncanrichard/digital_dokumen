<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class DepartmentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Daftar permission untuk master Divisi
        $permissions = [
            'master.departments.view',
            'master.departments.create',
            'master.departments.update',
            'master.departments.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                [
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]
            );
        }

        // 2. Mapping Role -> Permissions
        $rolesMap = [
            // Superadmin: bypass via logic di controller
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [],
            ],

            // Manager: full akses master divisi
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'master.departments.view',
                    'master.departments.create',
                    'master.departments.update',
                    'master.departments.delete',
                ],
            ],

            // Staff: misal hanya boleh lihat
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'master.departments.view',
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

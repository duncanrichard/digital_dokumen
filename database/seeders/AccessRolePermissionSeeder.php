<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AccessRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'access.roles.view',
            'access.roles.create',
            'access.roles.update',
            'access.roles.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                [
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]
            );
        }

        // Konfigurasi role -> permissions
        $rolesConfig = [
            // Superadmin: akses full lewat bypass di controller
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [],
            ],

            // Manager boleh kelola role
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'access.roles.view',
                    'access.roles.create',
                    'access.roles.update',
                    'access.roles.delete',
                ],
            ],

            // Staff misal hanya boleh lihat daftar role
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'access.roles.view',
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

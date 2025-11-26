<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class SettingsWatermarkPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Daftar permission untuk Settings Watermark / DRM
        $permissions = [
            'settings.watermark.view',
            'settings.watermark.update',
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

            // Manager: full access watermark settings
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'settings.watermark.view',
                    'settings.watermark.update',
                ],
            ],

            // Staff: misal hanya boleh lihat
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'settings.watermark.view',
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

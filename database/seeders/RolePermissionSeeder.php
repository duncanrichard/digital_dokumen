<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========================
        // PERMISSION DASHBOARD
        // =========================
        $permissionName = 'dashboard.analytics.view';

        $permission = Permission::firstOrCreate([
            'name'       => $permissionName,
            'guard_name' => 'web',
        ]);

        // =========================
        // ROLES DEFAULT (DINAMIS)
        // =========================
        $rolesConfig = [
            // Superadmin: tidak perlu di-assign manual
            // karena sudah bypass semua permission lewat Gate::before
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [], // biarkan kosong
            ],

            // Manager: boleh lihat dashboard
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'dashboard.analytics.view',
                ],
            ],

            // Staff: boleh lihat dashboard juga
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'dashboard.analytics.view',
                ],
            ],
        ];

        foreach ($rolesConfig as $roleName => $config) {
            /** @var \App\Models\Role $role */
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $config['guard_name']]
            );

            // Assign permission hanya kalau list-nya tidak kosong
            if (!empty($config['permissions'])) {
                $role->givePermissionTo($config['permissions']);
            }
        }
    }
}

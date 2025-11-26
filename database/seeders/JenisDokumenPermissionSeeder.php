<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class JenisDokumenPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // DAFTAR PERMISSIONS
        $permissions = [
            'master.jenis-dokumen.view',
            'master.jenis-dokumen.create',
            'master.jenis-dokumen.update',
            'master.jenis-dokumen.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }

        // MAPPING ROLE → PERMISSIONS
        $rolesMap = [
            'Superadmin' => [], // bypass logic
            'Manager' => [
                'master.jenis-dokumen.view',
                'master.jenis-dokumen.create',
                'master.jenis-dokumen.update',
                'master.jenis-dokumen.delete',
            ],
            'Staff' => [
                'master.jenis-dokumen.view',
            ],
        ];

        foreach ($rolesMap as $roleName => $perms) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            if (!empty($perms)) {
                $role->givePermissionTo($perms);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ClinicPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'master.clinics.view',
            'master.clinics.create',
            'master.clinics.update',
            'master.clinics.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->givePermissionTo($permissions);

        $staff = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $staff->givePermissionTo('master.clinics.view');
    }
}

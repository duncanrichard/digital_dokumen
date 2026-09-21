<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Permission;

class DocumentAccessApprovalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // bersihkan cache permission Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['documents.access-approvals.view', 'documents.access-approvals.decide'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // opsional: kalau mau langsung diberikan ke role tertentu
        // use App\Models\Role;
        // $role = \App\Models\Role::where('name', 'Manager')->first();
        // if ($role) {
        //     $role->givePermissionTo($name);
        // }
    }
}

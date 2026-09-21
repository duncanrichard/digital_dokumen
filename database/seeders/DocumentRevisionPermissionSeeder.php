<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Permission;

class DocumentRevisionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan cache permission Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['documents.revisions.view', 'documents.revisions.create'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // (Opsional) auto kasih ke role tertentu:
        // use App\Models\Role;
        // $role = \App\Models\Role::where('name', 'Admin')->first();
        // if ($role) {
        //     $role->givePermissionTo($name);
        // }
    }
}

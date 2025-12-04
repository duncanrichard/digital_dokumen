<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AddDocumentChangePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Buat permission baru jika belum ada
        $perm = Permission::firstOrCreate(
            [
                'name'       => 'documents.upload.change',
                'guard_name' => 'web',
            ]
        );

        // Assign ke role Manager
        $manager = Role::where('name', 'Manager')->first();
        if ($manager) {
            $manager->givePermissionTo($perm);
        }

        // Kalau mau assign ke Staff, buka komentar:
        /*
        $staff = Role::where('name', 'Staff')->first();
        if ($staff) {
            $staff->givePermissionTo($perm);
        }
        */
    }
}

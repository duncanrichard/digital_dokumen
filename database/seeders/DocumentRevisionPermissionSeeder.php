<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DocumentRevisionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan cache permission Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $name      = 'documents.revisions.view';
        $guardName = 'web';

        // Cek apakah permission sudah ada
        $exists = DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->exists();

        if (! $exists) {
            $now = now();

            DB::table('permissions')->insert([
                'id'         => (string) Str::uuid(),   // kolom id NOT NULL, jadi isi manual
                'name'       => $name,
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // (Opsional) auto kasih ke role tertentu:
        // use App\Models\Role;
        // $role = \App\Models\Role::where('name', 'Admin')->first();
        // if ($role) {
        //     $role->givePermissionTo($name);
        // }
    }
}

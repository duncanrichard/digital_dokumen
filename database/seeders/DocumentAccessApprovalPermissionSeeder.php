<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DocumentAccessApprovalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // bersihkan cache permission Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $name      = 'documents.access-approvals.view';
        $guardName = 'web';

        // cek apakah permission sudah ada
        $exists = DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->exists();

        if (! $exists) {
            $now = now();

            DB::table('permissions')->insert([
                'id'         => (string) Str::uuid(),   // wajib diisi untuk PostgreSQL (uuid / not null)
                'name'       => $name,
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // opsional: kalau mau langsung diberikan ke role tertentu
        // use App\Models\Role;
        // $role = \App\Models\Role::where('name', 'Manager')->first();
        // if ($role) {
        //     $role->givePermissionTo($name);
        // }
    }
}

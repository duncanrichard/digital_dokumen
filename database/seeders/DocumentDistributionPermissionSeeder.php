<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class DocumentDistributionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===========================================
        // 1. PERMISSION UNTUK DISTRIBUSI DOKUMEN
        // ===========================================
        $permissions = [
            'documents.distribution.view',   // akses halaman distribusi
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                [
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]
            );
        }

        // ===========================================
        // 2. ASSIGN PERMISSION KE ROLE
        // ===========================================
        // Catatan:
        // Superadmin tidak perlu assign (bypass via Gate::before)

        // --- Manager: punya akses halaman distribusi ---
        $manager = Role::where('name', 'Manager')
            ->where('guard_name', 'web')
            ->first();
        if ($manager) {
            $manager->givePermissionTo([
                'documents.distribution.view',
            ]);
        }

        // --- Staff: bisa akses juga (optional, bisa kamu ubah) ---
        $staff = Role::where('name', 'Staff')
            ->where('guard_name', 'web')
            ->first();
        if ($staff) {
            $staff->givePermissionTo([
                'documents.distribution.view',
            ]);
        }

        // Role lain bisa ditambahkan sesuai kebutuhan
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class DocumentUploadPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===========================================
        // 1. DAFTAR PERMISSION UNTUK DOCUMENT UPLOAD
        // ===========================================
        $permissions = [
            'documents.upload.view',          // boleh melihat menu & list dokumen
            'documents.upload.create',        // tombol "Add Document" + store
            'documents.upload.update',        // edit/update dokumen
            'documents.upload.delete',        // delete dokumen

            // ✅ permission baru untuk Turunan Klinik
            'documents.upload.turunan_clinic',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);
        }

        // ===========================================
        // 2. ASSIGN PERMISSION KE ROLE
        // ===========================================
        $rolesConfig = [
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [], // bypass via logic kamu
            ],

            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'documents.upload.view',
                    'documents.upload.create',
                    'documents.upload.update',
                    'documents.upload.delete',

                    // ✅ boleh turunan klinik
                    'documents.upload.turunan_clinic',
                ],
            ],

            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'documents.upload.view',
                    'documents.upload.create',
                    'documents.upload.update',

                    // ✅ kalau staff juga boleh turunan klinik, aktifkan:
                    // 'documents.upload.turunan_clinic',
                ],
            ],
        ];

        foreach ($rolesConfig as $roleName => $config) {
            /** @var \App\Models\Role $role */
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => $config['guard_name'],
            ]);

            if (!empty($config['permissions'])) {
                $role->givePermissionTo($config['permissions']);
            }
        }
    }
}

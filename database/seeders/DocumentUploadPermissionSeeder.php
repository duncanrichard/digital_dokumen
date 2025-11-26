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
            'documents.upload.view',   // boleh melihat menu & list dokumen
            'documents.upload.create', // tombol "Add Document" + store
            'documents.upload.update', // edit/update dokumen
            'documents.upload.delete', // delete dokumen
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
        //    Superadmin tetap bypass via Gate::before
        //    atau via logic di controller (cek role Superadmin)
        // ===========================================

        // Konfigurasi mapping Role -> permissions
        $rolesConfig = [
            // Superadmin: tidak perlu di-assign manual
            'Superadmin' => [
                'guard_name'  => 'web',
                'permissions' => [], // dibiarkan kosong, akses full via bypass
            ],

            // Manager: full akses Documents
            'Manager' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'documents.upload.view',
                    'documents.upload.create',
                    'documents.upload.update',
                    'documents.upload.delete',
                ],
            ],

            // Staff: boleh view + create + update (TIDAK boleh delete)
            'Staff' => [
                'guard_name'  => 'web',
                'permissions' => [
                    'documents.upload.view',
                    'documents.upload.create',
                    'documents.upload.update',
                    // kalau mau boleh delete juga, tinggal tambahkan:
                    // 'documents.upload.delete',
                ],
            ],
        ];

        foreach ($rolesConfig as $roleName => $config) {
            /** @var \App\Models\Role $role */
            $role = Role::firstOrCreate(
                [
                    'name'       => $roleName,
                    'guard_name' => $config['guard_name'],
                ]
            );

            if (!empty($config['permissions'])) {
                $role->givePermissionTo($config['permissions']);
            }
        }
    }
}

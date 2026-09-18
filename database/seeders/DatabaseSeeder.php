<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserAccessPermissionSeeder::class,
            JenisDokumenPermissionSeeder::class,
            DepartmentPermissionSeeder::class,
            ClinicPermissionSeeder::class,
            DocumentUploadPermissionSeeder::class,
            AddDocumentChangePermissionSeeder::class,
            DocumentDistributionPermissionSeeder::class,
            DocumentRevisionPermissionSeeder::class,
            DocumentAccessApprovalPermissionSeeder::class,
            SettingsWatermarkPermissionSeeder::class,
            SettingsDocumentAccessPermissionSeeder::class,
            SystemFrameworkPermissionSeeder::class,
        ]);
    }

}

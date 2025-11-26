<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams       = config('permission.teams');
        $tableNames  = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $pivotRole       = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS (UUID)
        |--------------------------------------------------------------------------
        */
        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        /*
        |--------------------------------------------------------------------------
        | ROLES (UUID)
        |--------------------------------------------------------------------------
        | Tabel roles SUDAH ADA → LEWATI
        */
        if (!Schema::hasTable($tableNames['roles'])) {
            Schema::create($tableNames['roles'], function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | model_has_permissions (UUID FK)
        |--------------------------------------------------------------------------
        */
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use (
            $tableNames,
            $columnNames,
            $pivotPermission,
            $teams
        ) {
            $table->uuid($pivotPermission);
            $table->uuid($columnNames['model_morph_key']);
            $table->string('model_type');

            $table->index([$columnNames['model_morph_key'], 'model_type']);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->primary([
                    $columnNames['team_foreign_key'],
                    $pivotPermission,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type']);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | model_has_roles (UUID FK)
        |--------------------------------------------------------------------------
        */
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use (
            $tableNames,
            $columnNames,
            $pivotRole,
            $teams
        ) {
            $table->uuid($pivotRole);
            $table->uuid($columnNames['model_morph_key']);
            $table->string('model_type');

            $table->index([$columnNames['model_morph_key'], 'model_type']);

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->primary([
                    $columnNames['team_foreign_key'],
                    $pivotRole,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type']);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | role_has_permissions (UUID FK)
        |--------------------------------------------------------------------------
        */
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($pivotRole, $pivotPermission, $tableNames) {
            $table->uuid($pivotPermission);
            $table->uuid($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole]);
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['permissions']);

        // TABEL ROLES TIDAK DIHAPUS (karena tabel kamu sudah custom)
    }
};

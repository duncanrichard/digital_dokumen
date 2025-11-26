<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Hris\Employee; // MODEL KARYAWAN HRIS (MySQL)
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * Tabel utama (PostgreSQL).
     */
    protected $table = 'users';

    /**
     * Primary key bertipe UUID (string) dan tidak auto increment.
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Field yang boleh diisi mass-assignment.
     */
    protected $fillable = [
        'hris_employee_id',   // id karyawan HRIS (tabel employees di mysql_hris)
        'name',
        'username',
        'email',
        'password',
        'nomor_wa',           // nomor WhatsApp
        'department_id',
        'role_id',            // role utama (reference ke tabel roles — bisa selaras dengan Spatie Role)
        'is_active',
    ];

    /**
     * Field yang disembunyikan ketika di-serialize.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting field.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'password'  => 'hashed',
    ];

    /**
     * Relasi ke Department (Postgres).
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Relasi ke Role (1 user = 1 role, Postgres).
     *
     * NOTE:
     * - Ini role "utama" via kolom role_id.
     * - Untuk permission & pengecekan dari Spatie, gunakan:
     *   $user->roles (many-to-many spatie) atau
     *   $user->hasRole('Superadmin'), dll.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Relasi ke Employee di HRIS (MySQL).
     * Menggunakan connection "mysql_hris" di model Employee.
     */
    public function hrisEmployee()
    {
        // foreign key di tabel users = hris_employee_id
        // primary key di tabel employees = id
        return $this->belongsTo(Employee::class, 'hris_employee_id');
    }

    /**
     * Scope untuk search (nama, username, email di tabel users).
     */
    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        $term = mb_strtolower($term);

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(username) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"]);
        });
    }

    /**
     * Scope untuk filter status aktif / tidak aktif.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed                                  $status '1' | '0' | null
     */
    public function scopeStatus($query, $status)
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('is_active', (bool) $status);
    }
}

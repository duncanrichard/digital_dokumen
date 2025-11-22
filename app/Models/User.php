<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
<<<<<<< HEAD
use App\Models\Hris\Employee; // <── MODEL KARYAWAN HRIS (MYSQL)
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

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
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Penting untuk ID UUID
use Illuminate\Support\Facades\Hash; // Untuk hashing password

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids; // Tambahkan HasUuids

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users'; // Sesuaikan dengan nama tabel Anda

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false; // Karena kita pakai UUID

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string'; // Karena kita pakai UUID

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        'name',
        'username',
        'email',
        'password',
<<<<<<< HEAD
        'nomor_wa',           // <── nomor WhatsApp
        'department_id',
        'role_id',
=======
        'department_id',
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        'is_active',
    ];

    /**
<<<<<<< HEAD
     * Field yang disembunyikan ketika di-serialize.
=======
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
<<<<<<< HEAD
     * Casting field.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'password'  => 'hashed',
        // 'hris_employee_id' => 'integer', // boleh diaktifkan kalau id di HRIS integer
    ];

    /**
     * Relasi ke Department (Postgres).
=======
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed', // Otomatis hash jika di-assign
    ];

    /**
     * Relasi ke Department.
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
<<<<<<< HEAD
     * Relasi ke Role (1 user = 1 role, Postgres).
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
=======
     * Scope untuk search.
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }
<<<<<<< HEAD

        $term = mb_strtolower($term);

=======
        $term = mb_strtolower($term);
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(username) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"]);
        });
    }

    /**
<<<<<<< HEAD
     * Scope untuk filter status aktif / tidak aktif.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed                                  $status '1' | '0' | null
=======
     * Scope untuk status.
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function scopeStatus($query, $status)
    {
        if ($status === null || $status === '') {
            return $query;
        }
<<<<<<< HEAD

        return $query->where('is_active', (bool) $status);
=======
        return $query->where('is_active', (bool)$status);
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    }
}

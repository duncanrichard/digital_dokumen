<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        'name',
        'username',
        'password',
        'department_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
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
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Scope untuk search.
     */
    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }
        $term = mb_strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(username) LIKE ?', ["%{$term}%"]);
        });
    }

    /**
     * Scope untuk status.
     */
    public function scopeStatus($query, $status)
    {
        if ($status === null || $status === '') {
            return $query;
        }
        return $query->where('is_active', (bool)$status);
    }
}
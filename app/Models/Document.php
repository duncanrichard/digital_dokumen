<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Laravel 9+
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'documents';

    /**
     * PK = UUID string
     */
    public $incrementing = false;
    protected $keyType   = 'string';

    /**
     * Kolom yang bisa di-mass assign.
     * (JANGAN masukkan 'id' ke fillable — UUID akan digenerate otomatis)
     */
    protected $fillable = [
        'jenis_dokumen_id',
        'department_id',
        'sequence',
        'document_number',
        'name',
        'publish_date',
        'file_path',
        'is_active',
        'revision',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'is_active'    => 'boolean',
    ];

    /**
     * ====== RELATIONS ======
     */
    public function jenisDokumen()
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Ke tabel pivot (belongsToMany) — untuk list departemen penerima
    public function distributedDepartments()
    {
        return $this->belongsToMany(
            Department::class,
            'document_distributions',
            'document_id',
            'department_id'
        )->withTimestamps();
    }

    // Jika perlu akses baris pivotnya langsung (opsional)
    public function distributions()
    {
        return $this->hasMany(DocumentDistribution::class, 'document_id', 'id');
    }

    /**
     * ====== SCOPES (opsional, berguna di Controller) ======
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;

        // Gunakan ILIKE untuk Postgres
        $driver = $q->getModel()->getConnection()->getDriverName();
        $like   = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $q->where(function ($w) use ($term, $like) {
            $w->where('name', $like, "%{$term}%")
              ->orWhere('document_number', $like, "%{$term}%");
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrderDefault(Builder $q): Builder
    {
        return $q->orderByDesc('publish_date')->orderByDesc('created_at');
    }

    /**
     * ====== ACCESSORS (opsional) ======
     * Ambil URL file berdasarkan file_path (jika pakai storage public).
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        // Sesuaikan jika file_path sudah absolute URL
        return Str($this->file_path)->startsWith(['http://','https://'])
            ? $this->file_path
            : Storage::url($this->file_path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Laravel 9+
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * Kolom yang boleh di-mass assign.
     * (JANGAN masukkan 'id' — UUID digenerate otomatis)
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
        'read_notifikasi', // <— penting untuk notifikasi
    ];

    protected $casts = [
        'publish_date'     => 'date',
        'is_active'        => 'boolean',
        'read_notifikasi'  => 'boolean',
        'revision'         => 'integer',
        'sequence'         => 'integer',
    ];

    /* =======================
     * RELATIONS
     * ======================= */
    public function jenisDokumen()
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Ke tabel pivot — daftar departemen penerima distribusi
    public function distributedDepartments()
    {
        return $this->belongsToMany(
            Department::class,
            'document_distributions',
            'document_id',
            'department_id'
        )->withTimestamps();
    }

    // Jika butuh akses langsung ke baris pivot
    public function distributions()
    {
        return $this->hasMany(DocumentDistribution::class, 'document_id', 'id');
    }

    /* =======================
     * SCOPES (membantu query)
     * ======================= */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;

        // ILIKE untuk Postgres agar case-insensitive
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

    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('read_notifikasi', false);
    }

    public function scopeOrderDefault(Builder $q): Builder
    {
        return $q->orderByDesc('publish_date')
                 ->orderByDesc('created_at');
    }

    /* =======================
     * ACCESSORS / VIRTUAL ATTR
     * ======================= */

    /**
     * URL file untuk ditampilkan (jika pakai disk 'public').
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;

        // Jika sudah absolute URL, kembalikan apa adanya
        if (Str::startsWith($this->file_path, ['http://', 'https://'])) {
            return $this->file_path;
        }

        // Jika path storage (mis. documents/xxx.pdf)
        return Storage::url($this->file_path);
    }

    /**
     * Nomor tampil: "<document_number> R<revision>"
     */
    public function getDisplayNumberAttribute(): string
    {
        return "{$this->document_number} R{$this->revision}";
    }
}

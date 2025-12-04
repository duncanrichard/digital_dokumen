<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'documents';

    public $incrementing = false;
    protected $keyType   = 'string';

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
        'read_notifikasi',
        'notes',          // <-- TAMBAH INI
    ];

    protected $casts = [
        'publish_date'     => 'date',
        'is_active'        => 'boolean',
        'read_notifikasi'  => 'boolean',
        'revision'         => 'integer',
        'sequence'         => 'integer',
        'notes'            => 'string', // opsional, biar konsisten
    ];

    // ... sisanya tetap seperti semula ...

    public function jenisDokumen()
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function distributedDepartments()
    {
        return $this->belongsToMany(
            Department::class,
            'document_distributions',
            'document_id',
            'department_id'
        )->withTimestamps();
    }

    public function distributions()
    {
        return $this->hasMany(DocumentDistribution::class, 'document_id', 'id');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
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

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;

        if (Str::startsWith($this->file_path, ['http://', 'https://'])) {
            return $this->file_path;
        }

        return Storage::url($this->file_path);
    }

    public function getDisplayNumberAttribute(): string
    {
        return "{$this->document_number} R{$this->revision}";
    }

    // Dokumen ini DIUBAH MENJADI dokumen lain (dokumen baru)
    public function changedToDocuments()
    {
        return $this->belongsToMany(
            self::class,
            'document_relations',
            'parent_document_id',
            'child_document_id'
        )->withPivot('relation_type')->withTimestamps();
    }

    // Dokumen ini HASIL PERUBAHAN dari dokumen lain
    public function changedFromDocuments()
    {
        return $this->belongsToMany(
            self::class,
            'document_relations',
            'child_document_id',
            'parent_document_id'
        )->withPivot('relation_type')->withTimestamps();
    }
}

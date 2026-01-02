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
        'notes',
    ];

    protected $casts = [
        'publish_date'    => 'date',
        'is_active'       => 'boolean',
        'read_notifikasi' => 'boolean',
        'revision'        => 'integer',
        'sequence'        => 'integer',
        'notes'           => 'string',
    ];

    /* =======================
     |  RELATIONS (MASTER)
     ======================= */

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

    /* =======================
     |  SCOPES
     ======================= */

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') return $q;

        // escape LIKE wildcard agar aman
        $escaped = addcslashes($term, "\\%_");
        $driver  = $q->getModel()->getConnection()->getDriverName();
        $like    = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $q->where(function ($w) use ($escaped, $like) {
            $w->where('name', $like, "%{$escaped}%")
              ->orWhere('document_number', $like, "%{$escaped}%");
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
     |  ACCESSORS
     ======================= */

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;

        if (Str::startsWith($this->file_path, ['http://', 'https://'])) {
            return $this->file_path;
        }

        return Storage::url($this->file_path);
    }

    public function getFileNameAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return basename($this->file_path);
    }

    public function getDisplayNumberAttribute(): string
    {
        $rev = (int) ($this->revision ?? 0);
        return "{$this->document_number} R{$rev}";
    }

    /* =======================
     |  DOCUMENT RELATIONS
     |  Pivot: document_relations
     |  parent_document_id, child_document_id, relation_type
     ======================= */

    /**
     * Dokumen ini -> punya CHILD (dokumen hasil perubahan / turunan)
     */
    public function relationsToChildren()
    {
        return $this->belongsToMany(
            self::class,
            'document_relations',
            'parent_document_id',
            'child_document_id'
        )->withPivot('relation_type')->withTimestamps();
    }

    /**
     * Dokumen ini -> punya PARENT (dokumen asal)
     */
    public function relationsToParents()
    {
        return $this->belongsToMany(
            self::class,
            'document_relations',
            'child_document_id',
            'parent_document_id'
        )->withPivot('relation_type')->withTimestamps();
    }

    /**
     * Alias kompatibel dengan kode kamu sebelumnya:
     * changedToDocuments() dan changedFromDocuments()
     */
    public function changedToDocuments()
    {
        return $this->relationsToChildren();
    }

    public function changedFromDocuments()
    {
        return $this->relationsToParents();
    }

    /* =======================
     |  FILTERED RELATIONS (PENTING)
     ======================= */

    /**
     * Dokumen ini DIUBAH menjadi dokumen lain (relation_type = changed_to)
     */
    public function changedToDocumentsOnly()
    {
        return $this->relationsToChildren()->wherePivot('relation_type', 'changed_to');
    }

    /**
     * Dokumen ini TURUNAN klinik (relation_type = derived_clinic)
     */
    public function derivedClinicDocuments()
    {
        return $this->relationsToChildren()->wherePivot('relation_type', 'derived_clinic');
    }

    /**
     * Dokumen ini hasil perubahan dari dokumen lain (parent) (relation_type = changed_to)
     * biasanya cuma 1 parent.
     */
    public function changedFromDocumentOnly()
    {
        return $this->relationsToParents()->wherePivot('relation_type', 'changed_to');
    }

    /**
     * Dokumen ini turunan dari dokumen master (parent) (relation_type = derived_clinic)
     */
    public function derivedFromMasterDocument()
    {
        return $this->relationsToParents()->wherePivot('relation_type', 'derived_clinic');
    }

    /* =======================
     |  SINGLE HELPERS (BIAR ENAK DI BLADE)
     ======================= */

    /**
     * Ambil 1 dokumen parent (diubah dari) kalau ada
     */
    public function getChangedFromAttribute(): ?self
    {
        return $this->changedFromDocumentOnly()->orderByDesc('documents.created_at')->first();
    }

    /**
     * Ambil 1 dokumen child terakhir (hasil diubah) kalau ada
     */
    public function getChangedToLatestAttribute(): ?self
    {
        return $this->changedToDocumentsOnly()->orderByDesc('documents.created_at')->first();
    }

    /**
     * Ambil semua turunan klinik (children)
     */
    public function getClinicDerivativesAttribute()
    {
        return $this->derivedClinicDocuments()->orderByDesc('documents.created_at')->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDistribution extends Model
{
    protected $table = 'document_distributions';
    public $incrementing = false;    // tidak ada PK auto increment
    public $timestamps = true;       // ada created_at & updated_at

    // Tidak ada primary key tunggal; kita izinkan mass assignment field berikut
    protected $fillable = [
        'document_id',
        'department_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'document_id'  => 'string',
        'department_id'=> 'string',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}

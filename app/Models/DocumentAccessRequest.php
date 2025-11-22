<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DocumentAccessRequest extends Model
{
    use HasUuids;

    protected $table = 'document_access_requests';

    protected $fillable = [
        'user_id',
        'document_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'expires_at',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'decided_at'   => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function document()
    {
        return $this->belongsTo(\App\Models\Document::class);
    }

    public function decider()
    {
        return $this->belongsTo(\App\Models\User::class, 'decided_by');
    }
}

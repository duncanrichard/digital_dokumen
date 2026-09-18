<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DocumentAccessRequest extends Model
{
    use HasUuids;

    protected $table = 'document_access_requests';

    protected $fillable = [
        'requester_user_id',
        'requester_department_id',
        'document_id',
        'reason',
        'status',
        'decided_by_user_id',
        'decided_at',
        'access_expires_at',
    ];

    protected $casts = [
        'decided_at'   => 'datetime',
        'access_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_user_id');
    }

    public function document()
    {
        return $this->belongsTo(\App\Models\Document::class);
    }

    public function decider()
    {
        return $this->belongsTo(\App\Models\User::class, 'decided_by_user_id');
    }

    public function getRequestedAtAttribute()
    {
        return $this->created_at;
    }

    public function getExpiresAtAttribute()
    {
        return $this->access_expires_at;
    }
}

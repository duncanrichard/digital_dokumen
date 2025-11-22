<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentAccessSetting extends Model
{
    protected $table = 'document_access_settings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'enabled',
        'default_duration_minutes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}

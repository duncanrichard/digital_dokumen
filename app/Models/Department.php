<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'parent_id',
        'office_type',
        'code',          // dipakai untuk DIVISI UTAMA (parent)
        'name',
        'description',
        'no_wa',
        'is_active',
        'wa_send_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function scopeHolding($q)
    {
        return $q->where('office_type', 'holding');
    }

    public function scopeDjc($q)
    {
        return $q->where('office_type', 'djc');
    }
}

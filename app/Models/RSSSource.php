<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RSSSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'url',
        'enabled',
        'last_parsed_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_parsed_at' => 'datetime',
    ];

    /**
     * Связь: У источника много новостей
     */
    public function news()
    {
        return $this->hasMany(News::class);
    }

    /**
     * Scope: Только включённые источники
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
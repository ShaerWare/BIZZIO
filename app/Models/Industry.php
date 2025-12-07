<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * Компании в этой отрасли
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
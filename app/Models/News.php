<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rss_source_id',
        'title',
        'description',
        'link',
        'image',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Связь: Новость принадлежит RSS-источнику
     */
    public function rssSource()
    {
        return $this->belongsTo(RSSSource::class);
    }

    /**
     * Scope: Поиск по ключевым словам (FULLTEXT)
     * 
     * @param array $keywords Массив ключевых слов
     * @param string $mode 'any' (OR) или 'all' (AND)
     */
    public function scopeSearchByKeywords($query, array $keywords, string $mode = 'all')
    {
        if (empty($keywords)) {
            return $query;
        }

        if ($mode === 'all') {
            // Логика AND: все ключевые слова должны присутствовать
            foreach ($keywords as $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'LIKE', "%{$keyword}%")
                      ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            }
        } else {
            // Логика OR: хотя бы одно ключевое слово
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'LIKE', "%{$keyword}%")
                      ->orWhere('description', 'LIKE', "%{$keyword}%");
                }
            });
        }

        return $query;
    }

    /**
     * Scope: Фильтр по источнику
     */
    public function scopeBySource($query, $sourceId)
    {
        if ($sourceId) {
            return $query->where('rss_source_id', $sourceId);
        }
        return $query;
    }

    /**
     * Scope: Фильтр по дате
     */
    public function scopeByDate($query, $date)
    {
        if ($date) {
            return $query->whereDate('published_at', $date);
        }
        return $query;
    }
}
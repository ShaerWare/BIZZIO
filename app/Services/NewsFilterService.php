<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Auth;

class NewsFilterService
{
    /**
     * Фильтрация новостей по ключевым словам пользователя
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $applyKeywords Применять ли фильтрацию по ключевым словам
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyUserKeywords($query, bool $applyKeywords = false)
    {
        if (!$applyKeywords || !Auth::check()) {
            return $query;
        }

        $keywords = Auth::user()->keywords()->pluck('keyword')->toArray();

        if (empty($keywords)) {
            return $query;
        }

        // Логика AND: все ключевые слова должны присутствовать
        return $query->searchByKeywords($keywords, 'all');
    }

    /**
     * Получить отфильтрованные новости
     * 
     * @param array $filters Массив фильтров (source_id, date, apply_keywords)
     * @param int $perPage Количество новостей на страницу
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredNews(array $filters = [], int $perPage = 20)
    {
        $query = News::with('rssSource')
            ->orderBy('published_at', 'desc');

        // Фильтр по источнику
        if (!empty($filters['source_id'])) {
            $query->bySource($filters['source_id']);
        }

        // Фильтр по дате
        if (!empty($filters['date'])) {
            $query->byDate($filters['date']);
        }

        // Фильтр по ключевым словам пользователя
        if (!empty($filters['apply_keywords'])) {
            $query = $this->applyUserKeywords($query, true);
        }

        return $query->paginate($perPage);
    }
}
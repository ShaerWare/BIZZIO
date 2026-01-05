<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\RSSSource;
use App\Services\NewsFilterService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    protected $filterService;

    public function __construct(NewsFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Отображение ленты новостей
     */
    public function index(Request $request)
    {
        // Валидация фильтров
        $request->validate([
            'source_id' => 'nullable|exists:rss_sources,id',
            'date' => 'nullable|date',
            'apply_keywords' => 'nullable|boolean',
        ]);

        // Параметры фильтрации
        $filters = [
            'source_id' => $request->input('source_id'),
            'date' => $request->input('date'),
            'apply_keywords' => $request->boolean('apply_keywords'),
        ];

        // Получение отфильтрованных новостей
        $news = $this->filterService->getFilteredNews($filters, 20);

        // Список источников для фильтра
        $sources = RSSSource::orderBy('name')->get();

        // Ключевые слова пользователя (если залогинен)
        $userKeywords = auth()->check() 
            ? auth()->user()->keywords()->pluck('keyword')->toArray() 
            : [];

        return view('news.index', compact('news', 'sources', 'filters', 'userKeywords'));
    }
}
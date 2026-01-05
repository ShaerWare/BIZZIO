<?php

namespace App\Orchid\Screens;

use App\Models\RSSSource;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class RSSSourceListScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'sources' => RSSSource::withCount('news')
                ->orderBy('name')
                ->paginate(20),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'RSS-источники';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Управление источниками новостей';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Добавить источник')
                ->icon('plus')
                ->route('platform.systems.rss-sources.create'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('sources', [
                TD::make('name', 'Название')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn (RSSSource $source) => Link::make($source->name)
                        ->route('platform.systems.rss-sources.edit', $source)),

                TD::make('url', 'URL')
                    ->render(fn (RSSSource $source) => 
                        '<a href="' . e($source->url) . '" target="_blank" class="text-blue-600 hover:text-blue-500">' 
                        . e(Str::limit($source->url, 50)) 
                        . '</a>'
                    ),

                TD::make('enabled', 'Статус')
                    ->sort()
                    ->render(fn (RSSSource $source) => 
                        $source->enabled 
                            ? '<span class="badge bg-success">Включён</span>' 
                            : '<span class="badge bg-secondary">Выключен</span>'
                    ),

                TD::make('news_count', 'Новостей')
                    ->sort()
                    ->render(fn (RSSSource $source) => number_format($source->news_count)),

                TD::make('last_parsed_at', 'Последний парсинг')
                    ->sort()
                    ->render(fn (RSSSource $source) => 
                        $source->last_parsed_at 
                            ? $source->last_parsed_at->format('d.m.Y H:i') 
                            : '<span class="text-muted">Никогда</span>'
                    ),

                TD::make('created_at', 'Создан')
                    ->sort()
                    ->render(fn (RSSSource $source) => $source->created_at->format('d.m.Y')),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn (RSSSource $source) => 
                        Link::make('Редактировать')
                            ->icon('pencil')
                            ->route('platform.systems.rss-sources.edit', $source)
                    ),
            ]),
        ];
    }
}
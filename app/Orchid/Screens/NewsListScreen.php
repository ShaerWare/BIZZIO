<?php

namespace App\Orchid\Screens;

use App\Models\News;
use App\Models\RSSSource;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class NewsListScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'news' => News::with('rssSource')
                ->orderBy('published_at', 'desc')
                ->paginate(50),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Новости';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Список всех новостей из RSS-лент';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('news', [
                TD::make('title', 'Заголовок')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn (News $news) => 
                        '<a href="' . e($news->link) . '" target="_blank" class="text-blue-600 hover:text-blue-500">' 
                        . e(Str::limit($news->title, 80)) 
                        . '</a>'
                    ),

                TD::make('rss_source_id', 'Источник')
                    ->sort()
                    ->filter(TD::FILTER_SELECT, RSSSource::pluck('name', 'id')->toArray())
                    ->render(fn (News $news) => $news->rssSource->name),

                TD::make('published_at', 'Дата публикации')
                    ->sort()
                    ->render(fn (News $news) => 
                        $news->published_at 
                            ? $news->published_at->format('d.m.Y H:i') 
                            : '<span class="text-muted">Н/Д</span>'
                    ),

                TD::make('created_at', 'Добавлено')
                    ->sort()
                    ->render(fn (News $news) => $news->created_at->diffForHumans()),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn (News $news) => 
                        Button::make('Удалить')
                            ->icon('trash')
                            ->confirm('Удалить эту новость?')
                            ->method('remove', ['id' => $news->id])
                    ),
            ]),
        ];
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    public function remove(Request $request)
    {
        News::findOrFail($request->get('id'))->delete();

        Alert::info('Новость удалена');
    }
}
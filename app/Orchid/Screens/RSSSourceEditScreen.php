<?php

namespace App\Orchid\Screens;

use App\Models\RSSSource;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class RSSSourceEditScreen extends Screen
{
    /**
     * @var RSSSource
     */
    public $source;

    /**
     * Query data.
     */
    public function query(RSSSource $source): iterable
    {
        return [
            'source' => $source,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->source->exists ? 'Редактирование RSS-источника' : 'Создание RSS-источника';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('save'),

            Button::make('Удалить')
                ->icon('trash')
                ->method('remove')
                ->canSee($this->source->exists)
                ->confirm('Вы уверены, что хотите удалить этот источник?'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('source.name')
                    ->title('Название источника')
                    ->placeholder('Например: CNews')
                    ->required()
                    ->help('Название для отображения на сайте'),

                Input::make('source.url')
                    ->title('URL RSS-ленты')
                    ->type('url')
                    ->placeholder('https://example.com/rss.xml')
                    ->required()
                    ->help('Полный URL до RSS-ленты'),

                Switcher::make('source.enabled')
                    ->title('Статус')
                    ->sendTrueOrFalse()
                    ->help('Включить/выключить парсинг этого источника'),
            ]),
        ];
    }

    /**
     * @param RSSSource $source
     * @param Request   $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(RSSSource $source, Request $request)
    {
        $request->validate([
            'source.name' => 'required|string|max:255',
            'source.url' => 'required|url|max:500',
            'source.enabled' => 'boolean',
        ]);

        $source->fill($request->get('source'))->save();

        Alert::info('RSS-источник сохранён');

        return redirect()->route('platform.systems.rss-sources');
    }

    /**
     * @param RSSSource $source
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(RSSSource $source)
    {
        $source->delete();

        Alert::info('RSS-источник удалён');

        return redirect()->route('platform.systems.rss-sources');
    }
}
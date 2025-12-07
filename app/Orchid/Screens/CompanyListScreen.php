<?php

namespace App\Orchid\Screens;

use App\Models\Company;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class CompanyListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [
            'companies' => Company::with(['industry', 'creator'])
                ->filters()
                ->defaultSort('id', 'desc')
                ->paginate(20),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Компании';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Список всех компаний в системе';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Создать компанию')
                ->icon('plus')
                ->route('platform.companies.create'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('companies', [
                TD::make('id', 'ID')
                    ->sort()
                    ->filter(TD::FILTER_TEXT),

                TD::make('name', 'Название')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Company $company) => Link::make($company->name)
                        ->route('platform.companies.edit', $company->id)),

                TD::make('inn', 'ИНН')
                    ->sort()
                    ->filter(TD::FILTER_TEXT),

                TD::make('industry', 'Отрасль')
                    ->render(fn(Company $company) => $company->industry?->name ?? '—'),

                TD::make('is_verified', 'Верификация')
                    ->sort()
                    ->render(fn(Company $company) => $company->is_verified
                        ? '<span class="badge bg-success">Верифицирована</span>'
                        : '<span class="badge bg-warning">Не верифицирована</span>'),

                TD::make('creator', 'Создатель')
                    ->render(fn(Company $company) => $company->creator->name),

                TD::make('created_at', 'Создана')
                    ->sort()
                    ->render(fn(Company $company) => $company->created_at->format('d.m.Y H:i')),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn(Company $company) => Link::make('Редактировать')
                        ->icon('pencil')
                        ->route('platform.companies.edit', $company->id)),
            ]),
        ];
    }
}
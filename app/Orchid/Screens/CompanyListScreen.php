<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class CompanyListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(Request $request): iterable
    {
        $query = Company::with(['industry', 'creator']);

        if ($request->get('verification') === 'pending') {
            $query->where('is_verified', false);
        }

        return [
            'companies' => $query
                ->orderBy('is_verified', 'asc')  // Неверифицированные сверху
                ->orderBy('id', 'desc')
                ->paginate(20),
            'unverified_count' => Company::where('is_verified', false)->count(),
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
        $count = Company::where('is_verified', false)->count();

        if ($count > 0) {
            return "⚠ Ожидают верификации: {$count}";
        }

        return 'Все компании верифицированы';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        $unverifiedCount = Company::where('is_verified', false)->count();

        return [
            Link::make("Ожидают верификации ({$unverifiedCount})")
                ->icon('shield')
                ->route('platform.companies.list', ['verification' => 'pending'])
                ->canSee($unverifiedCount > 0),

            Link::make('Все компании')
                ->icon('list')
                ->route('platform.companies.list'),

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
                    ->cantHide(),

                TD::make('name', 'Название')
                    ->sort()
                    ->render(fn (Company $company) => Link::make($company->name)
                        ->route('platform.companies.edit', $company->id)),

                TD::make('inn', 'ИНН')
                    ->sort(),

                TD::make('industry', 'Отрасль')
                    ->render(fn (Company $company) => $company->industry?->name ?? '—'),

                TD::make('is_verified', 'Верификация')
                    ->sort()
                    ->render(fn (Company $company) => $company->is_verified
                        ? '<span class="badge bg-success">Верифицирована</span>'
                        : '<span class="badge bg-danger text-white">⚠ Ожидает верификации</span>'),

                TD::make('creator', 'Создатель')
                    ->render(fn (Company $company) => $company->creator?->name ?? '—'),

                TD::make('created_at', 'Создана')
                    ->sort()
                    ->render(fn (Company $company) => $company->created_at->format('d.m.Y H:i')),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn (Company $company) => Link::make('Редактировать')
                        ->icon('pencil')
                        ->route('platform.companies.edit', $company->id)),
            ]),
        ];
    }
}

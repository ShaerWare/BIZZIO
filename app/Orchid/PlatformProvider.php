<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            Menu::make(__('Юзеры'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Настройки')),

            Menu::make(__('Роли пользователей'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),

            Menu::make(__('Компании'))
                ->icon('briefcase')
                ->route('platform.companies.list'),

            Menu::make(__('Проекты'))
                ->icon('rocket')
                ->route('platform.projects')
                ->divider(),

            Menu::make('Запросы котировок')
                ->icon('stars')
                ->route('platform.rfqs.list')
                ->title('Тендеры и аукционы'),

            Menu::make('Аукционы')
                ->icon('trophy')
                ->route('platform.auctions.list'),

            Menu::make('Новости')
                ->icon('feed')
                ->route('platform.systems.news')
                //->permission('platform.systems.news')
                ->title('Контент'),

            Menu::make('RSS-источники')
                ->icon('globe')
                ->route('platform.systems.rss-sources')
                //->permission('platform.systems.rss-sources'),

            Menu::make('Статус проекта по ТЗ')
                ->title('Документация')
                ->icon('bs.box-arrow-up-right')
                ->url('https://docs.google.com/document/d/1I1sToGlYhBs8LfqvqQUpwbPSc3vi37kQRokgvtSpSzU/edit?usp=sharing')
                ->target('_blank'),

            Menu::make('Документация проекта')
                ->icon('bs.box-arrow-up-right')
                ->url('https://github.com/ShaerWare/BIZZIO')
                ->target('_blank'),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),

            ItemPermission::group(__('Новости'))
                ->addPermission('platform.systems.news', __('Просмотр и управление новостями'))
                ->addPermission('platform.systems.rss-sources', __('Управление RSS-источниками')),
        ];
    }
}
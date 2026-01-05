<?php

declare(strict_types=1);

use App\Orchid\Screens\Examples\ExampleActionsScreen;
use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleGridScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use App\Orchid\Screens\CompanyListScreen;
use App\Orchid\Screens\CompanyEditScreen;
use App\Orchid\Screens\ProjectListScreen;
use App\Orchid\Screens\ProjectEditScreen;
use App\Models\Project;
use App\Orchid\Screens\RfqListScreen;
use App\Orchid\Screens\RfqEditScreen;
use App\Models\Company;
use App\Orchid\Screens\AuctionListScreen;
use App\Orchid\Screens\AuctionEditScreen;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

//  ==================== Main ==================== 
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

//  ==================== Platform > Profile ==================== 
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

//  ==================== Platform > System > Users > User ==================== 
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

//  ==================== Platform > System > Users > Create ==================== 
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

//  ==================== Platform > System > Users ==================== 
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

//  ==================== Platform > System > Roles > Role ==================== 
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

//  ==================== Platform > System > Roles > Create ==================== 
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

//  ==================== Platform > System > Roles ==================== 
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

//  ==================== Example... ==================== 
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Example Screen'));

Route::screen('/examples/form/fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('/examples/form/advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');
Route::screen('/examples/form/editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('/examples/form/actions', ExampleActionsScreen::class)->name('platform.example.actions');

Route::screen('/examples/layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('/examples/grid', ExampleGridScreen::class)->name('platform.example.grid');
Route::screen('/examples/charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('/examples/cards', ExampleCardsScreen::class)->name('platform.example.cards');

// Route::screen('idea', Idea::class, 'platform.screens.idea');

// ==================== Компании ====================
Route::screen('companies', CompanyListScreen::class)->name('platform.companies.list');
Route::screen('companies/create', CompanyEditScreen::class)->name('platform.companies.create');
Route::screen('companies/{company}/edit', CompanyEditScreen::class)->name('platform.companies.edit');

// ==================== Список проектов ====================
Route::screen('projects', ProjectListScreen::class)
    ->name('platform.projects')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')           // ← от главного меню админки
        ->push('Проекты', route('platform.projects'))
    );

// ==================== Создание проекта ====================
Route::screen('projects/create', ProjectEditScreen::class)
    ->name('platform.projects.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.projects')
        ->push('Создание проекта', route('platform.projects.create'))
    );

// ==================== Редактирование проекта ====================
Route::screen('projects/{project}', ProjectEditScreen::class)
    ->name('platform.projects.edit')
    ->breadcrumbs(fn (Trail $trail, Project $project) => $trail
        ->parent('platform.projects')
        ->push($project->name, route('platform.projects.edit', $project))
    );

// ==================== RFQ Routes ====================
Route::screen('rfqs', RfqListScreen::class)
    ->name('platform.rfqs.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Запросы котировок', route('platform.rfqs.list')));

Route::screen('rfqs/create', RfqEditScreen::class)
    ->name('platform.rfqs.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.rfqs.list')
        ->push('Создать', route('platform.rfqs.create')));

Route::screen('rfqs/{rfq}/edit', RfqEditScreen::class)
    ->name('platform.rfqs.edit')
    ->breadcrumbs(fn (Trail $trail, $rfq) => $trail
        ->parent('platform.rfqs.list')
        ->push($rfq->number, route('platform.rfqs.edit', $rfq)));

Route::bind('company', function ($value) {
    // Если это число - ищем по id (для админки)
    if (is_numeric($value)) {
        return Company::findOrFail($value);
    }
    // Если строка - ищем по slug (для публичных роутов)
    return Company::where('slug', $value)->firstOrFail();
});
// ==================== Аукционы ====================
Route::screen('auctions', AuctionListScreen::class)
    ->name('platform.auctions.list');

Route::screen('auctions/create', AuctionEditScreen::class)
    ->name('platform.auctions.create');

Route::screen('auctions/{auction}/edit', AuctionEditScreen::class)
    ->name('platform.auctions.edit');

// ==================== RSS-ИСТОЧНИКИ ====================
Route::screen('rss-sources', RSSSourceListScreen::class)
    ->name('platform.systems.rss-sources')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('RSS-источники', route('platform.systems.rss-sources')));

Route::screen('rss-sources/create', RSSSourceEditScreen::class)
    ->name('platform.systems.rss-sources.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.rss-sources')
        ->push('Создание', route('platform.systems.rss-sources.create')));

Route::screen('rss-sources/{source}/edit', RSSSourceEditScreen::class)
    ->name('platform.systems.rss-sources.edit')
    ->breadcrumbs(fn (Trail $trail, $source) => $trail
        ->parent('platform.systems.rss-sources')
        ->push('Редактирование', route('platform.systems.rss-sources.edit', $source)));

// ==================== НОВОСТИ ====================
Route::screen('news', NewsListScreen::class)
    ->name('platform.systems.news')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Новости', route('platform.systems.news')));
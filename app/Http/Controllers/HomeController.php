<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\News;
use App\Models\Project;
use App\Models\Rfq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * #181 Единая точка входа: на `/` показывается гостевая или авторизованная главная v26
 * в зависимости от сессии. Прежний адрес `/dashboard` редиректит сюда же.
 */
class HomeController extends Controller
{
    /**
     * Сервисы, которых ещё нет: карточки остаются видимыми, клик уходит в аналитику
     * (future_service_interest) и не ведёт на несуществующую страницу.
     *
     * @var list<array{id: string, name: string, icon: string}>
     */
    private const FUTURE_SERVICES = [
        ['id' => 'articles', 'name' => 'Статьи', 'icon' => 'file'],
        ['id' => 'jobs', 'name' => 'Работа', 'icon' => 'guest-briefcase'],
        ['id' => 'goods_services', 'name' => 'Товары и услуги', 'icon' => 'guest-basket'],
        ['id' => 'events', 'name' => 'События и мероприятия', 'icon' => 'guest-calendar'],
        ['id' => 'experts', 'name' => 'Эксперты', 'icon' => 'guest-user'],
        ['id' => 'counterparty_check', 'name' => 'Проверка контрагентов', 'icon' => 'guest-shield'],
        ['id' => 'education', 'name' => 'Обучение', 'icon' => 'guest-graduation'],
        ['id' => 'ai_assistant', 'name' => 'AI-помощник', 'icon' => 'guest-bot'],
        ['id' => 'business_sale', 'name' => 'Покупка-продажа бизнеса', 'icon' => 'business-transfer'],
        ['id' => 'commercial_property', 'name' => 'Коммерческая недвижимость', 'icon' => 'building'],
    ];

    public function index(): View|RedirectResponse
    {
        // Прежний лендинг показывал формы входа и регистрации прямо на «/» по ?mode=…
        // Теперь это отдельные адреса (AUTH_URL), а старые ссылки переводим редиректом.
        if (! auth()->check() && in_array(request('mode'), ['login', 'register'], true)) {
            return redirect(request('mode') === 'register'
                ? config('app.register_url')
                : config('app.auth_url'));
        }

        $shared = [
            'futureServices' => self::FUTURE_SERVICES,
            'authUrl' => config('app.auth_url'),
            'registerUrl' => config('app.register_url'),
        ];

        if (auth()->check()) {
            return view('home.authorized', array_merge(
                $shared,
                app(DashboardController::class)->dashboardData(auth()->user()),
                ['events' => $this->publicEvents()],
            ));
        }

        return view('home.guest', array_merge($shared, [
            'events' => $this->publicEvents(),
            'latestNews' => News::latest('published_at')->take(3)->get(),
        ]));
    }

    /**
     * Блок «Актуальное в Bizzio» — публичные процедуры и проекты, доступные без регистрации.
     *
     * @return Collection<int, array<string, string>>
     */
    private function publicEvents(): Collection
    {
        $procedures = Rfq::query()
            ->where('status', 'active')
            ->where('type', 'open')
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (Rfq $rfq) => [
                'type' => 'procurement',
                'title' => $rfq->title,
                'meta' => 'Приём заявок до '.optional($rfq->end_date)->format('d.m.Y'),
                'tag' => 'Закупка',
                'tag_class' => 'purple',
                'url' => route('rfqs.show', $rfq),
            ]);

        $auctions = Auction::query()
            ->where('status', 'active')
            ->where('type', 'open')
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (Auction $auction) => [
                'type' => 'procurement',
                'title' => $auction->title,
                'meta' => 'Приём заявок до '.optional($auction->end_date)->format('d.m.Y'),
                'tag' => 'Аукцион',
                'tag_class' => 'purple',
                'url' => route('auctions.show', $auction),
            ]);

        $projects = Project::query()
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (Project $project) => [
                'type' => 'project',
                'title' => $project->name,
                'meta' => 'Проект ищет партнёров',
                'tag' => 'Проект',
                'tag_class' => 'green-tag',
                'url' => route('projects.show', $project),
            ]);

        return $procedures->concat($auctions)->concat($projects)->take(3)->values();
    }
}

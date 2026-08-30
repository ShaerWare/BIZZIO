<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionInvitation;
use App\Models\Company;
use App\Models\CompanyJoinRequest;
use App\Models\Friendship;
use App\Models\News;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\RfqInvitation;
use App\Models\User;
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
                ['events' => $this->personalEvents(auth()->user())],
            ));
        }

        return view('home.guest', array_merge($shared, [
            'events' => $this->publicEvents(),
            'latestNews' => News::latest('published_at')->take(3)->get(),
        ]));
    }

    /**
     * #181 Блок «Актуальное в Bizzio» авторизованного пользователя: три последних события
     * из разделов Закупки, Друзья, Компании и Проекты. Если личных событий не набралось,
     * блок добирается публичными — пустым он оставаться не должен.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function personalEvents(User $user): Collection
    {
        $companyIds = $user->moderatedCompanies()->pluck('companies.id');

        // Закупки: приглашения компаниям пользователя (запрос цен и аукционы).
        $procurements = RfqInvitation::query()
            ->whereIn('company_id', $companyIds)
            ->with('rfq')
            ->latest()
            ->take(3)
            ->get()
            ->filter(fn (RfqInvitation $invitation) => $invitation->rfq !== null)
            ->map(fn (RfqInvitation $invitation) => [
                'type' => 'procurement',
                'title' => 'Вас пригласили в закупку',
                'meta' => $invitation->rfq->title,
                'tag' => 'Закупка',
                'tag_class' => 'purple',
                'url' => route('rfqs.show', $invitation->rfq_id),
                'created_at' => $invitation->created_at,
            ])
            ->concat(AuctionInvitation::query()
                ->whereIn('company_id', $companyIds)
                ->with('auction')
                ->latest()
                ->take(3)
                ->get()
                ->filter(fn (AuctionInvitation $invitation) => $invitation->auction !== null)
                ->map(fn (AuctionInvitation $invitation) => [
                    'type' => 'procurement',
                    'title' => 'Вас пригласили в аукцион',
                    'meta' => $invitation->auction->title,
                    'tag' => 'Закупка',
                    'tag_class' => 'purple',
                    'url' => route('auctions.show', $invitation->auction_id),
                    'created_at' => $invitation->created_at,
                ]));

        // Друзья: входящие приглашения в контакты.
        $friends = Friendship::query()
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with('sender')
            ->latest()
            ->take(3)
            ->get()
            ->filter(fn (Friendship $friendship) => $friendship->sender !== null)
            ->map(fn (Friendship $friendship) => [
                'type' => 'friend',
                'title' => 'Новое приглашение в контакты',
                'meta' => $friendship->sender->full_name,
                'tag' => 'Связи',
                'tag_class' => 'blue-tag',
                'url' => route('friends.index'),
                'created_at' => $friendship->created_at,
            ]);

        // Компании: заявки на вступление в компании, которыми управляет пользователь.
        $companies = CompanyJoinRequest::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', 'pending')
            ->with('company')
            ->latest()
            ->take(3)
            ->get()
            ->filter(fn (CompanyJoinRequest $request) => $request->company !== null)
            ->map(fn (CompanyJoinRequest $request) => [
                'type' => 'company',
                'title' => 'Заявка на вступление в компанию',
                'meta' => $request->company->name,
                'tag' => 'Компания',
                'tag_class' => 'blue-tag',
                'url' => route('companies.show', $request->company),
                'created_at' => $request->created_at,
            ]);

        // Проекты: последние проекты компаний пользователя.
        $projects = Project::query()
            ->whereIn('company_id', $companyIds)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Project $project) => [
                'type' => 'project',
                'title' => $project->name,
                'meta' => 'Проект вашей компании',
                'tag' => 'Проект',
                'tag_class' => 'green-tag',
                'url' => route('projects.show', $project),
                'created_at' => $project->created_at,
            ]);

        $events = $procurements
            ->concat($friends)
            ->concat($companies)
            ->concat($projects)
            ->sortByDesc('created_at')
            ->take(3)
            ->values();

        if ($events->count() < 3) {
            $events = $events->concat($this->publicEvents())->take(3)->values();
        }

        return $events;
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

        // Заказчик просил в «Актуальном» состав из четырёх разделов. «Друзей» у гостя нет
        // по определению — сессии нет, поэтому для него это закупки, компании и проекты.
        $companies = Company::verified()
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (Company $company) => [
                'type' => 'company',
                'title' => $company->name,
                'meta' => 'Новая компания на площадке',
                'tag' => 'Компания',
                'tag_class' => 'blue-tag',
                'url' => route('companies.show', $company),
            ]);

        return $procedures
            ->concat($auctions)
            ->concat($companies)
            ->concat($projects)
            ->take(3)
            ->values();
    }
}

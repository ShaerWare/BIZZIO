{{-- #181 Мобильная главная авторизованного пользователя (эталон authorized-mobile).

     Структура эталона: #bizzio-mobile-v1[data-open] → .bz-phone → шапка, .bz-main, нижняя
     навигация. Обёртка .bz-phone обязательна: .bz-header и .bz-bottom позиционируются
     fixed, и именно она компенсирует их высоту отступами. Панели открываются атрибутом
     data-open на корне (не data-panel, как в десктопной части). --}}
<div id="bizzio-mobile-v1" data-open="none">
    <section class="bz-phone" aria-label="Главная Bizzio">
        <header class="bz-header">
            <button class="bz-icon-button" type="button" data-open-panel="menu" aria-label="Открыть меню"><svg><use href="#menu"/></svg></button>
            <a class="bz-logo" href="{{ route('home') }}"><img class="bz-logo-image" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
            <a class="bz-icon-button" href="{{ route('notifications.index') }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
            <button class="bz-icon-button" type="button" data-open-panel="services" aria-label="Открыть сервисы"><svg><use href="#grid"/></svg></button>
        </header>

        <main class="bz-main">
            <section class="bz-panel bz-shortcuts" aria-label="Быстрые ссылки">
                <a class="bz-shortcut" href="{{ route('friends.index') }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-find-friends-v4.png" alt=""><span>Найти друзей</span></a>
                <a class="bz-shortcut" href="{{ route('tenders.index') }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-find-procurement-v4.png" alt=""><span>Найти закупку</span></a>
                <a class="bz-shortcut" href="{{ route('rfqs.create', ['procedure' => 'commercial']) }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-create-procurement-v4.png" alt=""><span>Создать закупку</span></a>
                <a class="bz-shortcut" href="{{ route('news.index') }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-news-v4.png" alt=""><span>Новости</span></a>
            </section>

            <section class="bz-panel bz-events" aria-label="Мои закупки">
                <div class="bz-events-title">Мои закупки</div>
                @forelse($myTenders as $tender)
                    <a class="bz-event" href="{{ $tender['url'] }}">
                        <div class="bz-event-icon"><img class="bz-event-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""></div>
                        <div>
                            <div class="bz-event-title">{{ \Illuminate\Support\Str::limit($tender['title'], 40) }}</div>
                            <div class="bz-meta">{{ $tender['status_label'] }}</div>
                        </div>
                    </a>
                @empty
                    <div class="bz-meta">У вас пока нет закупок</div>
                @endforelse
            </section>

            <section class="bz-panel bz-composer" aria-label="Лента">
                <div class="bz-events-title">Лента</div>
                @forelse($recentPosts as $post)
                    <div class="bz-post">
                        <div class="bz-post-head">
                            <span class="bz-post-name">{{ $post->user->full_name }}</span>
                            <span class="bz-meta">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="bz-post-copy">{{ $post->body }}</p>
                    </div>
                @empty
                    <div class="bz-meta">В ленте пока пусто</div>
                @endforelse
            </section>

            <section class="bz-panel bz-news" aria-label="Новости">
                <div class="bz-news-title">Новости</div>
                @forelse($latestNews as $item)
                    <a class="bz-news-row" href="{{ $item->link }}" target="_blank" rel="noopener">
                        <span class="bz-news-dot" style="background:#0877ff"></span>
                        <span>{{ \Illuminate\Support\Str::limit($item->title, 60) }}</span>
                        <span class="bz-news-time">{{ optional($item->published_at)->diffForHumans() }}</span>
                    </a>
                @empty
                    <div class="bz-meta">Новостей пока нет</div>
                @endforelse
            </section>
        </main>

        {{-- Нижняя навигация: в эталоне ровно четыре ячейки (grid 4×1fr) --}}
        <nav class="bz-bottom" aria-label="Основная навигация">
            <a class="bz-bottom-item bz-current" href="{{ route('home') }}"><svg><use href="#auth-home"/></svg><span>Главная</span></a>
            <a class="bz-bottom-item" href="{{ route('tenders.index') }}"><svg><use href="#gavel"/></svg><span>Закупки</span></a>
            <a class="bz-bottom-item" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><span>Компании</span></a>
            <a class="bz-bottom-item" href="{{ route('users.show', $viewer) }}"><svg><use href="#auth-user"/></svg><span>Профиль</span></a>
        </nav>
    </section>

    <div class="bz-dim" data-close-panels></div>

    <aside class="bz-drawer bz-menu-drawer" aria-label="Меню">
        <div class="bz-drawer-head">
            <span>Меню</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть меню"><svg><use href="#auth-x"/></svg></button>
        </div>
        <div class="bz-drawer-section">Моя работа</div>
        <a class="bz-nav-row bz-current" href="{{ route('home') }}"><svg><use href="#auth-home"/></svg>Главная</a>
        <a class="bz-nav-row" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
        <a class="bz-nav-row" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg>Контакты</a>
        <a class="bz-nav-row" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
        <a class="bz-nav-row" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
        <a class="bz-nav-row" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>

        <div class="bz-drawer-section">Мои закупки</div>
        <a class="bz-nav-row" href="{{ route('tenders.my') }}"><svg><use href="#gavel"/></svg>Мои закупки</a>
        <a class="bz-nav-row" href="{{ route('tenders.invitations.my') }}"><svg><use href="#clip"/></svg>Мои приглашения</a>
        <a class="bz-nav-row" href="{{ route('tenders.bids.my') }}"><svg><use href="#file"/></svg>Мои заявки</a>

        <div class="bz-drawer-section">Настройки и поддержка</div>
        <a class="bz-nav-row" href="{{ route('profile.edit') }}"><svg><use href="#auth-settings"/></svg>Настройки профиля</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="bz-nav-row" type="submit"><svg><use href="#auth-logout"/></svg>Выйти</button>
        </form>
    </aside>

    <aside class="bz-drawer bz-services-drawer" aria-label="Сервисы Bizzio">
        <div class="bz-drawer-head">
            <span>Сервисы Bizzio</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть сервисы"><svg><use href="#auth-x"/></svg></button>
        </div>
        <div class="bz-service-label">Текущие сервисы</div>
        <div class="bz-service-grid">
            <a class="bz-service-tile" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
            <a class="bz-service-tile" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>
            <a class="bz-service-tile" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
            <a class="bz-service-tile" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
            <a class="bz-service-tile" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg>Контакты</a>
            <a class="bz-service-tile" href="{{ route('subscriptions.index') }}"><svg><use href="#auth-bookmark"/></svg>Подписки</a>
        </div>

        <div class="bz-service-label">Будущие сервисы · нажмите, чтобы поддержать</div>
        <div class="bz-service-grid">
            @foreach($futureServices as $service)
                <div class="bz-service-tile"
                     data-future-service="{{ $service['id'] }}"
                     data-service-name="{{ $service['name'] }}"
                     data-placement="auth_mobile_drawer">
                    <svg><use href="#{{ $service['icon'] }}"/></svg>{{ $service['name'] }}
                    <span class="bz-plus">+</span>
                </div>
            @endforeach
        </div>
    </aside>
</div>

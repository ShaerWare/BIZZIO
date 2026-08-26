{{-- #181 Мобильная гостевая главная (эталон guest-mobile).

     Как и в авторизованной версии, обязательна обёртка .bz-phone: шапка и нижняя навигация
     позиционируются fixed, отступы под них задаёт именно она. Панели открываются
     атрибутом data-open на #bizzio-mobile-v1. --}}
<div id="bizzio-mobile-v1" data-open="none">
    <section class="bz-phone" aria-label="Главная Bizzio для гостя">
        <header class="bz-header gm-header">
            <button class="bz-icon-button" type="button" data-open-panel="menu" aria-label="Открыть меню"><svg><use href="#menu"/></svg></button>
            <a class="bz-logo" href="{{ route('home') }}"><img class="bz-logo-image" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
            <a class="bz-icon-button" href="{{ $authUrl }}" aria-label="Войти"><svg><use href="#guest-user"/></svg></a>
            <button class="bz-icon-button" type="button" data-open-panel="services" aria-label="Открыть сервисы"><svg><use href="#grid"/></svg></button>
        </header>

        <main class="bz-main">
            <section class="bz-panel gm-welcome" aria-label="Добро пожаловать">
                <div class="gm-welcome-copy">
                    <h1>Добро пожаловать в Bizzio!</h1>
                    <p>Находите компании и специалистов, участвуйте в закупках и проектах, следите за деловыми событиями.</p>
                    <a class="gm-register" href="{{ $registerUrl }}">Регистрация</a>
                </div>
            </section>

            <section class="bz-panel" aria-label="Сервисы">
                <div class="bz-service-label">Текущие сервисы</div>
                <div class="bz-service-grid">
                    <a class="bz-service-tile" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
                    <a class="bz-service-tile" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>
                    <a class="bz-service-tile" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
                    <a class="bz-service-tile" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
                    <a class="bz-service-tile" href="{{ $registerUrl }}"><svg><use href="#users"/></svg>Контакты</a>
                </div>
            </section>

            <section class="bz-panel" aria-label="Будущие сервисы">
                <div class="bz-service-label">Какой сервис запустить следующим?</div>
                <div class="gm-future-grid">
                    @foreach($futureServices as $service)
                        <div class="gm-future"
                             data-future-service="{{ $service['id'] }}"
                             data-service-name="{{ $service['name'] }}"
                             data-placement="guest_mobile">
                            <div class="gm-future-top"><svg><use href="#{{ $service['icon'] }}"/></svg></div>
                            <div class="gm-future-name">{{ $service['name'] }}</div>
                            <button class="gm-interest" type="button" data-interest-button>Мне интересно</button>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="bz-panel bz-events" aria-label="Актуальное в Bizzio">
                <div class="bz-events-title">Актуальное в Bizzio</div>
                @forelse($events as $event)
                    <a class="bz-event" href="{{ $event['url'] }}">
                        <div class="bz-event-icon">
                            @if($event['type'] === 'procurement')
                                <img class="bz-event-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">
                            @else
                                <svg><use href="#clip"/></svg>
                            @endif
                        </div>
                        <div>
                            <div class="bz-event-title">{{ \Illuminate\Support\Str::limit($event['title'], 40) }}</div>
                            <div class="bz-meta">{{ $event['meta'] }}</div>
                        </div>
                    </a>
                @empty
                    <div class="bz-meta">Пока нет активных процедур и проектов</div>
                @endforelse
            </section>

            <section class="bz-panel bz-news" aria-label="Новости">
                <div class="bz-news-title">Новости</div>
                @forelse($latestNews as $item)
                    <a class="bz-news-row" href="{{ $item->link }}" target="_blank" rel="noopener">
                        <span class="bz-news-dot" style="background:#60739f"></span>
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
            <a class="bz-bottom-item bz-current" href="{{ route('home') }}"><svg><use href="#guest-home"/></svg><span>Главная</span></a>
            <a class="bz-bottom-item" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><span>Компании</span></a>
            <a class="bz-bottom-item" href="{{ route('tenders.index') }}"><svg><use href="#gavel"/></svg><span>Закупки</span></a>
            <a class="bz-bottom-item" href="{{ $authUrl }}"><svg><use href="#guest-user"/></svg><span>Войти</span></a>
        </nav>
    </section>

    <div class="bz-dim" data-close-panels></div>

    <aside class="bz-drawer bz-menu-drawer" aria-label="Меню">
        <div class="bz-drawer-head">
            <span>Меню</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть меню"><svg><use href="#guest-x"/></svg></button>
        </div>
        <div class="bz-drawer-section">Моя работа</div>
        <a class="bz-nav-row bz-current" href="{{ route('home') }}"><svg><use href="#guest-home"/></svg>Главная</a>
        <a class="bz-nav-row" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
        <a class="bz-nav-row" href="{{ $registerUrl }}"><svg><use href="#users"/></svg>Контакты</a>
        <a class="bz-nav-row" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
        <a class="bz-nav-row" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
        <a class="bz-nav-row" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>

        <div class="bz-drawer-section">Настройки и поддержка</div>
        <a class="bz-nav-row" href="{{ $authUrl }}"><svg><use href="#help-chat"/></svg>Помощь и обратная связь</a>

        <div class="gm-menu-note">Персональные возможности станут доступны после регистрации.</div>
        <div class="gm-menu-actions">
            <a class="gm-login" href="{{ $authUrl }}">Войти</a>
            <a class="gm-register-button" href="{{ $registerUrl }}">Регистрация</a>
        </div>
    </aside>

    <aside class="bz-drawer bz-services-drawer" aria-label="Сервисы Bizzio">
        <div class="bz-drawer-head">
            <span>Сервисы Bizzio</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть сервисы"><svg><use href="#guest-x"/></svg></button>
        </div>
        <div class="bz-service-label">Текущие сервисы</div>
        <div class="bz-service-grid">
            <a class="bz-service-tile" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
            <a class="bz-service-tile" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>
            <a class="bz-service-tile" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
            <a class="bz-service-tile" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
            <a class="bz-service-tile" href="{{ $registerUrl }}"><svg><use href="#users"/></svg>Контакты</a>
        </div>

        <div class="bz-service-label">Будущие сервисы · нажмите, чтобы поддержать</div>
        <div class="bz-service-grid">
            @foreach($futureServices as $service)
                <div class="bz-service-tile"
                     data-future-service="{{ $service['id'] }}"
                     data-service-name="{{ $service['name'] }}"
                     data-placement="guest_mobile_drawer">
                    <svg><use href="#{{ $service['icon'] }}"/></svg>{{ $service['name'] }}
                    <span class="bz-plus">+</span>
                </div>
            @endforeach
        </div>
    </aside>
</div>

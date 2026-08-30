{{-- #181 Шапка Bizzio v26 для внутренних страниц сайта.

     Заказчик просил распространить новое меню на все разделы, а прежние выпадающие
     меню второго уровня («Закупки», «Новости») перенести в меню раздела — их состав
     лежит в partials/v26/menu-items.

     Обёртка `.v26-chrome` обязательна: стили шапки скоупированы в v26-chrome.css,
     иначе общие селекторы прототипа (.card, .service, .link) поехали бы по контенту
     страниц, свёрстанному на Tailwind. --}}
@php
    $viewer = auth()->user();
@endphp

<div class="v26-chrome" data-v26-root data-auth-state="{{ $viewer ? 'authorized' : 'guest' }}">
    {{-- ===================== DESKTOP / TABLET ===================== --}}
    <div class="v26-desktop">
        <div class="page{{ $viewer ? '' : ' guest-page' }}" data-panel="none">
            <header class="topbar{{ $viewer ? '' : ' guest-topbar' }}">
                <div class="brand">
                    <a href="{{ route('home') }}"><img class="brand-logo" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
                </div>
                <button class="top-btn" type="button" data-panel-toggle="menu">
                    <svg class="hamb" aria-hidden="true"><use href="#menu"/></svg><span>Меню</span>
                </button>

                @auth
                    <form class="search" method="GET" action="{{ route('search.index') }}">
                        <svg><use href="#search"/></svg>
                        <input type="text" name="q" placeholder="Компании, проекты, люди, закупки и новости"
                               data-placeholder-full="Компании, проекты, люди, закупки и новости"
                               data-placeholder-short="Поиск в Bizzio"
                               value="{{ request('q') }}" aria-label="Поиск по Bizzio">
                    </form>
                @else
                    <a class="search" href="{{ config('app.auth_url') }}">
                        <svg><use href="#search"/></svg>
                        <span data-placeholder-full="Компании, проекты, люди, закупки и новости"
                              data-placeholder-short="Поиск в Bizzio">Компании, проекты, люди, закупки и новости</span>
                    </a>
                @endauth

                <div class="top-actions">
                    @auth
                        <a class="icon-btn" href="{{ route('profile.edit') }}#feedback" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
                        <a class="plain-icon" href="{{ route('notifications.index') }}" aria-label="Сообщения"><svg><use href="#chat"/></svg></a>
                        <a class="plain-icon" href="{{ route('notifications.index') }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
                        <button class="services" type="button" data-panel-toggle="services">
                            <svg class="nav-icon" aria-hidden="true"><use href="#grid"/></svg><span>Сервисы</span>
                        </button>
                        <div class="divider"></div>
                        <div class="auth-profile-menu">
                            <button class="auth-profile-trigger" type="button" aria-expanded="false">
                                <span class="photo header"><img src="{{ $viewer->avatar_url }}" alt=""></span>
                                <span>{{ $viewer->name }}</span>
                                <svg class="chev" aria-hidden="true"><use href="#chevron-down"/></svg>
                            </button>
                            <div class="auth-profile-popover">
                                <a class="auth-profile-action" href="{{ route('users.show', $viewer) }}"><svg><use href="#auth-user"/></svg>Профиль</a>
                                <a class="auth-profile-action" href="{{ route('profile.edit') }}"><svg><use href="#auth-settings"/></svg>Настройки</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="auth-profile-action" type="submit"><svg><use href="#auth-logout"/></svg>Выйти</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a class="icon-btn" href="{{ config('app.auth_url') }}" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
                        <a class="plain-icon" href="{{ config('app.auth_url') }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
                        <a class="guest-login" href="{{ config('app.auth_url') }}">Войти</a>
                        <a class="guest-register" href="{{ config('app.register_url') }}">Регистрация</a>
                        <div class="divider"></div>
                        <button class="services" type="button" data-panel-toggle="services">
                            <svg class="nav-icon" aria-hidden="true"><use href="#grid"/></svg><span>Сервисы</span>
                        </button>
                        <div class="guest-avatar" aria-label="Неавторизованный пользователь">Гость</div>
                    @endauth
                </div>
            </header>

            <div class="{{ $viewer ? 'auth-overlay' : 'guest-overlay' }}" data-panel-close data-panel-overlay></div>

            <aside class="{{ $viewer ? 'auth-drawer auth-menu-drawer' : 'guest-drawer guest-menu-drawer' }}" aria-label="Меню">
                <div class="{{ $viewer ? 'auth-drawer-head' : 'guest-drawer-head' }}">
                    <span>Меню</span>
                    <button class="{{ $viewer ? 'auth-close' : 'guest-close' }}" type="button" data-panel-close aria-label="Закрыть меню">
                        <svg><use href="#{{ $viewer ? 'auth-x' : 'guest-x' }}"/></svg>
                    </button>
                </div>

                @auth
                    <div class="auth-account">
                        <span class="photo"><img src="{{ $viewer->avatar_url }}" alt=""></span>
                        <div>
                            <strong>{{ $viewer->full_name }}</strong>
                            <a href="{{ route('users.show', $viewer) }}">Открыть профиль</a>
                        </div>
                    </div>
                @endauth

                @include('partials.v26.menu-items', [
                    'rowClass' => $viewer ? 'auth-menu-row' : 'guest-menu-row',
                    'labelClass' => $viewer ? 'auth-drawer-label' : 'guest-drawer-label',
                ])

                @guest
                    <div class="guest-menu-note">Персональные возможности станут доступны после регистрации.</div>
                @endguest
            </aside>

            <aside class="{{ $viewer ? 'auth-drawer auth-services-drawer' : 'guest-drawer guest-services-drawer' }}" aria-label="Сервисы Bizzio">
                <div class="{{ $viewer ? 'auth-drawer-head' : 'guest-drawer-head' }}">
                    <span>Сервисы Bizzio</span>
                    <button class="{{ $viewer ? 'auth-close' : 'guest-close' }}" type="button" data-panel-close aria-label="Закрыть сервисы">
                        <svg><use href="#{{ $viewer ? 'auth-x' : 'guest-x' }}"/></svg>
                    </button>
                </div>

                @include('partials.v26.services-items', [
                    'placement' => $viewer ? 'page_services_drawer' : 'page_services_drawer_guest',
                ])
            </aside>
        </div>
    </div>

    {{-- ===================== MOBILE ===================== --}}
    <div id="bizzio-mobile-v1" data-open="none">
        <header class="bz-header">
            <button class="bz-icon-button" type="button" data-open-panel="menu" aria-label="Открыть меню"><svg><use href="#menu"/></svg></button>
            <a class="bz-logo" href="{{ route('home') }}"><img class="bz-logo-image" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
            @auth
                <a class="bz-icon-button" href="{{ route('notifications.index') }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
            @else
                <a class="bz-icon-button" href="{{ config('app.auth_url') }}" aria-label="Войти"><svg><use href="#guest-user"/></svg></a>
            @endauth
            <button class="bz-icon-button" type="button" data-open-panel="services" aria-label="Открыть сервисы"><svg><use href="#grid"/></svg></button>
        </header>

        <nav class="bz-bottom" aria-label="Основная навигация">
            <a class="bz-bottom-item {{ request()->routeIs('home') ? 'bz-current' : '' }}" href="{{ route('home') }}"><svg><use href="#auth-home"/></svg><span>Главная</span></a>
            <a class="bz-bottom-item {{ request()->routeIs('tenders.*', 'rfqs.*', 'auctions.*') ? 'bz-current' : '' }}" href="{{ route('tenders.index') }}"><svg><use href="#gavel"/></svg><span>Закупки</span></a>
            <a class="bz-bottom-item {{ request()->routeIs('companies.*') ? 'bz-current' : '' }}" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><span>Компании</span></a>
            @auth
                <a class="bz-bottom-item {{ request()->routeIs('users.show') ? 'bz-current' : '' }}" href="{{ route('users.show', $viewer) }}"><svg><use href="#auth-user"/></svg><span>Профиль</span></a>
            @else
                <a class="bz-bottom-item" href="{{ config('app.auth_url') }}"><svg><use href="#guest-user"/></svg><span>Войти</span></a>
            @endauth
        </nav>

        <div class="bz-dim" data-close-panels></div>

        <aside class="bz-drawer bz-menu-drawer" aria-label="Меню">
            <div class="bz-drawer-head">
                <span>Меню</span>
                <button class="bz-close" type="button" data-close-panels aria-label="Закрыть меню"><svg><use href="#auth-x"/></svg></button>
            </div>

            @include('partials.v26.menu-items', [
                'rowClass' => 'bz-nav-row',
                'labelClass' => 'bz-drawer-section',
            ])

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bz-nav-row" type="submit"><svg><use href="#auth-logout"/></svg>Выйти</button>
                </form>
            @endauth
        </aside>

        <aside class="bz-drawer bz-services-drawer" aria-label="Сервисы Bizzio">
            <div class="bz-drawer-head">
                <span>Сервисы Bizzio</span>
                <button class="bz-close" type="button" data-close-panels aria-label="Закрыть сервисы"><svg><use href="#auth-x"/></svg></button>
            </div>

            @include('partials.v26.services-items', [
                'labelClass' => 'bz-service-label',
                'gridClass' => 'bz-service-grid',
                'tileClass' => 'bz-service-tile',
                'plusClass' => 'bz-plus',
                'placement' => 'page_mobile_drawer',
            ])
        </aside>
    </div>
</div>

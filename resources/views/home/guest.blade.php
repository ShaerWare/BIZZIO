{{-- #181 Гостевая главная (эталон Bizzio_Dashboard_v26, экраны guest-desktop / tablet / mobile).

     Десктоп и планшет — одна разметка, различаются медиазапросами v26.css;
     мобильная версия в макете сделана отдельной структурой (скоуп #bizzio-mobile-v1),
     поэтому здесь она идёт вторым блоком и переключается по ширине.

     Элементы без действующей функции помечены data-inactive-feature, карточки будущих
     сервисов — data-future-service: клик не ведёт никуда, но уходит в Метрику (см. v26.js). --}}
@extends('layouts.v26')

@section('content')
<div data-v26-root data-auth-state="guest">

    {{-- ======================= DESKTOP / TABLET ======================= --}}
    <div class="v26-desktop">
        <div class="page" data-panel="none">
            <header class="topbar guest-topbar">
                <div class="brand">
                    <a href="{{ route('home') }}"><img class="brand-logo" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
                </div>
                <button class="top-btn" type="button" data-panel-toggle="menu">
                    <svg class="hamb" aria-hidden="true"><use href="#menu"/></svg><span>Меню</span>
                </button>
                <a class="search" href="{{ route('login') }}">
                    <svg><use href="#search"/></svg><span>Компании, проекты, люди, закупки и новости</span>
                </a>
                <div class="top-actions">
                    <a class="icon-btn" href="{{ route('login') }}" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
                    <a class="plain-icon" href="{{ route('login') }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
                    <a class="guest-login" href="{{ $authUrl }}">Войти</a>
                    <a class="guest-register" href="{{ $registerUrl }}">Регистрация</a>
                    <div class="divider"></div>
                    <button class="services" type="button" data-panel-toggle="services">
                        <svg class="nav-icon" aria-hidden="true"><use href="#grid"/></svg><span>Сервисы</span>
                    </button>
                    <div class="guest-avatar" aria-label="Неавторизованный пользователь">Гость</div>
                </div>
            </header>

            <main class="content guest-content">
                <section class="column">
                    <article class="card guest-intro">
                        <div class="guest-round">Гость</div>
                        <p class="guest-side-copy">Просматривайте публичные компании, проекты, закупки и новости без регистрации.</p>
                        <a class="guest-text-link" href="{{ $registerUrl }}">Что доступно без регистрации&nbsp; →</a>
                    </article>
                    <article class="card guest-placeholder">
                        <h3>Мои компании</h3>
                        <div class="guest-placeholder-icon"><svg><use href="#building"/></svg></div>
                        <p class="guest-side-copy">После регистрации здесь появятся ваши компании и роли в них.</p>
                        <a class="guest-text-link" href="{{ $registerUrl }}">Добавить компанию после регистрации&nbsp; →</a>
                    </article>
                    <article class="card guest-placeholder">
                        <h3>Контакты</h3>
                        <div class="guest-placeholder-icon"><svg><use href="#users"/></svg></div>
                        <p class="guest-side-copy">Создавайте деловые связи и расширяйте профессиональную сеть.</p>
                        <a class="guest-text-link" href="{{ $registerUrl }}">Найти контакты после регистрации&nbsp; →</a>
                    </article>
                </section>

                <section class="column">
                    <article class="card guest-welcome">
                        <div class="guest-welcome-copy">
                            <h1>Добро пожаловать в Bizzio!</h1>
                            <p>Находите компании и специалистов, участвуйте в закупках и проектах, следите за деловыми событиями и открывайте новые возможности для бизнеса.</p>
                            <a class="guest-welcome-cta" href="{{ $registerUrl }}">Регистрация</a>
                            <div class="guest-public-note"><svg><use href="#guest-shield"/></svg><span>Публичный просмотр доступен без регистрации</span></div>
                        </div>
                        <img class="guest-city" src="/images/v26/bizzio-guest-city-v1.png" alt="Современный деловой город">
                    </article>

                    <article class="card guest-services-card">
                        <h2 class="guest-services-title">Сервисы</h2>
                        <div class="guest-active-grid">
                            <a class="guest-service-card" href="{{ route('companies.index') }}">
                                <div class="guest-service-icon"><svg><use href="#building"/></svg></div>
                                <div class="guest-service-name">Компании</div>
                                <div class="guest-service-copy">Каталог компаний и поиск партнёров</div>
                            </a>
                            <a class="guest-service-card" href="{{ route('projects.index') }}">
                                <div class="guest-service-icon"><svg><use href="#clip"/></svg></div>
                                <div class="guest-service-name">Проекты</div>
                                <div class="guest-service-copy">Проекты и кооперация для бизнеса</div>
                            </a>
                            <a class="guest-service-card" href="{{ $registerUrl }}">
                                <div class="guest-service-icon"><svg><use href="#users"/></svg></div>
                                <div class="guest-service-name">Контакты</div>
                                <div class="guest-service-copy">Контакты и деловые связи</div>
                            </a>
                            <a class="guest-service-card" href="{{ route('tenders.index') }}">
                                <div class="guest-service-icon"><img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""></div>
                                <div class="guest-service-name">Закупки</div>
                                <div class="guest-service-copy">Торги и закупки товаров и услуг</div>
                            </a>
                            <a class="guest-service-card" href="{{ route('news.index') }}">
                                <div class="guest-service-icon"><svg><use href="#news"/></svg></div>
                                <div class="guest-service-name">Новости</div>
                                <div class="guest-service-copy">Новости и события компаний</div>
                            </a>
                        </div>

                        <div class="guest-research-head">
                            <div>
                                <strong>Выберите, какой сервис запустить следующим</strong>
                                <div><span>Нажмите «Мне интересно» — ваш выбор будет учтён</span></div>
                            </div>
                            <div class="guest-research-line"></div>
                        </div>

                        <div class="guest-future-grid">
                            @foreach($futureServices as $service)
                                <div class="guest-future"
                                     data-future-service="{{ $service['id'] }}"
                                     data-service-name="{{ $service['name'] }}"
                                     data-placement="guest_services_card">
                                    <div class="guest-future-top">
                                        <svg><use href="#{{ $service['icon'] }}"/></svg><span class="guest-plus">+</span>
                                    </div>
                                    <div class="guest-service-name">{{ $service['name'] }}</div>
                                    <button class="guest-interest" type="button" data-interest-button>Мне интересно</button>
                                </div>
                            @endforeach
                        </div>

                        <div class="guest-suggest">
                            <svg><use href="#guest-light"/></svg>
                            <span>Не нашли нужный сервис? Предложите идею — мы рассмотрим её для развития Bizzio.</span>
                            <button type="button"
                                    data-inactive-feature="suggest_service"
                                    data-feature-label="Предложить сервис"
                                    data-placement="guest_services_card">Предложить сервис</button>
                        </div>
                    </article>
                </section>

                <aside class="column">
                    <article class="card events guest-events" aria-label="Актуальное в Bizzio">
                        <div class="events-title">Актуальное в Bizzio</div>
                        @forelse($events as $event)
                            <a class="event" href="{{ $event['url'] }}">
                                <div class="event-icon neutral-i" style="background:#f0f5fc">
                                    @if($event['type'] === 'procurement')
                                        <img class="procurement-event-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">
                                    @else
                                        <svg><use href="#clip"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <div class="event-title">{{ $event['title'] }}</div>
                                    <div class="meta">{{ $event['meta'] }}</div>
                                </div>
                                <span class="tag {{ $event['tag_class'] }}">{{ $event['tag'] }}</span>
                                <span>›</span>
                            </a>
                        @empty
                            <div class="event"><div><div class="meta">Пока нет активных процедур и проектов</div></div></div>
                        @endforelse
                    </article>

                    <article class="card ad guest-ad">
                        <div class="ad-label">Реклама</div>
                        <h3>Найдите надёжных<br>партнёров для бизнеса<br>на Bizzio</h3>
                        <a class="guest-welcome-cta" href="{{ $registerUrl }}">Присоединиться</a>
                    </article>

                    <article class="card news guest-news">
                        <div class="news-head">
                            <h3>Новости</h3>
                            <div class="news-tools"><a href="{{ route('news.index') }}">Все новости&nbsp; →</a></div>
                        </div>
                        @forelse($latestNews as $item)
                            <a class="news-row" href="{{ $item->link }}" target="_blank" rel="noopener">
                                <span class="dot" style="background:#60739f"></span>
                                <span>{{ \Illuminate\Support\Str::limit($item->title, 70) }}</span>
                                <span class="time">{{ optional($item->published_at)->diffForHumans() }}</span>
                            </a>
                        @empty
                            <div class="news-row"><span></span><span class="meta">Новостей пока нет</span><span></span></div>
                        @endforelse
                    </article>
                </aside>
            </main>

            <div class="guest-overlay" data-panel-close data-panel-overlay></div>

            <aside class="guest-drawer guest-menu-drawer" aria-label="Меню">
                <div class="guest-drawer-head">
                    <span>Меню</span>
                    <button class="guest-close" type="button" data-panel-close aria-label="Закрыть меню"><svg><use href="#guest-x"/></svg></button>
                </div>
                <div class="guest-drawer-label">Моя работа</div>
                <div class="guest-menu-list">
                    <a class="guest-menu-row active" href="{{ route('home') }}"><svg><use href="#guest-home"/></svg><span>Главная</span></a>
                    <a class="guest-menu-row" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><span>Компании</span></a>
                    <a class="guest-menu-row" href="{{ $registerUrl }}"><svg><use href="#users"/></svg><span>Контакты</span></a>
                    <a class="guest-menu-row" href="{{ route('tenders.index') }}"><img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""><span>Закупки</span></a>
                    <a class="guest-menu-row" href="{{ route('news.index') }}"><svg><use href="#news"/></svg><span>Новости</span></a>
                    <a class="guest-menu-row" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg><span>Проекты</span></a>
                    <a class="guest-menu-row" href="{{ $registerUrl }}"><svg><use href="#file"/></svg><span>Публикации</span></a>
                </div>
                <div class="guest-drawer-label">Настройки и поддержка</div>
                <div class="guest-menu-list">
                    <a class="guest-menu-row" href="{{ $authUrl }}"><svg><use href="#help-chat"/></svg><span>Помощь и обратная связь</span></a>
                </div>
                <div class="guest-menu-note">Персональные возможности станут доступны после регистрации.</div>
                <div class="guest-menu-actions">
                    <a class="guest-login" href="{{ $authUrl }}">Войти</a>
                    <a class="guest-register" href="{{ $registerUrl }}">Регистрация</a>
                </div>
            </aside>

            <aside class="guest-drawer guest-services-drawer" aria-label="Сервисы Bizzio">
                <div class="guest-drawer-head">
                    <span>Сервисы Bizzio</span>
                    <button class="guest-close" type="button" data-panel-close aria-label="Закрыть сервисы"><svg><use href="#guest-x"/></svg></button>
                </div>
                <div class="guest-drawer-section">Доступные сервисы</div>
                <div class="guest-drawer-grid">
                    <a class="guest-drawer-service" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><div class="guest-service-name">Компании</div></a>
                    <a class="guest-drawer-service" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg><div class="guest-service-name">Проекты</div></a>
                    <a class="guest-drawer-service" href="{{ route('tenders.index') }}"><img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""><div class="guest-service-name">Закупки</div></a>
                    <a class="guest-drawer-service" href="{{ route('news.index') }}"><svg><use href="#news"/></svg><div class="guest-service-name">Новости</div></a>
                    <a class="guest-drawer-service" href="{{ $registerUrl }}"><svg><use href="#users"/></svg><div class="guest-service-name">Контакты</div></a>
                </div>
                <div class="guest-drawer-section">Будущие сервисы</div>
                <div class="guest-drawer-grid">
                    @foreach($futureServices as $service)
                        <div class="guest-drawer-service"
                             data-future-service="{{ $service['id'] }}"
                             data-service-name="{{ $service['name'] }}"
                             data-placement="guest_services_drawer">
                            <svg><use href="#{{ $service['icon'] }}"/></svg>
                            <div class="guest-service-name">{{ $service['name'] }}</div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>

    {{-- Мобильная версия: у эталона своя структура, вынесена в партиал --}}
    @include('partials.v26.mobile-guest')
</div>
@endsection

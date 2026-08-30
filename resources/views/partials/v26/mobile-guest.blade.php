{{-- #181 Мобильная гостевая главная (эталон guest-mobile).

     Обязательна обёртка .bz-phone: шапка и нижняя навигация позиционируются fixed,
     отступы под них задаёт именно она. Панели открываются атрибутом data-open
     на #bizzio-mobile-v1 (см. resources/js/v26.js).

     Порядок блоков основной области — как в эталоне v26:
     Добро пожаловать → Сервисы → Актуальное в Bizzio → Реклама → заглушки
     «Мои компании»/«Контакты» → Новости → выбор следующего сервиса. --}}
<div id="bizzio-mobile-v1" data-open="none">
    <section class="bz-phone" aria-label="Главная Bizzio для гостя">
        <header class="bz-header gm-header">
            <button class="bz-icon-button" type="button" data-open-panel="menu" aria-label="Открыть меню"><svg><use href="#menu"/></svg></button>
            <a class="bz-logo" href="{{ route('home') }}"><img class="bz-logo-image" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
            {{-- 2.1.1 Кнопка входа/регистрации в шапке: ведёт на форму «Войти» со ссылкой на регистрацию --}}
            <a class="bz-icon-button gm-auth-button" href="{{ $authUrl }}" aria-label="Войти или зарегистрироваться"><svg><use href="#login"/></svg></a>
            <button class="bz-icon-button" type="button" data-open-panel="services" aria-label="Открыть сервисы"><svg><use href="#grid"/></svg></button>
            <div class="bz-avatar" aria-label="Неавторизованный пользователь">Гость</div>
        </header>

        <main class="bz-main gm-main">
            <section class="bz-panel gm-welcome" aria-label="Добро пожаловать">
                <div class="gm-welcome-copy">
                    <h1>Добро пожаловать в Bizzio!</h1>
                    <p>Находите компании и специалистов, участвуйте в закупках и проектах, следите за деловыми событиями и открывайте новые возможности для бизнеса.</p>
                    <a class="gm-register" href="{{ $registerUrl }}">Регистрация</a>
                    <div class="gm-public-note"><svg><use href="#guest-shield"/></svg><span>Публичный просмотр доступен без регистрации</span></div>
                </div>
                <img class="gm-city" src="/images/v26/bizzio-guest-city-v1.png" alt="">
            </section>

            <section class="bz-panel gm-services" aria-label="Сервисы Bizzio">
                <h2 class="gm-title">Сервисы</h2>
                <div class="gm-active-grid">
                    <a class="gm-active-service" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><strong>Компании</strong><span>Каталог компаний и партнёров</span></a>
                    <a class="gm-active-service" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg><strong>Проекты</strong><span>Проекты и кооперация</span></a>
                    <a class="gm-active-service" href="{{ $registerUrl }}"><svg><use href="#users"/></svg><strong>Контакты</strong><span>Контакты и связи</span></a>
                    <a class="gm-active-service" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""><strong>Закупки</strong><span>Торги и закупки</span></a>
                    <a class="gm-active-service" href="{{ route('news.index') }}"><svg><use href="#news"/></svg><strong>Новости</strong><span>Новости компаний</span></a>
                </div>
            </section>

            {{-- 2.1.4 Актуальное в Bizzio: последние события разделов Закупки / Друзья / Компании / Проекты --}}
            <section class="bz-panel bz-events gm-events" aria-label="Актуальное в Bizzio">
                <div class="bz-events-title">Актуальное в Bizzio</div>
                @forelse($events as $event)
                    <a class="bz-event" href="{{ $event['url'] }}">
                        <div class="bz-event-icon">
                            @switch($event['type'])
                                @case('procurement')
                                    <img class="bz-event-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">
                                    @break
                                @case('friend')
                                    <svg><use href="#users"/></svg>
                                    @break
                                @case('company')
                                    <svg><use href="#building"/></svg>
                                    @break
                                @default
                                    <svg><use href="#clip"/></svg>
                            @endswitch
                        </div>
                        <div>
                            <div class="bz-event-title">{{ \Illuminate\Support\Str::limit($event['title'], 40) }}</div>
                            <div class="bz-meta">{{ \Illuminate\Support\Str::limit($event['meta'], 44) }}</div>
                        </div>
                        <span class="bz-tag">{{ $event['tag'] }}</span>
                    </a>
                @empty
                    <div class="bz-meta">Пока нет активных процедур и проектов</div>
                @endforelse
            </section>

            <aside class="bz-panel bz-ad">
                <div class="bz-ad-label">Реклама</div>
                <div class="bz-ad-copy">Найдите надёжных партнёров для бизнеса на Bizzio</div>
                <a class="bz-ad-link" href="{{ $registerUrl }}">Присоединиться</a>
                <svg class="bz-handshake" viewBox="0 0 220 180" aria-hidden="true">
                    <defs><linearGradient id="gm-hand-fill" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#70cfa8"/><stop offset="1" stop-color="#079b38"/></linearGradient></defs>
                    <circle cx="110" cy="91" r="66" fill="#e8f7f1"/><circle cx="110" cy="91" r="52" fill="#f5fbf8" stroke="#d6eee3"/>
                    <svg x="32" y="31" width="156" height="125" viewBox="0 0 640 512"><path fill="url(#gm-hand-fill)" stroke="none" d="M323.4 85.2l-96.8 78.4c-16.1 13-19.2 36.4-7 53.1c12.9 17.8 38 21.3 55.3 7.8l99.3-77.2c7-5.4 17-4.2 22.5 2.8s4.2 17-2.8 22.5l-20.9 16.2L550.2 352H592c26.5 0 48-21.5 48-48V176c0-26.5-21.5-48-48-48H516h-4-.7l-3.9-2.5L434.8 79c-15.3-9.8-33.2-15-51.4-15c-21.8 0-43 7.5-60 21.2zm22.8 124.4l-51.7 40.2C263 274.4 217.3 268 193.7 235.6c-22.2-30.5-16.6-73.1 12.7-96.8l83.2-67.3c-11.6-4.9-24.1-7.4-36.8-7.4C234 64 215.7 69.6 200 80l-72 48H48c-26.5 0-48 21.5-48 48V304c0 26.5 21.5 48 48 48H156.2l91.4 83.4c19.6 17.9 49.9 16.5 67.8-3.1c5.5-6.1 9.2-13.2 11.1-20.6l17 15.6c19.5 17.9 49.9 16.6 67.8-2.9c4.5-4.9 7.8-10.6 9.9-16.5c19.4 13 45.8 10.3 62.1-7.5c17.9-19.5 16.6-49.9-2.9-67.8l-134.2-123z"/></svg>
                </svg>
            </aside>

            <section class="bz-panel gm-placeholder">
                <div class="gm-placeholder-head">
                    <div class="gm-placeholder-icon"><svg><use href="#building"/></svg></div>
                    <h2>Мои компании</h2>
                </div>
                <p>После регистрации здесь появятся ваши компании и роли в них.</p>
                <a href="{{ $registerUrl }}">Добавить компанию после регистрации →</a>
            </section>

            <section class="bz-panel gm-placeholder">
                <div class="gm-placeholder-head">
                    <div class="gm-placeholder-icon"><svg><use href="#users"/></svg></div>
                    <h2>Контакты</h2>
                </div>
                <p>Создавайте деловые связи и расширяйте профессиональную сеть.</p>
                <a href="{{ $registerUrl }}">Найти контакты после регистрации →</a>
            </section>

            <section class="bz-panel bz-news" aria-label="Новости">
                <div class="bz-news-head">
                    <span class="bz-news-title">Новости</span>
                    {{-- 2.1.5 «Настроить выдачу» — персональная выдача доступна после регистрации --}}
                    <span class="bz-news-link">
                        <a href="{{ $registerUrl }}">Настроить выдачу</a> ·
                        <a href="{{ route('news.index') }}">Все новости →</a>
                    </span>
                </div>
                @forelse($latestNews as $item)
                    <a class="bz-news-row" href="{{ $item->link }}" target="_blank" rel="noopener">
                        <span class="bz-news-dot"></span>
                        <span>{{ \Illuminate\Support\Str::limit($item->title, 60) }}<span class="bz-news-time">{{ optional($item->published_at)->diffForHumans() }}</span></span>
                    </a>
                @empty
                    <div class="bz-meta">Новостей пока нет</div>
                @endforelse
            </section>

            <section class="bz-panel gm-future-services" aria-label="Выбор следующего сервиса">
                <div class="gm-research">
                    <strong>Выберите следующий сервис</strong>
                    <span>Нажмите «Мне интересно» — ваш выбор будет учтён</span>
                </div>
                <div class="gm-future-grid">
                    @foreach($futureServices as $service)
                        <div class="gm-future"
                             data-future-service="{{ $service['id'] }}"
                             data-service-name="{{ $service['name'] }}"
                             data-placement="guest_mobile">
                            <div class="gm-future-top"><svg><use href="#{{ $service['icon'] }}"/></svg><span class="gm-plus">+</span></div>
                            <div class="gm-future-name">{{ $service['name'] }}</div>
                            <button class="gm-interest" type="button" data-interest-button>Мне интересно</button>
                        </div>
                    @endforeach
                </div>
                <div class="gm-suggest">
                    <svg><use href="#guest-light"/></svg>
                    <span>Не нашли нужный сервис?</span>
                    <button type="button"
                            data-inactive-feature="suggest_service"
                            data-feature-label="Предложить сервис"
                            data-placement="guest_mobile">Предложить сервис</button>
                </div>
            </section>
        </main>

        {{-- Нижняя навигация: в эталоне ровно четыре ячейки (grid 4×1fr) --}}
        <nav class="bz-bottom gm-bottom" aria-label="Основная навигация">
            <a class="bz-bottom-item" href="{{ route('search.index') }}" aria-label="Поиск"><svg><use href="#search"/></svg></a>
            <a class="bz-bottom-item" href="{{ $authUrl }}" aria-label="Сообщения"><svg><use href="#chat"/></svg></a>
            <a class="bz-bottom-item" href="{{ $authUrl }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
            <a class="bz-bottom-item" href="{{ $authUrl }}" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
        </nav>
    </section>

    <div class="bz-dim" data-close-panels></div>

    <aside class="bz-drawer bz-menu-drawer gm-drawer" aria-label="Меню">
        <div class="bz-drawer-head">
            <span>Меню</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть меню"><svg><use href="#guest-x"/></svg></button>
        </div>
        {{-- 2.1.2 Раздела «Публикации» у нас пока нет — пункта в меню тоже нет.
             Кнопки «Войти»/«Регистрация» из меню убраны: вход теперь в шапке. --}}
        {{-- #181 Состав меню общий для всех страниц сайта --}}
        @include('partials.v26.menu-items', [
            'rowClass' => 'bz-nav-row',
            'labelClass' => 'bz-drawer-section',
        ])

    </aside>

    <aside class="bz-drawer bz-services-drawer gm-drawer" aria-label="Сервисы Bizzio">
        <div class="bz-drawer-head">
            <span>Сервисы Bizzio</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть сервисы"><svg><use href="#guest-x"/></svg></button>
        </div>
        <div class="gm-service-title">Текущие сервисы</div>
        <div class="bz-service-grid">
            <a class="bz-service-tile" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
            <a class="bz-service-tile" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>
            <a class="bz-service-tile" href="{{ $registerUrl }}"><svg><use href="#users"/></svg>Контакты</a>
            <a class="bz-service-tile" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
            <a class="bz-service-tile" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
        </div>

        {{-- 2.1.3 «Зелёные плюсики» у будущих сервисов — голосование за очерёдность запуска --}}
        <div class="gm-service-title">Будущие сервисы<span class="gm-service-sub">Нажмите на сервис — ваш выбор будет учтён</span></div>
        <div class="bz-service-grid">
            @foreach($futureServices as $service)
                <div class="bz-service-tile gm-service-tile"
                     data-future-service="{{ $service['id'] }}"
                     data-service-name="{{ $service['name'] }}"
                     data-placement="guest_mobile_drawer">
                    <span class="gm-plus">+</span>
                    <svg><use href="#{{ $service['icon'] }}"/></svg>{{ $service['name'] }}
                </div>
            @endforeach
        </div>

        <div class="gm-propose">
            <svg><use href="#guest-light"/></svg>
            <span>Не нашли нужный сервис?</span>
            <button type="button"
                    data-inactive-feature="suggest_service"
                    data-feature-label="Предложить сервис"
                    data-placement="guest_mobile_drawer">Предложить</button>
        </div>

        {{-- 2.1.3 Блок «Bizzio в соцсетях»: аккаунтов ещё нет, ссылки пустые --}}
        <div class="gm-social">
            <div class="gm-social-title">Bizzio в соцсетях</div>
            <div class="gm-social-row">
                <a class="gm-social-link" href="#" data-inactive-feature="social_vk_video" data-feature-label="VK Видео" data-placement="guest_mobile_drawer"><span class="gm-social-mark" style="background:#1674e8">VK</span>VK Видео</a>
                <a class="gm-social-link" href="#" data-inactive-feature="social_rutube" data-feature-label="RUTUBE" data-placement="guest_mobile_drawer"><span class="gm-social-mark" style="background:#121728">R</span>RUTUBE</a>
                <a class="gm-social-link" href="#" data-inactive-feature="social_youtube" data-feature-label="YouTube" data-placement="guest_mobile_drawer"><span class="gm-social-mark" style="background:#e21b23">▶</span>YouTube</a>
                <a class="gm-social-link" href="#" data-inactive-feature="social_telegram" data-feature-label="Telegram" data-placement="guest_mobile_drawer"><span class="gm-social-mark" style="background:#26a5e4">➤</span>Telegram</a>
            </div>
        </div>
    </aside>
</div>

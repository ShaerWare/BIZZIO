{{-- #181 Мобильная главная авторизованного пользователя (эталон authorized-mobile).

     Структура эталона: #bizzio-mobile-v1[data-open] → .bz-phone → шапка, .bz-main, нижняя
     навигация. Обёртка .bz-phone обязательна: .bz-header и .bz-bottom позиционируются
     fixed, и именно она компенсирует их высоту отступами. Панели открываются атрибутом
     data-open на корне (не data-panel, как в десктопной части).

     Порядок блоков основной области — как в эталоне v26:
     быстрые ссылки → Актуальное в Bizzio → форма публикации → Реклама → лента → Новости. --}}
<div id="bizzio-mobile-v1" data-open="none">
    <section class="bz-phone" aria-label="Главная Bizzio">
        {{-- 2.2.1 Шапка эталона: меню, логотип, сервисы и меню профиля (вход/выход, настройки) --}}
        <header class="bz-header">
            <button class="bz-icon-button" type="button" data-open-panel="menu" aria-label="Открыть меню"><svg><use href="#menu"/></svg></button>
            <a class="bz-logo" href="{{ route('home') }}"><img class="bz-logo-image" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
            <button class="bz-icon-button" type="button" data-open-panel="services" aria-label="Открыть сервисы"><svg><use href="#grid"/></svg></button>
            <div class="bz-profile-menu">
                <button class="bz-avatar bz-profile-trigger" type="button" aria-label="Открыть меню профиля" aria-expanded="false">{{ mb_substr($viewer->name, 0, 1) }}</button>
                <div class="bz-profile-popover" role="menu">
                    <a class="bz-profile-action" href="{{ route('users.show', $viewer) }}" role="menuitem"><svg><use href="#auth-user"/></svg><span>Профиль</span></a>
                    <a class="bz-profile-action" href="{{ route('profile.edit') }}" role="menuitem"><svg><use href="#auth-settings"/></svg><span>Настройки</span></a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="bz-profile-action" type="submit" role="menuitem"><svg><use href="#auth-logout"/></svg><span>Выйти</span></button>
                    </form>
                </div>
            </div>
        </header>

        <main class="bz-main">
            <section class="bz-panel bz-shortcuts" aria-label="Быстрые ссылки">
                <a class="bz-shortcut" href="{{ route('friends.index') }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-find-friends-v4.png" alt=""><span>Найти друзей</span></a>
                <a class="bz-shortcut" href="{{ route('tenders.index') }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-find-procurement-v4.png" alt=""><span>Найти закупку</span></a>
                <a class="bz-shortcut" href="{{ route('rfqs.create', ['procedure' => 'commercial']) }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-create-procurement-v4.png" alt=""><span>Создать закупку</span></a>
                <a class="bz-shortcut" href="{{ route('profile.keywords.index') }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-news-v4.png" alt=""><span>Настроить новости</span></a>
            </section>

            {{-- 2.2.2 «Актуальное в Bizzio»: последние события разделов Закупки / Друзья / Компании / Проекты --}}
            <section class="bz-panel bz-events" aria-label="Актуальное в Bizzio">
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
                    <div class="bz-meta">Пока нет событий</div>
                @endforelse
            </section>

            <section class="bz-panel bz-composer" aria-label="Создание публикации">
                <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="bz-compose-row">
                        <span class="bz-avatar"><img src="{{ $viewer->avatar_url }}" alt=""></span>
                        <input class="bz-compose-field" type="text" name="body" maxlength="2000" required
                               placeholder="Поделиться новостью или деловой идеей…" value="{{ old('body') }}">
                    </div>
                    <div class="bz-compose-actions">
                        <label class="bz-compose-action">
                            <svg><use href="#camera"/></svg>Фото
                            <input type="file" name="photo" accept="image/*" hidden>
                        </label>
                        <button class="bz-compose-action" type="submit"><svg><use href="#file"/></svg>Опубликовать</button>
                    </div>
                </form>
            </section>

            <aside class="bz-panel bz-ad" aria-label="Реклама">
                <div class="bz-ad-label">Реклама</div>
                <div class="bz-ad-copy">Найдите надёжных партнёров для бизнеса на Bizzio</div>
                <a class="bz-ad-link" href="{{ route('companies.index') }}">Подробнее</a>
                <svg class="bz-handshake" viewBox="0 0 220 180" aria-hidden="true">
                    <defs><linearGradient id="bz-mobile-hand-fill" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#70cfa8"/><stop offset="1" stop-color="#079b38"/></linearGradient></defs>
                    <circle cx="110" cy="91" r="66" fill="#e8f7f1"/><circle cx="110" cy="91" r="52" fill="#f5fbf8" stroke="#d6eee3"/>
                    <svg x="32" y="31" width="156" height="125" viewBox="0 0 640 512"><path fill="url(#bz-mobile-hand-fill)" stroke="none" d="M323.4 85.2l-96.8 78.4c-16.1 13-19.2 36.4-7 53.1c12.9 17.8 38 21.3 55.3 7.8l99.3-77.2c7-5.4 17-4.2 22.5 2.8s4.2 17-2.8 22.5l-20.9 16.2L550.2 352H592c26.5 0 48-21.5 48-48V176c0-26.5-21.5-48-48-48H516h-4-.7l-3.9-2.5L434.8 79c-15.3-9.8-33.2-15-51.4-15c-21.8 0-43 7.5-60 21.2zm22.8 124.4l-51.7 40.2C263 274.4 217.3 268 193.7 235.6c-22.2-30.5-16.6-73.1 12.7-96.8l83.2-67.3c-11.6-4.9-24.1-7.4-36.8-7.4C234 64 215.7 69.6 200 80l-72 48H48c-26.5 0-48 21.5-48 48V304c0 26.5 21.5 48 48 48H156.2l91.4 83.4c19.6 17.9 49.9 16.5 67.8-3.1c5.5-6.1 9.2-13.2 11.1-20.6l17 15.6c19.5 17.9 49.9 16.6 67.8-2.9c4.5-4.9 7.8-10.6 9.9-16.5c19.4 13 45.8 10.3 62.1-7.5c17.9-19.5 16.6-49.9-2.9-67.8l-134.2-123z"/></svg>
                </svg>
            </aside>

            @forelse($recentPosts as $post)
                <article class="bz-panel bz-post">
                    <div class="bz-post-head">
                        <a class="bz-company-icon" href="{{ route('users.show', $post->user) }}"><img src="{{ $post->user->avatar_url }}" alt=""></a>
                        <div>
                            <div class="bz-post-name">{{ $post->user->full_name }}</div>
                            <div class="bz-meta">{{ $post->created_at->diffForHumans() }}</div>
                        </div>
                        @if($post->user_id === $viewer->id)
                            <form class="bz-more" method="POST" action="{{ route('posts.destroy', $post) }}"
                                  onsubmit="return confirm('Удалить пост?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Удалить пост">×</button>
                            </form>
                        @endif
                    </div>
                    <p class="bz-post-copy">{{ $post->body }}</p>
                    @if($post->getFirstMediaUrl('photos'))
                        <img class="bz-post-photo" src="{{ $post->getFirstMediaUrl('photos') }}" alt="">
                    @endif
                </article>
            @empty
                <section class="bz-panel">
                    <div class="bz-meta">В ленте пока пусто. Подпишитесь на коллег и компании, чтобы видеть их публикации.</div>
                </section>
            @endforelse

            <section class="bz-panel bz-news" aria-label="Новости">
                <div class="bz-news-head">
                    <span class="bz-news-title">Новости</span>
                    <span class="bz-news-link">
                        <a href="{{ route('profile.keywords.index') }}">Настроить выдачу</a> ·
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
        </main>

        {{-- 2.2.1 Нижняя навигация эталона: поиск, сообщения, уведомления, помощь --}}
        <nav class="bz-bottom" aria-label="Основная навигация">
            <a class="bz-bottom-item" href="{{ route('search.index') }}" aria-label="Поиск"><svg><use href="#search"/></svg></a>
            {{-- Раздела сообщений пока нет: элемент остаётся видимым и уходит в аналитику --}}
            <button class="bz-bottom-item" type="button"
                    data-inactive-feature="messages"
                    data-feature-label="Сообщения"
                    data-placement="auth_mobile_bottom"
                    aria-label="Сообщения"><svg><use href="#chat"/></svg></button>
            <a class="bz-bottom-item" href="{{ route('notifications.index') }}" aria-label="Уведомления">
                <svg><use href="#bell"/></svg>
                @php($unread = $viewer->unreadNotifications()->count())
                @if($unread > 0)
                    <span class="bz-count">{{ $unread }}</span>
                @endif
            </a>
            <a class="bz-bottom-item" href="{{ route('profile.edit') }}#feedback" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
        </nav>
    </section>

    <div class="bz-dim" data-close-panels></div>

    <aside class="bz-drawer bz-menu-drawer" aria-label="Меню">
        <div class="bz-drawer-head">
            <span>Меню</span>
            <button class="bz-close" type="button" data-close-panels aria-label="Закрыть меню"><svg><use href="#auth-x"/></svg></button>
        </div>
        {{-- Настройки профиля и выход живут в меню профиля (шапка), в меню раздела их нет --}}
        {{-- #181 Состав меню общий для всех страниц сайта --}}
        @include('partials.v26.menu-items', [
            'rowClass' => 'bz-nav-row',
            'labelClass' => 'bz-drawer-section',
        ])

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
            <a class="bz-service-tile" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg>Контакты</a>
            <a class="bz-service-tile" href="{{ route('tenders.index') }}"><img class="bz-procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
            <a class="bz-service-tile" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
            <a class="bz-service-tile" href="{{ route('subscriptions.index') }}"><svg><use href="#auth-bookmark"/></svg>Подписки</a>
        </div>

        <div class="bz-service-label">Будущие сервисы · нажмите, чтобы поддержать</div>
        <div class="bz-service-grid">
            @foreach($futureServices as $service)
                <div class="bz-service-tile bz-future-tile"
                     data-future-service="{{ $service['id'] }}"
                     data-service-name="{{ $service['name'] }}"
                     data-placement="auth_mobile_drawer">
                    <span class="bz-plus">+</span>
                    <svg><use href="#{{ $service['icon'] }}"/></svg>{{ $service['name'] }}
                </div>
            @endforeach
        </div>

        <div class="bz-propose">
            <svg><use href="#auth-light"/></svg>
            <span>Не нашли нужный сервис?</span>
            <button type="button"
                    data-inactive-feature="suggest_service"
                    data-feature-label="Предложить сервис"
                    data-placement="auth_mobile_drawer">Предложить</button>
        </div>

        <div class="bz-social">
            <div class="bz-social-title">Bizzio в соцсетях</div>
            <div class="bz-social-row">
                <a class="bz-social-link" href="#" data-inactive-feature="social_vk_video" data-feature-label="VK Видео" data-placement="auth_mobile_drawer"><span class="bz-social-mark" style="background:#1674e8">VK</span>VK Видео</a>
                <a class="bz-social-link" href="#" data-inactive-feature="social_rutube" data-feature-label="RUTUBE" data-placement="auth_mobile_drawer"><span class="bz-social-mark" style="background:#121728">R</span>RUTUBE</a>
                <a class="bz-social-link" href="#" data-inactive-feature="social_youtube" data-feature-label="YouTube" data-placement="auth_mobile_drawer"><span class="bz-social-mark" style="background:#e21b23">▶</span>YouTube</a>
                <a class="bz-social-link" href="#" data-inactive-feature="social_telegram" data-feature-label="Telegram" data-placement="auth_mobile_drawer"><span class="bz-social-mark" style="background:#26a5e4">➤</span>Telegram</a>
            </div>
        </div>
    </aside>
</div>

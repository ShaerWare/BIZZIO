{{-- #181 Авторизованная главная (эталон Bizzio_Dashboard_v26, экраны authorized-*).

     Данные те же, что у прежнего дашборда: HomeController берёт их из
     DashboardController::dashboardData(). Новых сущностей и API задача не добавляет. --}}
@extends('layouts.v26')

@section('title', 'Bizzio — главная')

@section('content')
@php
    $viewer = auth()->user();
@endphp
<div data-v26-root data-auth-state="authorized">

    {{-- ======================= DESKTOP / TABLET ======================= --}}
    <div class="v26-desktop">
        <div class="page" data-panel="none">
            <header class="topbar">
                <div class="brand">
                    <a href="{{ route('home') }}"><img class="brand-logo" src="/images/bizzio_horizontal_logo_color_whitebg.svg" alt="Bizzio"></a>
                </div>
                <button class="top-btn" type="button" data-panel-toggle="menu">
                    <svg class="hamb" aria-hidden="true"><use href="#menu"/></svg><span>Меню</span>
                </button>
                <form class="search" method="GET" action="{{ route('search.index') }}">
                    <svg><use href="#search"/></svg>
                    <input type="text" name="q" placeholder="Компании, проекты, люди, закупки и новости"
                           value="{{ request('q') }}" aria-label="Поиск по Bizzio">
                </form>
                <div class="top-actions">
                    <a class="icon-btn" href="{{ route('profile.edit') }}#feedback" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
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
                </div>
            </header>

            <main class="content">
                <section class="column">
                    <article class="card profile-card">
                        <span class="photo profile"><img src="{{ $viewer->avatar_url }}" alt=""></span>
                        <h2>{{ $viewer->full_name }}</h2>
                        <div class="role">{{ $viewer->position ?: 'Участник Bizzio' }}</div>
                        @if($userCompanies->isNotEmpty())
                            <div class="location">⌖ <span>{{ $userCompanies->first()->name }}</span></div>
                        @endif
                        <a class="profile-link" href="{{ route('users.show', $viewer) }}">Открыть профиль&nbsp; →</a>
                    </article>

                    <article class="card side-card">
                        <h3>Компании</h3>
                        @forelse($userCompanies->take(2) as $company)
                            <a class="company-row" href="{{ route('companies.show', $company) }}">
                                <div class="soft-icon"><svg><use href="#building"/></svg></div>
                                <div>
                                    <div class="name">{{ $company->name }}</div>
                                    <div class="meta">{{ $company->pivot->role === 'owner' ? 'Руководитель' : 'Представитель' }}</div>
                                </div>
                            </a>
                        @empty
                            <p class="meta">Вы пока не состоите ни в одной компании.</p>
                        @endforelse
                        <div class="side-links">
                            <a class="link" href="{{ route('companies.index') }}">Все компании&nbsp; →</a>
                            <a class="link green-link" href="{{ route('companies.create') }}">Добавить компанию&nbsp; →</a>
                        </div>
                    </article>

                    <article class="card side-card">
                        <h3>Контакты</h3>
                        <a class="friend-row" href="{{ route('friends.index') }}">
                            <div class="soft-icon"><svg><use href="#users"/></svg></div>
                            <div>
                                <div class="name">{{ $viewer->friends()->count() }} контактов</div>
                                @if($viewer->pendingFriendRequests()->count() > 0)
                                    <div class="meta" style="color:#0669dc">{{ $viewer->pendingFriendRequests()->count() }} новых приглашений</div>
                                @endif
                            </div>
                        </a>
                        <div class="side-links">
                            <a class="link green-link" href="{{ route('friends.index') }}">Все контакты&nbsp; →</a>
                            <a class="link green-link" href="{{ route('subscriptions.index') }}">Мои подписки&nbsp; →</a>
                        </div>
                    </article>
                </section>

                <section class="column">
                    <article class="card services-card">
                        <a class="service neutral-i" href="{{ route('tenders.index') }}"><svg><use href="#gavel"/></svg><span>Найти закупку</span></a>
                        <a class="service neutral-i" href="{{ route('rfqs.create', ['procedure' => 'commercial']) }}"><img class="quick-icon" src="/images/v26/bizzio-quick-icon-create-procurement-v4.png" alt=""><span>Создать закупку</span></a>
                        <a class="service neutral-i" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg><span>Найти друзей</span></a>
                        <a class="service neutral-i" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><span>Компании</span></a>
                    </article>

                    <article class="card composer">
                        <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="composer-top">
                                <span class="photo composer"><img src="{{ $viewer->avatar_url }}" alt=""></span>
                                <input class="composer-field" type="text" name="body" maxlength="2000" required
                                       placeholder="Поделиться новостью, идеей или деловым предложением…"
                                       value="{{ old('body') }}">
                            </div>
                            <div class="composer-bottom">
                                <label class="composer-action">
                                    <svg class="green-i"><use href="#camera"/></svg>Фото
                                    <input type="file" name="photo" accept="image/*" hidden>
                                </label>
                                <button class="publish" type="submit">Создать публикацию</button>
                            </div>
                        </form>
                        @error('body')
                            <p class="meta" style="color:#e31d2d">{{ $message }}</p>
                        @enderror
                    </article>

                    @forelse($recentPosts as $post)
                        <article class="card post">
                            <div class="post-head">
                                <a class="company-avatar" href="{{ route('users.show', $post->user) }}">
                                    <img src="{{ $post->user->avatar_url }}" alt="">
                                </a>
                                <div>
                                    <div class="name">{{ $post->user->full_name }}</div>
                                    <div class="meta">{{ $post->created_at->diffForHumans() }}</div>
                                </div>
                                @if($post->user_id === $viewer->id)
                                    <form class="dots" method="POST" action="{{ route('posts.destroy', $post) }}"
                                          onsubmit="return confirm('Удалить пост?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Удалить пост">×</button>
                                    </form>
                                @endif
                            </div>
                            <p class="post-copy">{{ $post->body }}</p>
                            @if($post->getFirstMediaUrl('photos'))
                                <img class="post-photo" src="{{ $post->getFirstMediaUrl('photos') }}" alt="">
                            @endif
                        </article>
                    @empty
                        <article class="card post">
                            <p class="post-copy">В ленте пока пусто. Подпишитесь на коллег и компании, чтобы видеть их публикации.</p>
                            @if($recommendedCompanies->isNotEmpty())
                                <div class="side-links">
                                    @foreach($recommendedCompanies as $company)
                                        <a class="link" href="{{ route('companies.show', $company) }}">{{ $company->name }}&nbsp; →</a>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforelse
                </section>

                <aside class="column">
                    <article class="card events">
                        <div class="events-title">Мои закупки</div>
                        @forelse($myTenders as $tender)
                            <a class="event" href="{{ $tender['url'] }}">
                                <div class="event-icon neutral-i" style="background:#f0f5fc">
                                    <img class="procurement-event-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">
                                </div>
                                <div>
                                    <div class="event-title">{{ \Illuminate\Support\Str::limit($tender['title'], 34) }}</div>
                                    <div class="meta">{{ $tender['number'] }}</div>
                                </div>
                                <span class="tag purple">{{ $tender['status_label'] }}</span>
                                <span>›</span>
                            </a>
                        @empty
                            <div class="event"><div><div class="meta">У вас пока нет закупок</div></div></div>
                        @endforelse
                    </article>

                    <article class="card events">
                        <div class="events-title">Приглашения и заявки</div>
                        @forelse($myInvitations->take(2) as $invitation)
                            <a class="event" href="{{ $invitation['url'] }}">
                                <div class="event-icon blue-i" style="background:#e7f1ff"><svg><use href="#clip"/></svg></div>
                                <div>
                                    <div class="event-title">{{ \Illuminate\Support\Str::limit($invitation['title'], 34) }}</div>
                                    <div class="meta">{{ $invitation['inv_label'] }}</div>
                                </div>
                                <span class="tag blue-tag">Приглашение</span>
                                <span>›</span>
                            </a>
                        @empty
                            <div class="event"><div><div class="meta">Приглашений пока нет</div></div></div>
                        @endforelse
                        <div class="side-links">
                            <a class="link" href="{{ route('tenders.invitations.my') }}">Все приглашения&nbsp; →</a>
                            <a class="link" href="{{ route('tenders.bids.my') }}">Мои заявки&nbsp; →</a>
                        </div>
                    </article>

                    <article class="card news">
                        <div class="news-head">
                            <h3>Новости</h3>
                            <div class="news-tools">
                                <a href="{{ route('profile.keywords.index') }}">Настроить выдачу</a>
                                <a href="{{ route('news.index') }}">Все новости&nbsp; →</a>
                            </div>
                        </div>
                        @forelse($latestNews as $item)
                            <a class="news-row" href="{{ $item->link }}" target="_blank" rel="noopener">
                                <span class="dot" style="background:#0877ff"></span>
                                <span>{{ \Illuminate\Support\Str::limit($item->title, 60) }}</span>
                                <span class="time">{{ optional($item->published_at)->diffForHumans() }}</span>
                            </a>
                        @empty
                            <div class="news-row"><span></span><span class="meta">Новостей пока нет</span><span></span></div>
                        @endforelse
                    </article>
                </aside>
            </main>

            <div class="auth-overlay" data-panel-close data-panel-overlay></div>

            <aside class="auth-drawer auth-menu-drawer" aria-label="Меню">
                <div class="auth-drawer-head">
                    <span>Меню</span>
                    <button class="auth-close" type="button" data-panel-close aria-label="Закрыть меню"><svg><use href="#auth-x"/></svg></button>
                </div>
                <div class="auth-account">
                    <span class="photo"><img src="{{ $viewer->avatar_url }}" alt=""></span>
                    <div>
                        <strong>{{ $viewer->full_name }}</strong>
                        <a href="{{ route('users.show', $viewer) }}">Открыть профиль</a>
                    </div>
                </div>
                <div class="auth-drawer-label">Мои компании</div>
                @forelse($userCompanies as $company)
                    <a class="auth-company-line" href="{{ route('companies.show', $company) }}">
                        <span class="auth-company-mark"><svg><use href="#building"/></svg></span>
                        <span class="auth-company-copy">
                            {{ $company->name }}
                            <span class="auth-company-role">{{ $company->pivot->role === 'owner' ? 'Руководитель' : 'Представитель' }}</span>
                        </span>
                    </a>
                @empty
                    <div class="auth-company-line"><span class="auth-company-copy">Компаний пока нет</span></div>
                @endforelse
                <a class="auth-add-company" href="{{ route('companies.create') }}">+ Добавить компанию</a>

                <div class="auth-drawer-label">Моя работа</div>
                <a class="auth-menu-row current" href="{{ route('home') }}"><svg><use href="#auth-home"/></svg><span>Главная</span></a>
                <a class="auth-menu-row" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg><span>Компании</span></a>
                <a class="auth-menu-row" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg><span>Контакты</span></a>
                <a class="auth-menu-row" href="{{ route('tenders.index') }}"><img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""><span>Закупки</span></a>
                <a class="auth-menu-row" href="{{ route('news.index') }}"><svg><use href="#news"/></svg><span>Новости</span></a>
                <a class="auth-menu-row" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg><span>Проекты</span></a>

                <div class="auth-drawer-label">Настройки и поддержка</div>
                <a class="auth-menu-row" href="{{ route('profile.edit') }}"><svg><use href="#auth-settings"/></svg><span>Настройки профиля</span></a>
                <a class="auth-menu-row" href="{{ route('profile.edit') }}#feedback"><svg><use href="#help-chat"/></svg><span>Помощь и обратная связь</span></a>
            </aside>

            <aside class="auth-drawer auth-services-drawer" aria-label="Сервисы Bizzio">
                <div class="auth-drawer-head">
                    <span>Сервисы Bizzio</span>
                    <button class="auth-close" type="button" data-panel-close aria-label="Закрыть сервисы"><svg><use href="#auth-x"/></svg></button>
                </div>
                <div class="auth-services-label">Доступные сервисы</div>
                <div class="auth-services-grid">
                    <a class="auth-service-tile" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
                    <a class="auth-service-tile" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>
                    <a class="auth-service-tile" href="{{ route('tenders.index') }}"><img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
                    <a class="auth-service-tile" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
                    <a class="auth-service-tile" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg>Контакты</a>
                    <a class="auth-service-tile" href="{{ route('subscriptions.index') }}"><svg><use href="#auth-bookmark"/></svg>Подписки</a>
                </div>

                <div class="auth-services-label">
                    Будущие сервисы
                    <span class="auth-services-sub">Нажмите на карточку — ваш интерес будет учтён</span>
                </div>
                <div class="auth-services-grid">
                    @foreach($futureServices as $service)
                        <div class="auth-service-tile"
                             data-future-service="{{ $service['id'] }}"
                             data-service-name="{{ $service['name'] }}"
                             data-placement="auth_services_drawer">
                            <svg><use href="#{{ $service['icon'] }}"/></svg>{{ $service['name'] }}
                            <span class="auth-plus">+</span>
                        </div>
                    @endforeach
                </div>

                <div class="auth-rule"></div>
                <div class="auth-propose">
                    <svg><use href="#auth-light"/></svg>
                    <span>Не нашли нужный сервис?</span>
                    <button type="button"
                            data-inactive-feature="suggest_service"
                            data-feature-label="Предложить сервис"
                            data-placement="auth_services_drawer">Предложить сервис</button>
                </div>
            </aside>
        </div>
    </div>

    {{-- Мобильная версия: у эталона своя структура, вынесена в партиал --}}
    @include('partials.v26.mobile-authorized')
</div>
@endsection

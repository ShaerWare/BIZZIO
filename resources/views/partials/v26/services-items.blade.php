{{-- #181 Состав меню «Сервисы» — единственный источник для всех страниц.

     Параметры: $labelClass — заголовок группы, $gridClass — сетка, $tileClass — плитка,
     $plusClass — «плюсик» будущего сервиса, $placement — метка размещения для аналитики. --}}
@php
    $labelClass ??= 'auth-services-label';
    $gridClass ??= 'auth-services-grid';
    $tileClass ??= 'auth-service-tile';
    $plusClass ??= 'auth-plus';
    $placement ??= 'chrome_services_drawer';
    // На внутренних страницах контроллеры список будущих сервисов не передают.
    $services = $futureServices ?? \App\Http\Controllers\HomeController::futureServices();
@endphp

<div class="{{ $labelClass }}">Доступные сервисы</div>
<div class="{{ $gridClass }}">
    <a class="{{ $tileClass }}" href="{{ route('companies.index') }}"><svg><use href="#building"/></svg>Компании</a>
    <a class="{{ $tileClass }}" href="{{ route('projects.index') }}"><svg><use href="#clip"/></svg>Проекты</a>
    <a class="{{ $tileClass }}" href="{{ route('tenders.index') }}"><img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt="">Закупки</a>
    <a class="{{ $tileClass }}" href="{{ route('news.index') }}"><svg><use href="#news"/></svg>Новости</a>
    @auth
        <a class="{{ $tileClass }}" href="{{ route('friends.index') }}"><svg><use href="#users"/></svg>Контакты</a>
        <a class="{{ $tileClass }}" href="{{ route('subscriptions.index') }}"><svg><use href="#auth-bookmark"/></svg>Подписки</a>
    @else
        <a class="{{ $tileClass }}" href="{{ config('app.register_url') }}"><svg><use href="#users"/></svg>Контакты</a>
    @endauth
</div>

<div class="{{ $labelClass }}">
    Будущие сервисы
    <span class="auth-services-sub">Нажмите на карточку — ваш интерес будет учтён</span>
</div>
<div class="{{ $gridClass }}">
    @foreach($services as $service)
        <div class="{{ $tileClass }}"
             data-future-service="{{ $service['id'] }}"
             data-service-name="{{ $service['name'] }}"
             data-placement="{{ $placement }}">
            <svg><use href="#{{ $service['icon'] }}"/></svg>{{ $service['name'] }}
            <span class="{{ $plusClass }}">+</span>
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
            data-placement="{{ $placement }}">Предложить сервис</button>
</div>

@include('partials.v26.social-links')

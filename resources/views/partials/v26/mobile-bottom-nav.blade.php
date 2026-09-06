{{-- #181 Нижняя навигация мобильной версии — единственный источник для всех страниц.

     В эталоне v26 это ровно четыре ячейки с иконками: поиск, сообщения, уведомления,
     помощь. Внутренние страницы раньше рисовали свою навигацию (Главная / Закупки /
     Компании / Профиль) — заказчик увидел её как «старое нижнее меню» (замечание от 03.09).

     Параметры: $navClass — дополнительный класс <nav> (у гостевой главной свой),
     $placement — метка размещения для аналитики Метрики. --}}
@php
    $navClass ??= '';
    $placement ??= 'page_mobile_bottom';
    $navUser = auth()->user();
@endphp

<nav class="bz-bottom {{ $navClass }}" aria-label="Основная навигация">
    <a class="bz-bottom-item" href="{{ $navUser ? route('search.index') : config('app.auth_url') }}" aria-label="Поиск"><svg><use href="#search"/></svg></a>
    @if($navUser)
        {{-- Раздела сообщений пока нет: элемент остаётся видимым и уходит в аналитику --}}
        <button class="bz-bottom-item" type="button"
                data-inactive-feature="messages"
                data-feature-label="Сообщения"
                data-placement="{{ $placement }}"
                aria-label="Сообщения"><svg><use href="#chat"/></svg></button>
        <a class="bz-bottom-item" href="{{ route('notifications.index') }}" aria-label="Уведомления">
            <svg><use href="#bell"/></svg>
            @php($unreadCount = $navUser->unreadNotifications()->count())
            @if($unreadCount > 0)
                <span class="bz-count">{{ $unreadCount }}</span>
            @endif
        </a>
        <a class="bz-bottom-item" href="{{ route('profile.edit') }}#feedback" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
    @else
        <a class="bz-bottom-item" href="{{ config('app.auth_url') }}" aria-label="Сообщения"><svg><use href="#chat"/></svg></a>
        <a class="bz-bottom-item" href="{{ config('app.auth_url') }}" aria-label="Уведомления"><svg><use href="#bell"/></svg></a>
        <a class="bz-bottom-item" href="{{ config('app.auth_url') }}" aria-label="Помощь и обратная связь"><svg><use href="#help-chat"/></svg></a>
    @endif
</nav>

{{-- #181 Состав меню раздела (левая панель «Меню») — единственный источник для всех страниц.

     Сюда же переехали прежние вложенные меню второго уровня из Tailwind-шапки:
     выпадающие «Закупки» и «Новости» стали разделами этого меню.

     Параметры: $rowClass — класс пункта, $labelClass — класс заголовка группы,
     $current — активный пункт (home|companies|projects|friends|tenders|news). --}}
@php
    $rowClass ??= 'auth-menu-row';
    $labelClass ??= 'auth-drawer-label';
    $current ??= request()->routeIs('home') ? 'home' : null;

    $isCurrent = fn (string $key) => $current === $key;
@endphp

<div class="{{ $labelClass }}">Моя работа</div>
<a class="{{ $rowClass }} {{ $isCurrent('home') ? 'current bz-current' : '' }}" href="{{ route('home') }}">
    <svg><use href="#auth-home"/></svg><span>Главная</span>
</a>
<a class="{{ $rowClass }} {{ request()->routeIs('companies.*') ? 'current bz-current' : '' }}" href="{{ route('companies.index') }}">
    <svg><use href="#building"/></svg><span>Компании</span>
</a>
<a class="{{ $rowClass }} {{ request()->routeIs('projects.*') ? 'current bz-current' : '' }}" href="{{ route('projects.index') }}">
    <svg><use href="#clip"/></svg><span>Проекты</span>
</a>
@auth
    <a class="{{ $rowClass }} {{ request()->routeIs('friends.*') ? 'current bz-current' : '' }}" href="{{ route('friends.index') }}">
        <svg><use href="#users"/></svg><span>Контакты</span>
    </a>
@endauth

{{-- Закупки: прежнее выпадающее меню второго уровня целиком --}}
<div class="{{ $labelClass }}">Закупки</div>
<a class="{{ $rowClass }} {{ request()->routeIs('tenders.index') ? 'current bz-current' : '' }}" href="{{ route('tenders.index') }}">
    <img class="procurement-icon" src="/images/v26/bizzio-quick-icon-procurement-base-v5.png" alt=""><span>Найти закупку</span>
</a>
@auth
    @if(auth()->user()->isModeratorOfAnyCompany())
        <a class="{{ $rowClass }}" href="{{ route('rfqs.create', ['procedure' => 'commercial']) }}">
            <img class="procurement-icon" src="/images/v26/bizzio-quick-icon-create-procurement-v4.png" alt=""><span>Создать закупку</span>
        </a>
        <a class="{{ $rowClass }} {{ request()->routeIs('tenders.my') ? 'current bz-current' : '' }}" href="{{ route('tenders.my') }}">
            <svg><use href="#gavel"/></svg><span>Мои закупки</span>
        </a>
    @endif
    <a class="{{ $rowClass }} {{ request()->routeIs('tenders.invitations.my') ? 'current bz-current' : '' }}" href="{{ route('tenders.invitations.my') }}">
        <svg><use href="#clip"/></svg><span>Мои приглашения</span>
    </a>
    <a class="{{ $rowClass }} {{ request()->routeIs('tenders.bids.my') ? 'current bz-current' : '' }}" href="{{ route('tenders.bids.my') }}">
        <svg><use href="#file"/></svg><span>Мои заявки</span>
    </a>
@endauth
<a class="{{ $rowClass }} {{ request()->routeIs('tenders.rules') ? 'current bz-current' : '' }}" href="{{ route('tenders.rules') }}">
    <svg><use href="#file"/></svg><span>Правила закупок</span>
</a>

{{-- Новости: прежнее выпадающее меню второго уровня --}}
<div class="{{ $labelClass }}">Новости</div>
<a class="{{ $rowClass }} {{ request()->routeIs('news.index') ? 'current bz-current' : '' }}" href="{{ route('news.index') }}">
    <svg><use href="#news"/></svg><span>Лента новостей</span>
</a>
@auth
    <a class="{{ $rowClass }} {{ request()->routeIs('profile.keywords.*') ? 'current bz-current' : '' }}" href="{{ route('profile.keywords.index') }}">
        <svg><use href="#auth-settings"/></svg><span>Ключевые слова</span>
    </a>
@endauth

<div class="{{ $labelClass }}">Настройки и поддержка</div>
@auth
    <a class="{{ $rowClass }}" href="{{ route('profile.edit') }}#feedback">
        <svg><use href="#help-chat"/></svg><span>Помощь и обратная связь</span>
    </a>
@else
    <a class="{{ $rowClass }}" href="{{ config('app.auth_url') }}">
        <svg><use href="#help-chat"/></svg><span>Помощь и обратная связь</span>
    </a>
@endauth

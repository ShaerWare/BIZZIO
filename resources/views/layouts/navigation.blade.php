<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('images/apple-touch-icon.png') }}" alt="Icon" class="reduce-10 h-10 w-auto max-h-12 object-contain">
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <!-- Dashboard -->
                    @auth
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @endauth

                    <!-- Компании -->
                    <x-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">
                        {{ __('Компании') }}
                    </x-nav-link>
                    
                    <!-- Проекты -->
                    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                        {{ __('Проекты') }}
                    </x-nav-link>
                    
                    <!-- Тендеры (Dropdown) -->
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = ! open"
                                    class="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2
                                        {{ request()->routeIs('tenders.*', 'rfqs.*', 'auctions.*', 'bids.*', 'invitations.*') ? 'border-emerald-400 text-gray-900 focus:border-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300' }}">
                                <span>{{ __('Тендеры') }}</span>
                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                style="display: none;">
                                <div class="py-1">
                                    <a href="{{ route('tenders.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        {{ __('Найти тендер') }}
                                    </a>
                                    @auth
                                        @if(auth()->user()->isModeratorOfAnyCompany())
                                            <div class="border-t border-gray-100"></div>
                                            <a href="{{ route('rfqs.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                {{ __('Разместить Запрос котировок') }}
                                            </a>
                                            <a href="{{ route('auctions.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                {{ __('Разместить аукцион') }}
                                            </a>
                                        @endif
                                        <div class="border-t border-gray-100"></div>
                                        <a href="{{ route('tenders.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Мои тендеры') }}
                                        </a>
                                        <a href="{{ route('tenders.bids.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Мои заявки') }}
                                        </a>
                                        <a href="{{ route('tenders.invitations.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Мои приглашения') }}
                                        </a>
                                        <div class="border-t border-gray-100"></div>
                                        <a href="{{ route('join-requests.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Мои запросы на присоединение') }}
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Новости (Dropdown) -->
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = ! open" 
                                    class="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2
                                        {{ request()->routeIs('news.*', 'profile.keywords.*') ? 'border-emerald-400 text-gray-900 focus:border-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300' }}">
                                <span>{{ __('Новости') }}</span>
                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" 
                                @click.away="open = false"
                                x-transition
                                class="absolute z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                style="display: none;">
                                <div class="py-1">
                                    <a href="{{ route('news.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        {{ __('Лента новостей') }}
                                    </a>
                                    @auth
                                        <a href="{{ route('profile.keywords.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Ключевые слова') }}
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Dropdown (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Global Search -->
                <div class="relative mr-4" x-data="{ open: false, query: '', results: [], loading: false }">
                    <div class="relative">
                        <input
                            type="text"
                            x-model="query"
                            @input.debounce.300ms="if(query.length >= 2) { loading = true; fetch('{{ route('search.quick') }}?q=' + encodeURIComponent(query)).then(r => r.json()).then(d => { results = d.results || []; loading = false; open = true; }).catch(() => loading = false); } else { results = []; open = false; }"
                            @focus="if(results.length > 0) open = true"
                            @keydown.escape="open = false"
                            placeholder="Поиск..."
                            class="w-48 lg:w-64 pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <div x-show="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div
                        x-show="open && results.length > 0"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                        style="display: none;"
                    >
                        <div class="py-2 max-h-96 overflow-y-auto">
                            <template x-for="result in results" :key="result.type + '-' + result.id">
                                <a :href="result.url" class="flex items-center px-4 py-2 hover:bg-gray-100">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-600 mr-3 text-xs font-medium" x-text="result.type_label.substring(0, 1)"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="result.title"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="result.subtitle || result.type_label"></p>
                                    </div>
                                </a>
                            </template>
                        </div>
                        <div class="px-4 py-2 border-t border-gray-100 bg-gray-50">
                            <a :href="'{{ route('search.index') }}?q=' + encodeURIComponent(query)" class="block text-center text-sm text-emerald-600 hover:text-emerald-800">
                                {{ __('Все результаты') }}
                            </a>
                        </div>
                    </div>
                </div>

                @auth
                    <!-- Notifications Bell -->
                    <div class="relative mr-4" x-data="{ open: false, notifications: [], loading: false }">
                        <button
                            @click="open = !open; if(open && notifications.length === 0) { loading = true; fetch('{{ route('notifications.index') }}', {headers: {'Accept': 'application/json'}}).then(r => r.json()).then(d => { notifications = d.notifications || []; loading = false; }).catch(() => loading = false); }"
                            class="relative p-1 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 rounded-full"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            @php
                                $unreadCount = auth()->user()->unreadNotifications()->count();
                            @endphp
                            @if($unreadCount > 0)
                                <span
                                    id="notification-badge"
                                    class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform bg-red-500 rounded-full"
                                >
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @else
                                <span id="notification-badge" class="hidden absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform bg-red-500 rounded-full"></span>
                            @endif
                        </button>

                        <!-- Dropdown уведомлений -->
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                            style="display: none;"
                        >
                            <div class="py-2">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-900">{{ __('Уведомления') }}</span>
                                        <a href="{{ route('notifications.index') }}" class="text-xs text-emerald-600 hover:text-emerald-800">{{ __('Все') }}</a>
                                    </div>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <template x-if="loading">
                                        <div class="px-4 py-6 text-center text-gray-500 text-sm">
                                            {{ __('Загрузка...') }}
                                        </div>
                                    </template>
                                    <template x-if="!loading && notifications.length === 0">
                                        <div class="px-4 py-6 text-center text-gray-500 text-sm">
                                            {{ __('Нет уведомлений') }}
                                        </div>
                                    </template>
                                    <template x-for="notification in notifications.slice(0, 5)" :key="notification.id">
                                        <a
                                            :href="notification.data?.url || '#'"
                                            class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-0"
                                            :class="{ 'bg-emerald-50': !notification.read_at }"
                                        >
                                            <p class="text-sm text-gray-900" x-text="notification.data?.project_title || notification.data?.tender_number || notification.data?.message || 'Новое уведомление'"></p>
                                            <p class="text-xs text-gray-500 mt-1" x-text="new Date(notification.created_at).toLocaleString('ru-RU')"></p>
                                        </a>
                                    </template>
                                </div>
                                <div class="px-4 py-2 border-t border-gray-100 bg-gray-50">
                                    <a href="{{ route('notifications.index') }}" class="block text-center text-sm text-emerald-600 hover:text-emerald-800">
                                        {{ __('Показать все уведомления') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Профиль') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Выйти') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Ссылки для гостей -->
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900 px-3 py-2">
                        {{ __('Войти') }}
                    </a>
                    <a href="{{ route('register') }}" class="text-sm text-gray-700 hover:text-gray-900 px-3 py-2">
                        {{ __('Регистрация') }}
                    </a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    {{-- G3: Добавлен скролл для длинного мобильного меню --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden max-h-[calc(100vh-4rem)] overflow-y-auto">
        <div class="pt-2 pb-3 space-y-1">
            <!-- Dashboard -->
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <!-- Компании -->
            <x-responsive-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">
                {{ __('Компании') }}
            </x-responsive-nav-link>

            <!-- Проекты -->
            <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                {{ __('Проекты') }}
            </x-responsive-nav-link>

            <!-- Тендеры -->
            <div class="border-t border-gray-200 pt-2 mt-2">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    {{ __('Тендеры') }}
                </div>

                <x-responsive-nav-link :href="route('tenders.index')" :active="request()->routeIs('tenders.index')">
                    {{ __('Найти тендер') }}
                </x-responsive-nav-link>

                @auth
                    @if(auth()->user()->isModeratorOfAnyCompany())
                        <div class="border-t border-gray-100 my-2"></div>

                        <x-responsive-nav-link :href="route('rfqs.create')" :active="request()->routeIs('rfqs.create')">
                            {{ __('Разместить Запрос котировок') }}
                        </x-responsive-nav-link>

                        <x-responsive-nav-link :href="route('auctions.create')" :active="request()->routeIs('auctions.create')">
                            {{ __('Разместить аукцион') }}
                        </x-responsive-nav-link>
                    @endif

                    <div class="border-t border-gray-100 my-2"></div>

                    <x-responsive-nav-link :href="route('tenders.my')" :active="request()->routeIs('tenders.my')">
                        {{ __('Мои тендеры') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('tenders.bids.my')" :active="request()->routeIs('tenders.bids.my')">
                        {{ __('Мои заявки') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('tenders.invitations.my')" :active="request()->routeIs('tenders.invitations.my')">
                        {{ __('Мои приглашения') }}
                    </x-responsive-nav-link>

                    <div class="border-t border-gray-100 my-2"></div>

                    <x-responsive-nav-link :href="route('join-requests.index')" :active="request()->routeIs('join-requests.index')">
                        {{ __('Мои запросы на присоединение') }}
                    </x-responsive-nav-link>
                @endauth
            </div>

            <!-- Новости -->
            <div class="border-t border-gray-200 pt-2 mt-2">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    {{ __('Новости') }}
                </div>
                
                <x-responsive-nav-link :href="route('news.index')" :active="request()->routeIs('news.index')">
                    {{ __('Лента новостей') }}
                </x-responsive-nav-link>

                @auth
                    <x-responsive-nav-link :href="route('profile.keywords.index')" :active="request()->routeIs('profile.keywords.*')">
                        {{ __('Ключевые слова') }}
                    </x-responsive-nav-link>
                @endauth
            </div>
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Профиль') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Выйти') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')">
                        {{ __('Войти') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">
                        {{ __('Регистрация') }}
                    </x-responsive-nav-link>
                </div>
            </div>
        @endauth
    </div>
</nav>
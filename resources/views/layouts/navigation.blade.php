<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('images/assembly-icon.png') }}" alt="Icon" class="reduce-10">
                    </a>
                </div>

<!-- Navigation Links -->
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
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
                                    {{ request()->routeIs('rfqs.*', 'bids.*', 'invitations.*') ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300' }}">
                            <span>Тендеры и аукционы</span>
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
                                <a href="{{ route('rfqs.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Найти тендер
                                </a>
                                <a href="{{ route('auctions.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Найти аукцион
                </a>
                                @auth
                                    @if(auth()->user()->isModeratorOfAnyCompany())
                                        <a href="{{ route('rfqs.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Разместить Запрос котировок
                                        </a>
                                        <a href="{{ route('auctions.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Разместить аукцион
                        </a>
                                    @endif
                                    <div class="border-t border-gray-100"></div>
                                    <a href="{{ route('rfqs.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Мои тендеры
                                    </a>
                                    <a href="{{ route('auctions.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Мои аукционы
                    </a>
                                    <a href="{{ route('bids.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Мои заявки  (RFQ)
                                    </a>
                                    <a href="{{ route('auctions.bids.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Мои заявки (Аукционы)
                                    </a>
                                    <a href="{{ route('invitations.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Мои приглашения (RFQ)
                                    </a>
                                     <a href="{{ route('auctions.invitations.my') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Мои приглашения (Аукционы)
                    </a>
                                    <a href="{{ route('join-requests.index') }}" 
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Мои запросы на присоединение
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
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
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Ссылки для гостей -->
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900 px-3 py-2">
                        {{ __('Log in') }}
                    </a>
                    <a href="{{ route('register') }}" class="text-sm text-gray-700 hover:text-gray-900 px-3 py-2">
                        {{ __('Register') }}
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

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">
                {{ __('Компании') }}
            </x-responsive-nav-link>
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
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')">
                        {{ __('Log in') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">
                        {{ __('Register') }}
                    </x-responsive-nav-link>
                </div>
            </div>
        @endauth
    </div>
</nav>
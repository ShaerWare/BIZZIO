<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Поиск') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Форма поиска -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form action="{{ route('search.index') }}" method="GET" class="flex gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="q"
                                value="{{ $query }}"
                                placeholder="Поиск по компаниям, проектам, тендерам..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                autofocus
                            >
                        </div>
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Найти
                        </button>
                    </form>
                </div>
            </div>

            @if(strlen($query) >= 2)
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Боковая панель: Фильтры -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-4">Тип</h3>
                                <nav class="space-y-1">
                                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'all']) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-md text-sm {{ $type === 'all' ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                                        <span>Все результаты</span>
                                        <span class="text-xs {{ $type === 'all' ? 'text-emerald-600' : 'text-gray-400' }}">
                                            {{ $counts['users'] + $counts['companies'] + $counts['projects'] + $counts['rfqs'] + $counts['auctions'] }}
                                        </span>
                                    </a>
                                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'companies']) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-md text-sm {{ $type === 'companies' ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                                        <span>Компании</span>
                                        <span class="text-xs {{ $type === 'companies' ? 'text-emerald-600' : 'text-gray-400' }}">{{ $counts['companies'] }}</span>
                                    </a>
                                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'projects']) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-md text-sm {{ $type === 'projects' ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                                        <span>Проекты</span>
                                        <span class="text-xs {{ $type === 'projects' ? 'text-emerald-600' : 'text-gray-400' }}">{{ $counts['projects'] }}</span>
                                    </a>
                                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'rfqs']) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-md text-sm {{ $type === 'rfqs' ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                                        <span>Запросы цен</span>
                                        <span class="text-xs {{ $type === 'rfqs' ? 'text-emerald-600' : 'text-gray-400' }}">{{ $counts['rfqs'] }}</span>
                                    </a>
                                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'auctions']) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-md text-sm {{ $type === 'auctions' ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                                        <span>Аукционы</span>
                                        <span class="text-xs {{ $type === 'auctions' ? 'text-emerald-600' : 'text-gray-400' }}">{{ $counts['auctions'] }}</span>
                                    </a>
                                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'users']) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-md text-sm {{ $type === 'users' ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                                        <span>Пользователи</span>
                                        <span class="text-xs {{ $type === 'users' ? 'text-emerald-600' : 'text-gray-400' }}">{{ $counts['users'] }}</span>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- Основная колонка: Результаты -->
                    <div class="lg:col-span-3">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                @if($results->isEmpty())
                                    <div class="text-center py-12">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Ничего не найдено</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            По запросу "{{ $query }}" ничего не найдено. Попробуйте изменить запрос.
                                        </p>
                                    </div>
                                @else
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500">
                                            Найдено результатов: {{ $results->count() }}
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach($results as $result)
                                            <a href="{{ $result['url'] }}" class="block p-4 rounded-lg border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 transition-colors duration-150">
                                                <div class="flex items-start gap-4">
                                                    <!-- Иконка типа -->
                                                    <div class="flex-shrink-0">
                                                        @switch($result['type'])
                                                            @case('user')
                                                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                    </svg>
                                                                </div>
                                                                @break
                                                            @case('company')
                                                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                                    </svg>
                                                                </div>
                                                                @break
                                                            @case('project')
                                                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                                                    </svg>
                                                                </div>
                                                                @break
                                                            @case('rfq')
                                                                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                                </div>
                                                                @break
                                                            @case('auction')
                                                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                </div>
                                                                @break
                                                        @endswitch
                                                    </div>

                                                    <!-- Контент -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                                                {{ $result['title'] }}
                                                            </h4>
                                                            @if(isset($result['is_verified']) && $result['is_verified'])
                                                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            @endif
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                                {{ $result['type_label'] }}
                                                            </span>
                                                        </div>
                                                        @if(isset($result['subtitle']) && $result['subtitle'])
                                                            <p class="text-sm text-gray-500 truncate">
                                                                {{ $result['subtitle'] }}
                                                            </p>
                                                        @endif
                                                        @if(isset($result['description']) && $result['description'])
                                                            <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                                                                {{ Str::limit($result['description'], 150) }}
                                                            </p>
                                                        @endif
                                                    </div>

                                                    <!-- Стрелка -->
                                                    <div class="flex-shrink-0">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(strlen($query) > 0 && strlen($query) < 2)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <p class="text-gray-500">Введите минимум 2 символа для поиска</p>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Глобальный поиск</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Ищите компании, проекты, тендеры и аукционы по названию, описанию или номеру
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

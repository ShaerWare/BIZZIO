@extends('layouts.app')

@section('title', 'Тендеры')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Тендеры</h1>
                <p class="mt-1 text-sm text-gray-500">Запросы цен и аукционы</p>
            </div>
            @auth
                @if(auth()->user()->isModeratorOfAnyCompany())
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Разместить
                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                             style="display: none;">
                            <div class="py-1">
                                <a href="{{ route('rfqs.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Запрос цен
                                </a>
                                <a href="{{ route('auctions.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Аукцион
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endauth
        </div>

        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('tenders.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Поиск -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                            <input type="text" name="search" id="search"
                                   value="{{ request('search') }}"
                                   placeholder="Название или номер"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <!-- Вид процедуры -->
                        <div>
                            <label for="kind" class="block text-sm font-medium text-gray-700 mb-2">Вид процедуры</label>
                            <select name="kind" id="kind"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все виды</option>
                                <option value="rfq" {{ request('kind') === 'rfq' ? 'selected' : '' }}>Запрос цен</option>
                                <option value="auction" {{ request('kind') === 'auction' ? 'selected' : '' }}>Аукцион</option>
                            </select>
                        </div>

                        <!-- Статус -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <select name="status" id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все статусы</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Приём заявок</option>
                                <option value="trading" {{ request('status') === 'trading' ? 'selected' : '' }}>Торги (аукционы)</option>
                                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Завершённые</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Черновики</option>
                            </select>
                        </div>

                        <!-- Тип -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Тип процедуры</label>
                            <select name="type" id="type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все типы</option>
                                <option value="open" {{ request('type') === 'open' ? 'selected' : '' }}>Открытые</option>
                                <option value="closed" {{ request('type') === 'closed' ? 'selected' : '' }}>Закрытые</option>
                            </select>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex items-end space-x-2">
                            <button type="submit"
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                Применить
                            </button>
                            <a href="{{ route('tenders.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                                Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список тендеров -->
        @if($items->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Тендеры не найдены</h3>
                    <p class="mt-1 text-sm text-gray-500">Попробуйте изменить параметры поиска</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($items as $item)
                    @if($item['kind'] === 'rfq')
                        <x-rfq-card :rfq="$item['model']" />
                    @else
                        <x-auction-card :auction="$item['model']" />
                    @endif
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $items->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

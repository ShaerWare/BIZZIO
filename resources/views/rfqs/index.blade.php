@extends('layouts.app')

@section('title', 'Запросы котировок')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Запросы котировок</h1>
            @auth
                @if(auth()->user()->isModeratorOfAnyCompany())
                    <a href="{{ route('rfqs.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Разместить RFQ
                    </a>
                @endif
            @endauth
        </div>

        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('rfqs.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Поиск -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                Поиск
                            </label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="{{ request('search') }}"
                                   placeholder="Название или номер"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <!-- Статус -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Статус
                            </label>
                            <select name="status" 
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все статусы</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Активные</option>
                                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Завершённые</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Черновики</option>
                            </select>
                        </div>

                        <!-- Тип -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                Тип процедуры
                            </label>
                            <select name="type" 
                                    id="type"
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
                            <a href="{{ route('rfqs.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                                Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список RFQ -->
        @if($rfqs->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Запросы котировок не найдены</h3>
                    <p class="mt-1 text-sm text-gray-500">Попробуйте изменить параметры поиска</p>
                    @auth
                        @if(auth()->user()->isModeratorOfAnyCompany())
                            <div class="mt-6">
                                <a href="{{ route('rfqs.create') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                    Разместить первый RFQ
                                </a>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($rfqs as $rfq)
                    <x-rfq-card :rfq="$rfq" />
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $rfqs->withQueryString()->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
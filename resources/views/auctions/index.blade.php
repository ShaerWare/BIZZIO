@extends('layouts.app')

@section('title', 'Каталог аукционов')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Каталог аукционов</h1>
                <p class="mt-1 text-sm text-gray-500">Найдите интересующие вас обратные аукционы</p>
            </div>
            
            @auth
                @if(auth()->user()->isModeratorOfAnyCompany())
                    <a href="{{ route('auctions.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Разместить аукцион
                    </a>
                @endif
            @endauth
        </div>

        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('auctions.index') }}" class="flex flex-col md:flex-row gap-4">
                    <!-- Поиск -->
                    <div class="flex-1">
                        <input type="text" 
                               name="search" 
                               placeholder="Поиск по названию или номеру..."
                               value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <!-- Статус -->
                    <div>
                        <select name="status" 
                                class="w-full md:w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Все статусы</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Черновик</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Приём заявок</option>
                            <option value="trading" {{ request('status') === 'trading' ? 'selected' : '' }}>Торги</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Завершён</option>
                        </select>
                    </div>
                    
                    <!-- Тип -->
                    <div>
                        <select name="type" 
                                class="w-full md:w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Все типы</option>
                            <option value="open" {{ request('type') === 'open' ? 'selected' : '' }}>Открытая</option>
                            <option value="closed" {{ request('type') === 'closed' ? 'selected' : '' }}>Закрытая</option>
                        </select>
                    </div>
                    
                    <!-- Кнопки -->
                    <div class="flex gap-2">
                        <button type="submit" 
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Применить
                        </button>
                        <a href="{{ route('auctions.index') }}" 
                           class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Сбросить
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список аукционов -->
        @if($auctions->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($auctions as $auction)
                    <x-auction-card :auction="$auction" />
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $auctions->links() }}
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-4 text-gray-500">Аукционы не найдены</p>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
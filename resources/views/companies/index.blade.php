@extends('layouts.app')

@section('title', 'Каталог компаний')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Заголовок и кнопка создания -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Каталог компаний</h1>
                <p class="text-gray-600 mt-1">Найдите надёжных партнёров для бизнеса</p>
            </div>
            @auth
                <a href="{{ route('companies.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Создать компанию
                </a>
            @endauth
        </div>

        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('companies.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Поиск -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       id="search" 
                                       value="{{ request('search') }}"
                                       placeholder="Название или ИНН"
                                       class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <!-- Отрасль -->
                        <div>
                            <label for="industry_id" class="block text-sm font-medium text-gray-700 mb-1">Отрасль</label>
                            <select name="industry_id" 
                                    id="industry_id"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Все отрасли</option>
                                @foreach($industries as $industry)
                                    <option value="{{ $industry->id }}" 
                                            {{ request('industry_id') == $industry->id ? 'selected' : '' }}>
                                        {{ $industry->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Верификация -->
                        <div>
                            <label for="is_verified" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                            <select name="is_verified" 
                                    id="is_verified"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Все компании</option>
                                <option value="1" {{ request('is_verified') === '1' ? 'selected' : '' }}>Верифицированные</option>
                                <option value="0" {{ request('is_verified') === '0' ? 'selected' : '' }}>Не верифицированные</option>
                            </select>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex gap-2">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Применить фильтры
                        </button>
                        <a href="{{ route('companies.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Сбросить
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список компаний -->
        @if($companies->count() > 0)
            <!-- Статистика -->
            <div class="mb-4 text-sm text-gray-600">
                Найдено компаний: <span class="font-semibold">{{ $companies->total() }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($companies as $company)
                    <x-company-card :company="$company" />
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $companies->links() }}
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Компании не найдены</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Попробуйте изменить параметры поиска или 
                        @auth
                            <a href="{{ route('companies.create') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                создайте первую компанию!
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                зарегистрируйтесь, чтобы добавить компанию.
                            </a>
                        @endauth
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
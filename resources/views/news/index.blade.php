@extends('layouts.app')

@section('title', 'Новости')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Новости</h1>
            <p class="mt-1 text-sm text-gray-500">Агрегатор отраслевых новостей</p>
        </div>

        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('news.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- Фильтр по источнику -->
                        <div>
                            <label for="source_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Источник
                            </label>
                            <select name="source_id" 
                                    id="source_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все источники</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}" 
                                            {{ request('source_id') == $source->id ? 'selected' : '' }}>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Фильтр по дате -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                                Дата публикации
                            </label>
                            <input type="date" 
                                   name="date" 
                                   id="date"
                                   value="{{ request('date') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <!-- Фильтр по ключевым словам -->
                        @auth
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ключевые слова
                                </label>
                                <div class="flex items-center mt-3">
                                    <input type="checkbox" 
                                           name="apply_keywords" 
                                           id="apply_keywords"
                                           value="1"
                                           {{ request('apply_keywords') ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <label for="apply_keywords" class="ml-2 text-sm text-gray-600">
                                        Фильтровать по моим ключевым словам
                                        @if(!empty($userKeywords))
                                            <span class="text-xs text-gray-500">({{ count($userKeywords) }})</span>
                                        @endif
                                    </label>
                                </div>
                                @if(empty($userKeywords))
                                    <p class="mt-1 text-xs text-gray-500">
                                        <a href="{{ route('profile.keywords.index') }}" class="text-emerald-600 hover:text-emerald-500">
                                            Добавить ключевые слова
                                        </a>
                                    </p>
                                @endif
                            </div>
                        @endauth
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center space-x-2">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Применить фильтры
                        </button>
                        
                        @if(request()->anyFilled(['source_id', 'date', 'apply_keywords']))
                            <a href="{{ route('news.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                                Сбросить
                            </a>
                        @endif
                    </div>

                    <!-- Активные ключевые слова -->
                    @if(request('apply_keywords') && !empty($userKeywords))
                        <div class="mt-3 p-3 bg-emerald-50 rounded border border-emerald-200">
                            <p class="text-xs font-medium text-emerald-800 mb-2">Фильтрация по ключевым словам (все должны присутствовать):</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($userKeywords as $keyword)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        {{ $keyword }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Список новостей -->
        @if($news->count() > 0)
            <div class="space-y-4">
                @foreach($news as $item)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex">
                                <!-- Изображение -->
                                @if($item->image)
                                    <div class="flex-shrink-0 mr-4">
                                        <img src="{{ $item->image }}" 
                                             alt="{{ $item->title }}"
                                             class="w-32 h-24 object-cover rounded"
                                             onerror="this.style.display='none'">
                                    </div>
                                @endif

                                <!-- Контент -->
                                <div class="flex-1">
                                    <!-- Источник и дата -->
                                    <div class="flex items-center text-xs text-gray-500 mb-2">
                                        <span class="font-semibold text-emerald-600">{{ $item->rssSource->name }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $item->published_at->format('d.m.Y H:i') }}</span>
                                    </div>

                                    <!-- Заголовок -->
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        <a href="{{ $item->link }}" 
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="hover:text-emerald-600 transition">
                                            {{ $item->title }}
                                        </a>
                                    </h3>

                                    <!-- Описание -->
                                    @if($item->description)
                                        <p class="text-sm text-gray-600 line-clamp-2">
                                            {{ Str::limit(strip_tags($item->description), 200) }}
                                        </p>
                                    @endif

                                    <!-- Ссылка -->
                                    <a href="{{ $item->link }}" 
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center mt-2 text-sm text-emerald-600 hover:text-emerald-500">
                                        Читать далее
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $news->appends(request()->query())->links() }}
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Новостей не найдено</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request('apply_keywords'))
                            Попробуйте изменить ключевые слова или 
                            <a href="{{ route('news.index') }}" class="text-emerald-600 hover:text-emerald-500">
                                показать все новости
                            </a>
                        @else
                            Новости появятся после парсинга RSS-лент
                        @endif
                    </p>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
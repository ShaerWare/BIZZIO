@extends('layouts.app')

@section('title', 'Ключевые слова')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Ключевые слова</h1>
            <p class="mt-1 text-sm text-gray-500">
                Настройте фильтрацию новостей по интересующим вас темам
            </p>
        </div>

        <!-- Сообщения -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Информация о фильтрации -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Как работает фильтрация?</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Если указано <strong>одно</strong> ключевое слово — показываем новости, содержащие это слово</li>
                            <li>Если указано <strong>несколько</strong> ключевых слов — показываем новости, содержащие <strong>ВСЕ</strong> эти слова одновременно</li>
                            <li>Поиск ведётся в заголовке и описании новости</li>
                            <li>Максимум {{ App\Http\Controllers\UserKeywordController::MAX_KEYWORDS }} ключевых слов</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Форма добавления ключевого слова -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Добавить ключевое слово</h2>
                
                <form method="POST" action="{{ route('profile.keywords.store') }}">
                    @csrf
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-1">
                            <input type="text" 
                                   name="keyword" 
                                   id="keyword"
                                   placeholder="Например: искусственный интеллект"
                                   maxlength="50"
                                   required
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('keyword') border-red-500 @enderror">
                            @error('keyword')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Добавить
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список ключевых слов -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Ваши ключевые слова
                        <span class="text-sm text-gray-500 font-normal">
                            ({{ $keywords->count() }} из {{ App\Http\Controllers\UserKeywordController::MAX_KEYWORDS }})
                        </span>
                    </h2>
                    
                    @if($keywords->count() > 0)
                        <a href="{{ route('news.index', ['apply_keywords' => 1]) }}" 
                           class="text-sm text-indigo-600 hover:text-indigo-500">
                            Смотреть новости →
                        </a>
                    @endif
                </div>

                @if($keywords->count() > 0)
                    <div class="space-y-2">
                        @foreach($keywords as $keyword)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <span class="text-sm font-medium text-gray-900">{{ $keyword->keyword }}</span>
                                
                                <form method="POST" action="{{ route('profile.keywords.destroy', $keyword) }}"
                                      onsubmit="return confirm('Удалить это ключевое слово?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-500 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Нет ключевых слов</h3>
                        <p class="mt-1 text-sm text-gray-500">Добавьте ключевые слова для персонализированной ленты новостей</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
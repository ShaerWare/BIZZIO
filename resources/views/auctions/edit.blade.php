@extends('layouts.app')

@section('title', 'Редактировать аукцион')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Редактировать аукцион</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $auction->number }} — {{ $auction->title }}</p>
        </div>

        <!-- Форма -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('auctions.update', $auction) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Название -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Название <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               required
                               value="{{ old('title', $auction->title) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Описание -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Описание
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="5"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description', $auction->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Дата окончания приёма заявок -->
                    <div class="mb-6">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата окончания приёма заявок <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               name="end_date" 
                               id="end_date" 
                               required
                               value="{{ old('end_date', $auction->end_date->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Текущее значение: {{ $auction->end_date->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    <!-- Дата начала торгов -->
                    <div class="mb-6">
                        <label for="trading_start" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата начала торгов <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               name="trading_start" 
                               id="trading_start" 
                               required
                               value="{{ old('trading_start', $auction->trading_start->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('trading_start') border-red-500 @enderror">
                        @error('trading_start')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Текущее значение: {{ $auction->trading_start->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    <!-- Стартовая цена -->
                    <div class="mb-6">
                        <label for="starting_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Стартовая цена (₽) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="starting_price" 
                               id="starting_price" 
                               step="0.01"
                               min="0"
                               required
                               value="{{ old('starting_price', $auction->starting_price) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('starting_price') border-red-500 @enderror">
                        @error('starting_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Шаг аукциона -->
                    <div class="mb-6">
                        <label for="step_percent" class="block text-sm font-medium text-gray-700 mb-2">
                            Шаг аукциона (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="step_percent" 
                               id="step_percent" 
                               step="0.1"
                               min="0.5"
                               max="5"
                               required
                               value="{{ old('step_percent', $auction->step_percent) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('step_percent') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Допустимый диапазон: 0.5% — 5%</p>
                        @error('step_percent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Техническое задание (замена) -->
                    <div class="mb-6">
                        <label for="technical_specification" class="block text-sm font-medium text-gray-700 mb-2">
                            Заменить техническое задание (PDF)
                        </label>
                        
                        @if($auction->hasMedia('technical_specification'))
                            <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Текущий файл</p>
                                        <p class="text-xs text-gray-500">{{ $auction->getFirstMedia('technical_specification')->file_name }}</p>
                                    </div>
                                </div>
                                <a href="{{ $auction->getFirstMediaUrl('technical_specification') }}" 
                                   target="_blank"
                                   class="text-sm text-indigo-600 hover:text-indigo-500">
                                    Просмотр
                                </a>
                            </div>
                        @endif
                        
                        <input type="file" 
                               name="technical_specification" 
                               id="technical_specification" 
                               accept="application/pdf"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 @error('technical_specification') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Оставьте пустым, если не хотите заменять файл. Максимальный размер: 10 МБ</p>
                        @error('technical_specification')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('auctions.show', $auction) }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
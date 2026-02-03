@extends('layouts.app')

@section('title', 'Редактировать Запрос котировок')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Редактировать Запрос котировок</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $rfq->number }} — {{ $rfq->title }}</p>
        </div>

        <!-- Форма -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('rfqs.update', $rfq) }}" enctype="multipart/form-data">
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
                               value="{{ old('title', $rfq->title) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('title') border-red-500 @enderror">
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
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('description') border-red-500 @enderror">{{ old('description', $rfq->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Дата окончания -->
                    <div class="mb-6">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата окончания приёма заявок <span class="text-red-500">*</span>
                        </label>
                        <x-datetime-input name="end_date"
                                          :value="old('end_date', $rfq->end_date->format('Y-m-d\TH:i'))"
                                          :required="true"
                                          :error="$errors->has('end_date')" />
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Текущее значение: {{ $rfq->end_date->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    <!-- Техническое задание (замена) -->
                    <div class="mb-6">
                        <label for="technical_specification" class="block text-sm font-medium text-gray-700 mb-2">
                            Заменить техническое задание (PDF)
                        </label>
                        
                        @if($rfq->hasMedia('technical_specification'))
                            <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Текущий файл</p>
                                        <p class="text-xs text-gray-500">{{ $rfq->getFirstMedia('technical_specification')->file_name }}</p>
                                    </div>
                                </div>
                                <a href="{{ $rfq->getFirstMediaUrl('technical_specification') }}" 
                                   target="_blank"
                                   class="text-sm text-emerald-600 hover:text-emerald-500">
                                    Просмотр
                                </a>
                            </div>
                        @endif
                        
                        <input type="file" 
                               name="technical_specification" 
                               id="technical_specification" 
                               accept="application/pdf"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('technical_specification') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Оставьте пустым, если не хотите заменять файл. Максимальный размер: 10 МБ</p>
                        @error('technical_specification')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('rfqs.show', $rfq) }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
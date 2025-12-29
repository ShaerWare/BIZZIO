<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Редактировать аукцион') }} {{ $auction->number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('auctions.update', $auction) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Номер аукциона (только для отображения) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Номер аукциона
                            </label>
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm font-medium text-gray-900">{{ $auction->number }}</p>
                            </div>
                        </div>

                        <!-- Название аукциона -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Название аукциона *
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   value="{{ old('title', $auction->title) }}"
                                   required
                                   maxlength="255"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $auction->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Даты -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Окончание приёма заявок *
                                </label>
                                <input type="datetime-local" 
                                       name="end_date" 
                                       id="end_date" 
                                       value="{{ old('end_date', $auction->end_date->format('Y-m-d\TH:i')) }}"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Время МСК+3</p>
                                @error('end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="trading_start" class="block text-sm font-medium text-gray-700 mb-2">
                                    Начало торгов *
                                </label>
                                <input type="datetime-local" 
                                       name="trading_start" 
                                       id="trading_start" 
                                       value="{{ old('trading_start', $auction->trading_start->format('Y-m-d\TH:i')) }}"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Время МСК+3</p>
                                @error('trading_start')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Начальная цена и шаг -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="starting_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Начальная (максимальная) цена, ₽ *
                                </label>
                                <input type="number" 
                                       name="starting_price" 
                                       id="starting_price" 
                                       value="{{ old('starting_price', $auction->starting_price) }}"
                                       step="0.01"
                                       min="1"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('starting_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="step_percent" class="block text-sm font-medium text-gray-700 mb-2">
                                    Шаг аукциона, % *
                                </label>
                                <input type="number" 
                                       name="step_percent" 
                                       id="step_percent" 
                                       value="{{ old('step_percent', $auction->step_percent) }}"
                                       step="0.01"
                                       min="0.5"
                                       max="5"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">От 0.5% до 5%</p>
                                @error('step_percent')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Текущее техническое задание -->
                        @if($auction->hasMedia('technical_specification'))
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-semibold text-gray-900 mb-2">Текущее техническое задание</h4>
                                <a href="{{ $auction->getFirstMediaUrl('technical_specification') }}" 
                                   target="_blank"
                                   class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Скачать PDF
                                </a>
                            </div>
                        @endif

                        <!-- Замена технического задания -->
                        <div class="mb-6">
                            <label for="technical_specification" class="block text-sm font-medium text-gray-700 mb-2">
                                Заменить техническое задание (PDF)
                            </label>
                            <input type="file" 
                                   name="technical_specification" 
                                   id="technical_specification" 
                                   accept=".pdf"
                                   class="w-full">
                            <p class="mt-1 text-xs text-gray-500">Максимальный размер: 10 МБ</p>
                            @error('technical_specification')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('auctions.show', $auction) }}" 
                               class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Отмена
                            </a>
                            
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
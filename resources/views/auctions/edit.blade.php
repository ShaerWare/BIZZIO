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
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('description') border-red-500 @enderror">{{ old('description', $auction->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Дата окончания приёма заявок -->
                    <div class="mb-6">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата окончания приёма заявок <span class="text-red-500">*</span>
                        </label>
                        <x-datetime-input name="end_date"
                                          :value="old('end_date', $auction->end_date->format('Y-m-d\TH:i'))"
                                          :required="true"
                                          :error="$errors->has('end_date')" />
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва). Текущее значение: {{ $auction->end_date->format('d.m.Y H:i') }}</p>
                    </div>

                    <!-- Дата начала торгов -->
                    <div class="mb-6">
                        <label for="trading_start" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата начала торгов <span class="text-red-500">*</span>
                        </label>
                        <x-datetime-input name="trading_start"
                                          :value="old('trading_start', $auction->trading_start->format('Y-m-d\TH:i'))"
                                          :required="true"
                                          :error="$errors->has('trading_start')" />
                        @error('trading_start')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва). Текущее значение: {{ $auction->trading_start->format('d.m.Y H:i') }}</p>
                    </div>

                    <!-- Валюта -->
                    <div class="mb-6">
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                            Валюта <span class="text-red-500">*</span>
                        </label>
                        <select name="currency"
                                id="currency"
                                required
                                onchange="updateCurrencyLabel()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('currency') border-red-500 @enderror">
                            @foreach(\App\Models\Auction::CURRENCIES as $code => $symbol)
                                <option value="{{ $code }}" {{ old('currency', $auction->currency) === $code ? 'selected' : '' }}>
                                    {{ $code }} ({{ $symbol }})
                                </option>
                            @endforeach
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Начальная максимальная цена -->
                    <div class="mb-6">
                        <label for="starting_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Начальная максимальная цена (<span id="currency-symbol">{{ $auction->currency_symbol }}</span>) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="starting_price"
                               id="starting_price"
                               step="0.01"
                               min="0"
                               required
                               value="{{ old('starting_price', $auction->starting_price) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('starting_price') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Участники смогут снижать цену на 0.5% — 5% от текущей</p>
                        @error('starting_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- #185 Конкурсная документация: Извещение / ТЗ / Проект договора / Прочие файлы --}}
                    <p class="text-sm text-gray-500 mb-2">Загрузите новый файл, чтобы заменить текущий; пустое поле оставляет файл без изменений.</p>
                    @include('partials.procurement-documents', ['model' => $auction, 'tzRequired' => false])

                    <!-- Скрытие результатов после завершения -->
                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="is_results_hidden"
                                   value="1"
                                   {{ old('is_results_hidden', $auction->is_results_hidden) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Скрыть результаты после завершения
                                <span class="text-gray-500">(видны только организатору и участникам)</span>
                            </span>
                        </label>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('auctions.show', $auction) }}" 
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

@push('scripts')
<script>
    // A12: Авто-заполнение «Начало торгов» = «Окончание приёма» + 1 мин
    document.addEventListener('DOMContentLoaded', function() {
        const endDate = document.getElementById('end_date');
        const tradingStart = document.getElementById('trading_start');

        if (endDate && tradingStart) {
            endDate.addEventListener('change', function() {
                if (!this.value || tradingStart.dataset.userEdited) return;
                const dt = new Date(this.value);
                dt.setMinutes(dt.getMinutes() + 1);
                const pad = n => String(n).padStart(2, '0');
                tradingStart.value = dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
            });
            tradingStart.addEventListener('input', function() {
                this.dataset.userEdited = 'true';
            });
        }
    });

    // Обновление символа валюты в label цены
    const currencySymbols = {!! json_encode(\App\Models\Auction::CURRENCIES) !!};
    function updateCurrencyLabel() {
        const sel = document.getElementById('currency');
        const span = document.getElementById('currency-symbol');
        if (sel && span) span.textContent = currencySymbols[sel.value] || '₽';
    }
</script>
@endpush
@endsection
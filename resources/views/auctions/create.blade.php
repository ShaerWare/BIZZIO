@extends('layouts.app')

@section('title', 'Разместить аукцион')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Разместить аукцион</h1>
            <p class="mt-2 text-sm text-gray-600">Заполните форму для создания обратного аукциона</p>
        </div>

        <!-- Форма -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('auctions.store') }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Компания-организатор -->
                    <div class="mb-6">
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Компания-организатор <span class="text-red-500">*</span>
                        </label>
                        <select name="company_id" 
                                id="company_id" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('company_id') border-red-500 @enderror">
                            <option value="">Выберите компанию...</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Название -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Название аукциона <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               required
                               value="{{ old('title') }}"
                               placeholder="Например: Поставка офисной мебели"
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
                                  placeholder="Подробное описание предмета аукциона, требований и условий..."
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Тип процедуры -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Тип процедуры <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="open" 
                                       {{ old('type', 'open') === 'open' ? 'checked' : '' }}
                                       onchange="toggleInvitations()"
                                       class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Открытая процедура 
                                    <span class="text-gray-500">(любая компания может подать заявку)</span>
                                </span>
                            </label>
                            <br>
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="closed" 
                                       {{ old('type') === 'closed' ? 'checked' : '' }}
                                       onchange="toggleInvitations()"
                                       class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Закрытая процедура 
                                    <span class="text-gray-500">(только приглашённые компании)</span>
                                </span>
                            </label>
                        </div>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Приглашение компаний (для закрытой процедуры) -->
                    <div id="invitations-block" class="mb-6 hidden">
                        <label for="invited_companies" class="block text-sm font-medium text-gray-700 mb-2">
                            Пригласить компании
                        </label>
                        <select name="invited_companies[]" 
                                id="invited_companies" 
                                multiple
                                size="10"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($allCompanies as $company)
                                <option value="{{ $company->id }}" {{ in_array($company->id, old('invited_companies', [])) ? 'selected' : '' }}>
                                    {{ $company->name }} (ИНН: {{ $company->inn }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Удерживайте Ctrl (Cmd на Mac) для выбора нескольких компаний</p>
                        @error('invited_companies')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Даты -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Дата начала приёма заявок -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Начало приёма заявок <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local"
                                   name="start_date"
                                   id="start_date"
                                   required
                                   value="{{ old('start_date', now()->addDay()->format('Y-m-d\TH:i')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-500 @enderror">
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва)</p>
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Дата окончания приёма заявок -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Окончание приёма заявок <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local"
                                   name="end_date"
                                   id="end_date"
                                   required
                                   value="{{ old('end_date', now()->addWeek()->format('Y-m-d\TH:i')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror">
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва)</p>
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Дата начала торгов -->
                    <div class="mb-6">
                        <label for="trading_start" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата и время начала торгов <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               name="trading_start" 
                               id="trading_start" 
                               required
                               value="{{ old('trading_start', now()->addWeek()->addDay()->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('trading_start') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Время указывается по UTC +3 (Москва)</p>
                        @error('trading_start')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Начальная максимальная цена -->
                    <div class="mb-6">
                        <label for="starting_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Начальная максимальная цена (₽) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="starting_price"
                               id="starting_price"
                               step="0.01"
                               min="0"
                               required
                               value="{{ old('starting_price') }}"
                               placeholder="Введите начальную максимальную цену"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('starting_price') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Участники смогут снижать цену на 0.5% — 5% от текущей</p>
                        @error('starting_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Техническое задание - F3: с сохранением при ошибке валидации -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Техническое задание (PDF) <span class="text-red-500">*</span>
                        </label>
                        <x-file-upload
                            name="technical_specification"
                            collection="technical_specification"
                            accept="application/pdf"
                            :required="true"
                            hint="Максимальный размер: 20 МБ. Файл сохраняется при ошибке валидации."
                        />
                        @error('technical_specification')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Статус -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Статус публикации <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="status" 
                                       value="draft" 
                                       {{ old('status', 'draft') === 'draft' ? 'checked' : '' }}
                                       onchange="toggleStatusWarning()"
                                       class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Сохранить как черновик
                                    <span class="text-gray-500">(можно будет отредактировать позже)</span>
                                </span>
                            </label>
                            <br>
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="status" 
                                       value="active" 
                                       {{ old('status') === 'active' ? 'checked' : '' }}
                                       onchange="toggleStatusWarning()"
                                       class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Опубликовать сразу
                                    <span class="text-gray-500">(начнётся приём заявок)</span>
                                </span>
                            </label>
                        </div>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Предупреждение при выборе "Опубликовать сразу" -->
                    <div id="status-warning" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 hidden">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    При публикации аукцион сразу станет активным, и начнётся приём заявок. 
                                    Убедитесь, что все данные введены корректно.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Уведомление -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                        <div class="flex items-start">
                            <input type="checkbox" 
                                   name="notification_agreement" 
                                   id="notification_agreement"
                                   required
                                   class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <label for="notification_agreement" class="ml-3 text-sm text-gray-700">
                                Я уведомлён, что процедура проведения Аукциона не является торгами и не обязывает к заключению договора. 
                                Результаты подведения итогов носят информационный характер.
                            </label>
                        </div>
                        @error('notification_agreement')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('auctions.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Создать аукцион
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Показ/скрытие блока приглашений
    function toggleInvitations() {
        const type = document.querySelector('input[name="type"]:checked').value;
        const invitationsBlock = document.getElementById('invitations-block');
        
        if (type === 'closed') {
            invitationsBlock.classList.remove('hidden');
        } else {
            invitationsBlock.classList.add('hidden');
        }
    }

    // Показ/скрытие предупреждения о статусе
    function toggleStatusWarning() {
        const status = document.querySelector('input[name="status"]:checked').value;
        const warning = document.getElementById('status-warning');
        
        if (status === 'active') {
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    }

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        toggleInvitations();
        toggleStatusWarning();
    });
</script>
@endpush
@endsection
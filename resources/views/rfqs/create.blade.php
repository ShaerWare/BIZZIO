@extends('layouts.app')

@section('title', 'Разместить Запрос котировок')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Разместить Запрос котировок</h1>
            <p class="mt-2 text-sm text-gray-600">Заполните форму для размещения запроса котировок</p>
        </div>

        <!-- Форма -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('rfqs.store') }}" enctype="multipart/form-data">
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
                            <option value="">Выберите компанию</option>
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
                            Название <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               required
                               value="{{ old('title') }}"
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
                                       class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    <strong>Открытая</strong> — любая компания может подать заявку
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="closed"
                                       {{ old('type') === 'closed' ? 'checked' : '' }}
                                       class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    <strong>Закрытая</strong> — только приглашённые компании
                                </span>
                            </label>
                        </div>
                    </div>
                    <!-- Статус -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Статус <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="radio" 
                                    name="status" 
                                    value="draft" 
                                    {{ old('status', 'draft') === 'draft' ? 'checked' : '' }}
                                    class="mt-1 rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="ml-3">
                                    <span class="text-sm font-semibold text-gray-900">Черновик</span>
                                    <p class="text-xs text-gray-500 mt-1">
                                        RFQ будет сохранён, но не опубликован. Можно будет редактировать и активировать позже.
                                    </p>
                                </div>
                            </label>
                            <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="radio" 
                                    name="status" 
                                    value="active"
                                    {{ old('status') === 'active' ? 'checked' : '' }}
                                    class="mt-1 rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="ml-3">
                                    <span class="text-sm font-semibold text-gray-900">Активный (опубликовать сразу)</span>
                                    <p class="text-xs text-gray-500 mt-1">
                                        RFQ будет сразу опубликован, приём заявок начнётся автоматически. После активации редактирование будет ограничено.
                                    </p>
                                </div>
                            </label>
                        </div>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Приглашения (для закрытых процедур) -->
                    <div id="invitations-block" class="mb-6" style="display: {{ old('type') === 'closed' ? 'block' : 'none' }};">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Пригласить компании
                        </label>
                        <select name="invited_companies[]" 
                                multiple 
                                size="8"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($availableCompanies as $company)
                                <option value="{{ $company->id }}" 
                                        {{ in_array($company->id, old('invited_companies', [])) ? 'selected' : '' }}>
                                    {{ $company->name }} (ИНН: {{ $company->inn }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Удерживайте Ctrl (Cmd на Mac) для выбора нескольких компаний</p>
                    </div>

                    <!-- Даты -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Дата начала приёма заявок <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local"
                                   name="start_date"
                                   id="start_date"
                                   required
                                   value="{{ old('start_date') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-500 @enderror">
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва)</p>
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Дата окончания приёма заявок <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local"
                                   name="end_date"
                                   id="end_date"
                                   required
                                   value="{{ old('end_date') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror">
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва)</p>
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Критерии оценки -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Критерии оценки (веса в %)</h3>
                        <p class="text-sm text-gray-600 mb-4">Сумма весов должна быть равна 100%</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="weight_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Цена <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="weight_price" 
                                       id="weight_price" 
                                       required
                                       value="{{ old('weight_price', 50) }}"
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('weight_price') border-red-500 @enderror">
                                @error('weight_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="weight_deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                    Срок выполнения <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="weight_deadline" 
                                       id="weight_deadline" 
                                       required
                                       value="{{ old('weight_deadline', 30) }}"
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('weight_deadline') border-red-500 @enderror">
                                @error('weight_deadline')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="weight_advance" class="block text-sm font-medium text-gray-700 mb-2">
                                    Размер аванса <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="weight_advance" 
                                       id="weight_advance" 
                                       required
                                       value="{{ old('weight_advance', 20) }}"
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('weight_advance') border-red-500 @enderror">
                                @error('weight_advance')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @error('weights')
                            <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded p-3">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Техническое задание (PDF) - F3: с сохранением при ошибке валидации -->
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

                    <!-- Уведомление -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <label class="flex items-start">
                            <input type="checkbox" 
                                   id="agreement" 
                                   required
                                   class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Я уведомлён, что процедура проведения Запроса котировок не является публичной офертой и не обязывает к заключению договора. Организатор вправе отменить процедуру на любом этапе без объяснения причин.
                            </span>
                        </label>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('rfqs.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Разместить RFQ
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
    document.querySelectorAll('input[name="type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const invitationsBlock = document.getElementById('invitations-block');
            invitationsBlock.style.display = this.value === 'closed' ? 'block' : 'none';
        });
    });

    // Валидация суммы весов
    const weightInputs = ['weight_price', 'weight_deadline', 'weight_advance'];
    weightInputs.forEach(id => {
        document.getElementById(id).addEventListener('input', function() {
            const sum = weightInputs.reduce((acc, id) => {
                return acc + (parseFloat(document.getElementById(id).value) || 0);
            }, 0);
            
            // Подсветка, если сумма != 100
            weightInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (Math.abs(sum - 100) > 0.01) {
                    input.classList.add('border-red-500');
                    input.classList.remove('border-gray-300');
                } else {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                }
            });
        });
    });
</script>
@endpush
@endsection
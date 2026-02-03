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
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('company_id') border-red-500 @enderror">
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
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
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
                                       class="rounded-full border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    <strong>Открытая</strong> — любая компания может подать заявку
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="type" 
                                       value="closed"
                                       {{ old('type') === 'closed' ? 'checked' : '' }}
                                       class="rounded-full border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    <strong>Закрытая</strong> — только приглашённые компании
                                </span>
                            </label>
                        </div>
                    </div>
                    <!-- Валюта -->
                    <div class="mb-6">
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                            Валюта <span class="text-red-500">*</span>
                        </label>
                        <select name="currency"
                                id="currency"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('currency') border-red-500 @enderror">
                            @foreach(\App\Models\Rfq::CURRENCIES as $code => $symbol)
                                <option value="{{ $code }}" {{ old('currency', 'RUB') === $code ? 'selected' : '' }}>
                                    {{ $code }} ({{ $symbol }})
                                </option>
                            @endforeach
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                                    class="mt-1 rounded-full border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
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
                                    class="mt-1 rounded-full border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
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

                    <!-- T8: Приглашения (для любого типа процедуры) -->
                    <div class="mb-6" x-data="companySearch()">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Пригласить компании
                        </label>
                        <p class="text-sm text-gray-500 mb-2" id="invitations-hint">
                            <template x-if="procedureType === 'closed'">
                                <span>Только приглашённые компании смогут подать заявку</span>
                            </template>
                            <template x-if="procedureType === 'open'">
                                <span>Эти компании получат уведомление о вашем RFQ</span>
                            </template>
                        </p>

                        <!-- Поиск -->
                        <div class="relative">
                            <input type="text"
                                   x-model="query"
                                   @input.debounce.300ms="search()"
                                   @click.away="showResults = false"
                                   @focus="if (results.length) showResults = true"
                                   placeholder="Поиск по названию или ИНН..."
                                   class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">

                            <!-- Результаты поиска -->
                            <div x-show="showResults && results.length > 0" x-cloak
                                 class="absolute z-10 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto">
                                <template x-for="item in results" :key="item.id">
                                    <button type="button"
                                            @click="addCompany(item)"
                                            class="w-full text-left px-3 py-2 hover:bg-emerald-50 text-sm border-b border-gray-100 last:border-0 flex justify-between items-center">
                                        <div>
                                            <span class="font-medium text-gray-900" x-text="item.title"></span>
                                            <span class="text-gray-500 text-xs ml-1" x-text="item.subtitle ? '(' + item.subtitle + ')' : ''"></span>
                                        </div>
                                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>

                            <!-- Нет результатов -->
                            <div x-show="showResults && results.length === 0 && query.length >= 2 && !loading" x-cloak
                                 class="absolute z-10 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200 p-3 text-sm text-gray-500">
                                Компании не найдены
                            </div>

                            <!-- Загрузка -->
                            <div x-show="loading" x-cloak
                                 class="absolute z-10 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200 p-3 text-sm text-gray-500">
                                Поиск...
                            </div>
                        </div>

                        <!-- Выбранные компании -->
                        <div x-show="selected.length > 0" class="mt-3 space-y-2">
                            <template x-for="(company, index) in selected" :key="company.id">
                                <div class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2 text-sm">
                                    <input type="hidden" name="invited_companies[]" :value="company.id">
                                    <div>
                                        <span class="font-medium text-gray-900" x-text="company.name"></span>
                                        <span class="text-gray-500 text-xs ml-1" x-text="company.inn ? '(ИНН: ' + company.inn + ')' : ''"></span>
                                    </div>
                                    <button type="button" @click="removeCompany(index)"
                                            class="text-red-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Даты -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Дата начала приёма заявок <span class="text-red-500">*</span>
                            </label>
                            <x-datetime-input name="start_date"
                                              :value="old('start_date', '')"
                                              :required="true"
                                              :error="$errors->has('start_date')" />
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва)</p>
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Дата окончания приёма заявок <span class="text-red-500">*</span>
                            </label>
                            <x-datetime-input name="end_date"
                                              :value="old('end_date', '')"
                                              :required="true"
                                              :error="$errors->has('end_date')" />
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
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('weight_price') border-red-500 @enderror">
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
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('weight_deadline') border-red-500 @enderror">
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
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('weight_advance') border-red-500 @enderror">
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
                                   class="mt-1 rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
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
    // T8: Alpine.js компонент поиска компаний для приглашений
    function companySearch() {
        return {
            query: '',
            results: [],
            selected: [],
            showResults: false,
            loading: false,
            procedureType: '{{ old('type', 'open') }}',

            init() {
                // Восстановление выбранных компаний из old()
                @if(old('invited_companies'))
                    @foreach(old('invited_companies') as $companyId)
                        @php $c = \App\Models\Company::find($companyId); @endphp
                        @if($c)
                            this.selected.push({ id: {{ $c->id }}, name: '{{ addslashes($c->name) }}', inn: '{{ $c->inn }}' });
                        @endif
                    @endforeach
                @endif

                // Следим за переключением типа процедуры
                document.querySelectorAll('input[name="type"]').forEach(radio => {
                    radio.addEventListener('change', (e) => {
                        this.procedureType = e.target.value;
                    });
                });
            },

            async search() {
                if (this.query.length < 2) {
                    this.results = [];
                    this.showResults = false;
                    return;
                }

                this.loading = true;
                this.showResults = true;

                try {
                    const response = await fetch(`/search/quick?q=${encodeURIComponent(this.query)}`);
                    const data = await response.json();

                    // Фильтруем: только компании, исключая уже выбранные и компанию-организатора
                    const organizerId = document.getElementById('company_id').value;
                    const selectedIds = this.selected.map(s => s.id);

                    this.results = data
                        .filter(item => item.type === 'company')
                        .filter(item => !selectedIds.includes(item.id))
                        .filter(item => String(item.id) !== String(organizerId));
                } catch (e) {
                    this.results = [];
                } finally {
                    this.loading = false;
                }
            },

            addCompany(item) {
                this.selected.push({
                    id: item.id,
                    name: item.title,
                    inn: item.subtitle || ''
                });
                this.query = '';
                this.results = [];
                this.showResults = false;
            },

            removeCompany(index) {
                this.selected.splice(index, 1);
            }
        };
    }

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
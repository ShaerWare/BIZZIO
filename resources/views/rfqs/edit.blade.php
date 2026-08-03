@extends('layouts.app')

@php
    // #216 Редактировать можно только черновик (RfqPolicy::update) — доступен весь набор полей.
    $isCommercial = $rfq->isCommercial();
@endphp

@section('title', $isCommercial ? 'Редактировать коммерческий аукцион' : 'Редактировать Запрос цен')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">{{ $isCommercial ? 'Редактировать коммерческий аукцион' : 'Редактировать Запрос цен' }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $rfq->number }} — {{ $rfq->title }}</p>
        </div>

        <!-- Форма -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('rfqs.update', $rfq) }}" enctype="multipart/form-data" id="rfq-edit-form">
                    @csrf
                    @method('PUT')

                    <!-- Компания-организатор (не меняется после создания) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Компания-организатор</label>
                        <input type="text" value="{{ $rfq->company->name }}" disabled
                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm">
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

                    <!-- Тип процедуры -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Тип процедуры <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="type" value="open"
                                       {{ old('type', $rfq->type) === 'open' ? 'checked' : '' }}
                                       class="rounded-full border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    <strong>Открытая</strong> — любая компания может подать заявку
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="type" value="closed"
                                       {{ old('type', $rfq->type) === 'closed' ? 'checked' : '' }}
                                       class="rounded-full border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    <strong>Закрытая</strong> — только приглашённые компании
                                </span>
                            </label>
                        </div>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Валюта -->
                    <div class="mb-6">
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                            Валюта <span class="text-red-500">*</span>
                        </label>
                        <select name="currency" id="currency" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('currency') border-red-500 @enderror">
                            @foreach(\App\Models\Rfq::CURRENCIES as $code => $symbol)
                                <option value="{{ $code }}" {{ old('currency', $rfq->currency) === $code ? 'selected' : '' }}>
                                    {{ $code }} ({{ $symbol }})
                                </option>
                            @endforeach
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Даты -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $isCommercial ? 'Дата начала приёма предложений' : 'Дата начала приёма заявок' }} <span class="text-red-500">*</span>
                            </label>
                            <x-datetime-input name="start_date"
                                              :value="old('start_date', $rfq->start_date?->format('Y-m-d\TH:i'))"
                                              :required="true"
                                              :error="$errors->has('start_date')" />
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва)</p>
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $isCommercial ? 'Дата окончания приёма предложений' : 'Дата окончания приёма заявок' }} <span class="text-red-500">*</span>
                            </label>
                            <x-datetime-input name="end_date"
                                              :value="old('end_date', $rfq->end_date->format('Y-m-d\TH:i'))"
                                              :required="true"
                                              :error="$errors->has('end_date')" />
                            <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва). Текущее значение: {{ $rfq->end_date->format('d.m.Y H:i') }}</p>
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @if($isCommercial)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Начало торгов (этап 2)</label>
                                <x-datetime-input name="trading_start"
                                                  :value="old('trading_start', $rfq->trading_start?->format('Y-m-d\TH:i'))"
                                                  :error="$errors->has('trading_start')" />
                                <p class="mt-1 text-xs text-gray-500">Должно быть позже окончания приёма предложений. UTC +3</p>
                                @error('trading_start')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Критерии оценки -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Критерии оценки (веса в %)</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Сумма весов должна быть равна 100%{{ $isCommercial ? ' — применяются на этапе 2 (коммерческий аукцион)' : '' }}
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="weight_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Цена <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="weight_price" id="weight_price" required
                                       value="{{ old('weight_price', (int) $rfq->weight_price) }}"
                                       min="0" max="100" step="1"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('weight_price') border-red-500 @enderror">
                                @error('weight_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="weight_deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                    Срок выполнения <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="weight_deadline" id="weight_deadline" required
                                       value="{{ old('weight_deadline', (int) $rfq->weight_deadline) }}"
                                       min="0" max="100" step="1"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('weight_deadline') border-red-500 @enderror">
                                @error('weight_deadline')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="weight_advance" class="block text-sm font-medium text-gray-700 mb-2">
                                    Размер аванса <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="weight_advance" id="weight_advance" required
                                       value="{{ old('weight_advance', (int) $rfq->weight_advance) }}"
                                       min="0" max="100" step="1"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('weight_advance') border-red-500 @enderror">
                                @error('weight_advance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        @error('weights')
                            <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded p-3">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    @if($isCommercial)
                        <!-- #179/#210 Параметры этапа 2 -->
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Параметры коммерческого аукциона (Этап 2)</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Торги продолжаются, пока не пройдёт 20 минут с момента подачи последнего предложения.
                            </p>

                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Шаги изменения критериев</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Шаг цены (%)</label>
                                    <input type="number" name="step_price" value="{{ old('step_price', $rfq->step_price) }}"
                                           min="0.01" max="100" step="0.01"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('step_price') border-red-500 @enderror">
                                    @error('step_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Шаг срока (дни)</label>
                                    <input type="number" name="step_deadline" value="{{ old('step_deadline', $rfq->step_deadline) }}"
                                           min="1" step="1"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('step_deadline') border-red-500 @enderror">
                                    @error('step_deadline')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Шаг аванса (%)</label>
                                    <input type="number" name="step_advance" value="{{ old('step_advance', $rfq->step_advance) }}"
                                           min="0.01" max="100" step="0.01"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('step_advance') border-red-500 @enderror">
                                    @error('step_advance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Максимальные значения критериев (этап 2)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Макс. срок выполнения (дни)</label>
                                    <input type="number" name="max_deadline" value="{{ old('max_deadline', $rfq->max_deadline) }}"
                                           min="1" step="1"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('max_deadline') border-red-500 @enderror">
                                    @error('max_deadline')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Макс. размер аванса (%)</label>
                                    <input type="number" name="max_advance" value="{{ old('max_advance', $rfq->max_advance) }}"
                                           min="0.01" max="100" step="0.01"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('max_advance') border-red-500 @enderror">
                                    @error('max_advance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- #185 Конкурсная документация: Извещение / ТЗ / Проект договора / Прочие файлы --}}
                    <p class="text-sm text-gray-500 mb-2">Загрузите новый файл, чтобы заменить текущий; пустое поле оставляет файл без изменений.</p>
                    @include('partials.procurement-documents', ['model' => $rfq, 'tzRequired' => false])

                    <!-- Скрытие результатов после завершения -->
                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="is_results_hidden"
                                   value="1"
                                   {{ old('is_results_hidden', $rfq->is_results_hidden) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Скрыть результаты после завершения
                                <span class="text-gray-500">(видны только организатору и участникам)</span>
                            </span>
                        </label>
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

        {{-- #90: Управление приглашениями компаний (AJAX, вне основной формы) --}}
        @if($rfq->status === 'draft')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6" x-data="editCompanyInviter()">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Приглашённые компании</h2>

                    {{-- Поиск компаний --}}
                    <div class="relative mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пригласить компанию</label>
                        <input type="text"
                               x-model="query"
                               @input.debounce.300ms="search()"
                               @click.away="showResults = false"
                               @focus="if (results.length) showResults = true"
                               placeholder="Поиск по названию или ИНН..."
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">

                        <div x-show="showResults && results.length > 0" x-cloak
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="company in results" :key="company.id">
                                <button type="button"
                                        @click="invite(company)"
                                        class="w-full text-left px-3 py-2 hover:bg-emerald-50 border-b border-gray-100 last:border-0">
                                    <p class="text-sm font-medium text-gray-900" x-text="company.title"></p>
                                    <p class="text-xs text-gray-500" x-text="company.subtitle"></p>
                                </button>
                            </template>
                        </div>

                        <div x-show="searching" x-cloak class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg p-3 text-center text-xs text-gray-500">
                            Поиск...
                        </div>
                    </div>

                    <p x-show="message" x-cloak x-text="message" class="text-sm mb-3"
                       :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'"></p>

                    {{-- Список приглашённых --}}
                    @if($rfq->invitations->count() > 0)
                        <div class="space-y-2">
                            <p class="text-sm text-gray-500 font-medium">Приглашённые ({{ $rfq->invitations->count() }}):</p>
                            @foreach($rfq->invitations as $inv)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs text-gray-500 font-semibold">
                                                {{ strtoupper(substr($inv->company->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $inv->company->name }}</span>
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $inv->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ($inv->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                                {{ $inv->status === 'pending' ? 'Ожидает' : ($inv->status === 'accepted' ? 'Принято' : 'Отклонено') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Приглашений нет. Используйте поиск выше для приглашения компаний.</p>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    function editCompanyInviter() {
        const rfqCompanyId = {{ $rfq->company_id }};
        const existingInvitationIds = @json($rfq->invitations->pluck('company_id'));

        return {
            query: '',
            results: [],
            showResults: false,
            searching: false,
            message: '',
            messageType: 'success',

            async search() {
                if (this.query.length < 2) {
                    this.results = [];
                    this.showResults = false;
                    return;
                }
                this.searching = true;
                this.message = '';
                try {
                    const res = await fetch(`{{ route('search.quick') }}?q=${encodeURIComponent(this.query)}`);
                    const data = await res.json();
                    this.results = data
                        .filter(r => r.type === 'company')
                        .filter(r => r.id !== rfqCompanyId)
                        .filter(r => !existingInvitationIds.includes(r.id));
                    this.showResults = true;
                } catch (e) {
                    this.results = [];
                } finally {
                    this.searching = false;
                }
            },

            async invite(company) {
                this.showResults = false;
                this.query = '';
                this.message = '';
                try {
                    const res = await fetch(`{{ route('rfqs.invitations.store', $rfq) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ company_id: company.id }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.message = `${company.title} — приглашение отправлено`;
                        this.messageType = 'success';
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.message = data.error || 'Ошибка при отправке';
                        this.messageType = 'error';
                    }
                } catch (e) {
                    this.message = 'Ошибка сети';
                    this.messageType = 'error';
                }
            }
        };
    }

    // #216 Подсветка суммы весов ≠ 100% (как в форме создания)
    (function () {
        const weightInputs = ['weight_price', 'weight_deadline', 'weight_advance'];
        weightInputs.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () {
                const sum = weightInputs.reduce((acc, i) => {
                    const input = document.getElementById(i);
                    return acc + (input ? parseFloat(input.value) || 0 : 0);
                }, 0);

                weightInputs.forEach(inputId => {
                    const input = document.getElementById(inputId);
                    if (!input) return;
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
    })();
</script>
@endpush
@endsection

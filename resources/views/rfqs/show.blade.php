@extends('layouts.app')

@section('title', 'Запрос котировок № ' . $rfq->number)

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок с кнопками управления -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $rfq->title }}</h1>
                <p class="mt-1 text-sm text-gray-500">Запрос котировок № {{ $rfq->number }}</p>
            </div>
            
            @can('update', $rfq)
                <div class="flex space-x-2">
                    <!-- Кнопка активации (только для черновиков) -->
                    @if($rfq->status === 'draft')
                        <form method="POST" action="{{ route('rfqs.activate', $rfq) }}"
                            onsubmit="return confirm('Вы уверены, что хотите активировать RFQ? После активации редактирование будет ограничено.');">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Активировать RFQ
                            </button>
                        </form>
                    @endif

                    <!-- Кнопка редактирования (только для черновиков) -->
                    @if($rfq->status === 'draft')
                        <a href="{{ route('rfqs.edit', $rfq) }}" 
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Редактировать
                        </a>
                    @endif
                    
                    <!-- Кнопка удаления (только для черновиков) -->
                    @can('delete', $rfq)
                        @if($rfq->status === 'draft')
                            <form method="POST" action="{{ route('rfqs.destroy', $rfq) }}" 
                                onsubmit="return confirm('Вы уверены, что хотите удалить этот запрос котировок?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Удалить
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            @endcan
        </div>

        <!-- Главная информация -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Основная информация -->
                    <div class="flex-1">
                        <!-- Статус -->
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                'draft' => 'bg-yellow-100 text-yellow-800',
                            ];
                            $statusLabels = [
                                'active' => 'Активный',
                                'closed' => 'Завершён',
                                'cancelled' => 'Отменён',
                                'draft' => 'Черновик',
                            ];
                        @endphp
                        <div class="flex items-center space-x-2 mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$rfq->status] }}">
                                {{ $statusLabels[$rfq->status] }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $rfq->type === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $rfq->type === 'open' ? 'Открытая процедура' : 'Закрытая процедура' }}
                            </span>
                        </div>

                        <!-- Компания-организатор -->
                        <div class="flex items-center mb-4">
                            @if($rfq->company->logo)
                                <img src="{{ asset('storage/' . $rfq->company->logo) }}" 
                                     alt="{{ $rfq->company->name }}" 
                                     class="w-12 h-12 rounded-full mr-3 object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                    <span class="text-sm text-gray-500 font-semibold">
                                        {{ strtoupper(substr($rfq->company->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500">Организатор</p>
                                <a href="{{ route('companies.show', $rfq->company) }}" 
                                   class="text-base font-semibold text-emerald-600 hover:text-emerald-500">
                                    {{ $rfq->company->name }}
                                </a>
                            </div>
                        </div>

                        <!-- Сроки -->
                        <div class="flex items-center text-gray-600 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><strong>Начало:</strong> {{ $rfq->start_date->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex items-center text-gray-600 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><strong>Окончание:</strong> {{ $rfq->end_date->format('d.m.Y H:i') }}</span>
                        </div>

                        <!-- Создатель -->
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Создатель: <strong>{{ $rfq->creator->name }}</strong></span>
                        </div>
                    </div>

                    <!-- Боковая панель -->
                    <div class="w-full md:w-80">
                        <!-- Критерии оценки -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Критерии оценки:</h3>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• Цена — <strong>{{ $rfq->weight_price }}%</strong></li>
                                <li>• Срок выполнения — <strong>{{ $rfq->weight_deadline }}%</strong></li>
                                <li>• Размер аванса — <strong>{{ $rfq->weight_advance }}%</strong></li>
                            </ul>

                            {{-- T5: Формула расчёта балла --}}
                            <details class="mt-3">
                                <summary class="text-xs text-emerald-600 cursor-pointer hover:text-emerald-800">
                                    Как рассчитывается итоговый балл?
                                </summary>
                                <div class="mt-2 p-3 bg-white rounded border border-gray-200 text-xs text-gray-600">
                                    <p class="mb-2"><strong>Формула расчёта:</strong></p>
                                    <p class="mb-1">• Балл за цену = 100 × (мин. цена / ваша цена)</p>
                                    <p class="mb-1">• Балл за срок = 100 × (мин. срок / ваш срок)</p>
                                    <p class="mb-1">• Балл за аванс = 100 − (ваш аванс / макс. аванс) × 100</p>
                                    <p class="mt-2 font-medium">Итоговый балл = (Б<sub>цена</sub> × {{ $rfq->weight_price }}% + Б<sub>срок</sub> × {{ $rfq->weight_deadline }}% + Б<sub>аванс</sub> × {{ $rfq->weight_advance }}%) / 100</p>
                                    <p class="mt-2 text-gray-500">Чем выше итоговый балл — тем лучше заявка. Побеждает заявка с максимальным баллом.</p>
                                </div>
                            </details>
                        </div>

                        <!-- Техническое задание -->
                        @if($rfq->hasMedia('technical_specification'))
                            <a href="{{ $rfq->getFirstMediaUrl('technical_specification') }}" 
                               target="_blank"
                               class="block w-full text-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition mb-4">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Скачать ТЗ (PDF)
                            </a>
                        @endif

                        {{-- T1: Кнопка копирования ссылки --}}
                        @can('update', $rfq)
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2">Поделиться RFQ</h3>
                                <div class="flex items-center space-x-2">
                                    <input type="text" readonly
                                           id="rfq-link"
                                           value="{{ route('rfqs.show', $rfq) }}"
                                           class="flex-1 text-xs rounded border-gray-300 bg-white focus:ring-emerald-500">
                                    <button type="button" onclick="copyRfqLink()"
                                            class="px-3 py-2 bg-emerald-600 text-white text-xs rounded hover:bg-emerald-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p id="copy-success" class="text-xs text-green-600 mt-1 hidden">Ссылка скопирована!</p>
                            </div>
                        @endcan

                        {{-- T3: Кнопка «Подать заявку» с прокруткой к форме --}}
                        @auth
                            @if($canBid && $rfq->isActive() && !$rfq->isExpired())
                                <a href="#bid-form"
                                   onclick="document.getElementById('bid-form').scrollIntoView({behavior: 'smooth'}); return false;"
                                   class="block w-full text-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition mb-4">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Подать заявку
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        <!-- Предупреждение для черновиков -->
        @if($rfq->status === 'draft')
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Это черновик
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Этот RFQ находится в режиме черновика. Для начала приёма заявок активируйте его, нажав кнопку "Активировать RFQ".</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Вкладки -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <!-- Навигация по вкладкам -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="showTab('description')" 
                            id="tab-description"
                            class="tab-button active border-emerald-500 text-emerald-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Описание
                    </button>
                    <button onclick="showTab('bids')" 
                            id="tab-bids"
                            class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Заявки ({{ $rfq->bids->count() }})
                    </button>
                    @if($rfq->type === 'closed')
                        <button onclick="showTab('invitations')" 
                                id="tab-invitations"
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Приглашения ({{ $rfq->invitations->count() }})
                        </button>
                    @endif
                </nav>
            </div>

            <!-- Контент вкладок -->
            <div class="p-6">
                <!-- Вкладка: Описание -->
                <div id="content-description" class="tab-content">
                    @if($rfq->description)
                        <div class="prose max-w-none">
                            <div class="text-gray-700">{!! nl2br(e($rfq->description)) !!}</div>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">Описание не добавлено</p>
                    @endif
                </div>

                <!-- Вкладка: Заявки -->
                <div id="content-bids" class="tab-content hidden">
                    @if($canBid)
                        <!-- Форма подачи заявки -->
@auth
    @if($canBid && $rfq->isActive() && !$rfq->isExpired())
        <div id="bid-form" class="bg-green-50 border-2 border-green-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Подать заявку
            </h3>

            <form method="POST" action="{{ route('rfqs.bids.store', $rfq) }}">
                @csrf

                <!-- Выбор компании -->
                @if($availableCompanies->count() > 1)
                    <div class="mb-4">
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Компания <span class="text-red-500">*</span>
                        </label>
                        <select name="company_id" 
                                id="company_id" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">Выберите компанию...</option>
                            @foreach($availableCompanies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="company_id" value="{{ $availableCompanies->first()->id }}">
                    <div class="mb-4 p-3 bg-white rounded border border-gray-200">
                        <p class="text-sm text-gray-600">
                            Заявка от компании: <strong>{{ $availableCompanies->first()->name }}</strong>
                        </p>
                    </div>
                @endif

                <!-- Цена -->
                <div class="mb-4">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Цена ({{ $rfq->currency_symbol }} без НДС) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="price" 
                           id="price" 
                           step="0.01"
                           min="0"
                           required
                           placeholder="Введите цену"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <!-- Срок выполнения -->
                <div class="mb-4">
                    <label for="delivery_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Срок выполнения (календарных дней) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="deadline" 
                           id="deadline" 
                           min="1"
                           required
                           placeholder="Введите срок"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <!-- Размер аванса -->
                <div class="mb-4">
                    <label for="advance_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                        Размер аванса (%) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="advance_percent" 
                           id="advance_percent" 
                           step="0.01"
                           min="0"
                           max="100"
                           required
                           placeholder="Введите процент аванса"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <!-- Комментарий -->
                <div class="mb-4">
                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                        Комментарий (необязательно)
                    </label>
                    <textarea name="comment" 
                              id="comment"
                              rows="3"
                              placeholder="Дополнительная информация к заявке..."
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                </div>

                {{-- T5: Информация о формуле расчёта в форме заявки --}}
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded text-xs text-emerald-800">
                    <p class="font-medium mb-1">Как оценивается ваша заявка:</p>
                    <p>• Чем ниже цена — тем больше баллов (вес {{ $rfq->weight_price }}%)</p>
                    <p>• Чем короче срок — тем больше баллов (вес {{ $rfq->weight_deadline }}%)</p>
                    <p>• Чем меньше аванс — тем больше баллов (вес {{ $rfq->weight_advance }}%)</p>
                </div>

                <!-- Уведомление -->
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <div class="flex items-start">
                        <input type="checkbox"
                               name="acknowledgement"
                               id="acknowledgement"
                               required
                               class="mt-1 rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <label for="acknowledgement" class="ml-3 text-sm text-gray-700">
                            Я уведомлён, что процедура проведения Запроса котировок не является торгами и не обязывает к заключению договора.
                            Результаты подведения итогов носят информационный характер.
                        </label>
                    </div>
                </div>

                <!-- Кнопка -->
                <button type="submit" 
                        class="w-full inline-flex justify-center items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Подать заявку
                </button>
            </form>
        </div>
    @elseif($alreadyBid)
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-emerald-800 font-medium">Вы уже подали заявку на этот запрос котировок</p>
            </div>
        </div>
    @endif
@endauth
                    @elseif(!auth()->check())
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6 text-center">
                            <p class="text-gray-700">
                                <a href="{{ route('login') }}" class="text-emerald-600 hover:text-emerald-500 font-semibold">Войдите</a> 
                                или 
                                <a href="{{ route('register') }}" class="text-emerald-600 hover:text-emerald-500 font-semibold">зарегистрируйтесь</a>, 
                                чтобы подать заявку
                            </p>
                        </div>
                    @endif

                    <!-- Список заявок -->
                    @if($rfq->bids->count() > 0)
                        @php
                            // T2: Определяем компании текущего пользователя для подсветки его заявок
                            $userCompanyIds = auth()->check() && isset($availableCompanies) ? $availableCompanies->pluck('id')->toArray() : [];
                            // A15: Обезличиваем заявки на активном этапе для ВСЕХ (включая организатора)
                            $canSeeNames = $rfq->status === 'closed';
                        @endphp
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $canSeeNames ? 'Компания' : 'Участник' }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Цена ({{ $rfq->currency_symbol }})
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Срок (дн.)
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Аванс (%)
                                        </th>
                                        @if($rfq->status === 'closed')
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Итоговый балл
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Статус
                                            </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($rfq->bids->sortByDesc('total_score') as $index => $bid)
                                        @php
                                            $isUserBid = in_array($bid->company_id, $userCompanyIds);
                                        @endphp
                                        <tr class="{{ $bid->status === 'winner' ? 'bg-green-50' : ($isUserBid ? 'bg-emerald-50' : '') }}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($canSeeNames)
                                                    {{-- T2: После закрытия или для организатора показываем названия --}}
                                                    <a href="{{ route('companies.show', $bid->company) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">
                                                        {{ $bid->company->name }}
                                                    </a>
                                                @else
                                                    {{-- T2: На активном этапе показываем анонимный номер --}}
                                                    <span class="text-sm font-medium {{ $isUserBid ? 'text-emerald-600' : 'text-gray-900' }}">
                                                        Участник {{ $index + 1 }}
                                                        @if($isUserBid)
                                                            <span class="text-xs text-emerald-500">(вы)</span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ number_format($bid->price, 2, ',', ' ') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $bid->deadline }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $bid->advance_percent }}
                                            </td>
                                            @if($rfq->status === 'closed')
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    {{ number_format($bid->total_score, 2, ',', ' ') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($bid->status === 'winner')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Победитель
                                                        </span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">Заявок пока нет</p>
                    @endif

                    <!-- Протокол (если завершён) — A16: доступ только организатору и участникам -->
                    @if($rfq->status === 'closed' && $rfq->hasMedia('protocol'))
                        @php
                            $rfqIsParticipant = auth()->check() && isset($availableCompanies) && $rfq->bids->pluck('company_id')->intersect($availableCompanies->pluck('id'))->isNotEmpty();
                            $rfqIsManager = auth()->check() && $rfq->canManage(auth()->user());
                            $rfqCanViewProtocol = $rfqIsManager || $rfqIsParticipant;
                        @endphp
                        @if($rfqCanViewProtocol)
                            <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Процедура завершена</h3>
                                @if($rfq->winnerBid)
                                    <p class="text-sm text-gray-700 mb-1">
                                        <strong>Победитель:</strong> {{ $rfq->winnerBid->company->name }}
                                    </p>
                                    <p class="text-sm text-gray-700 mb-4">
                                        <strong>Итоговый балл:</strong> {{ number_format($rfq->winnerBid->total_score, 2, ',', ' ') }}
                                    </p>
                                @endif
                                <a href="{{ $rfq->getFirstMediaUrl('protocol') }}"
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                    Скачать протокол (PDF)
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Вкладка: Приглашения (для закрытых процедур) -->
                @if($rfq->type === 'closed')
                    <div id="content-invitations" class="tab-content hidden">
                        @if($rfq->invitations->count() > 0)
                            <div class="space-y-4">
                                @foreach($rfq->invitations as $invitation)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            @if($invitation->company->logo)
                                                <img src="{{ asset('storage/' . $invitation->company->logo) }}" 
                                                     alt="{{ $invitation->company->name }}" 
                                                     class="w-12 h-12 rounded-full mr-3 object-cover">
                                            @else
                                                <div class="w-12 h-12 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                                    <span class="text-sm text-gray-500 font-semibold">
                                                        {{ strtoupper(substr($invitation->company->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('companies.show', $invitation->company) }}" 
                                                   class="text-sm font-medium text-emerald-600 hover:text-emerald-500">
                                                    {{ $invitation->company->name }}
                                                </a>
                                                <p class="text-xs text-gray-500">
                                                    Приглашён: {{ $invitation->created_at->format('d.m.Y H:i') }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($invitation->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($invitation->status === 'accepted') bg-green-100 text-green-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            @if($invitation->status === 'pending') Ожидает ответа
                                            @elseif($invitation->status === 'accepted') Принято
                                            @else Отклонено
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">Приглашений нет</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- T4: Служба поддержки (перенесена вниз страницы) --}}
        <div class="bg-gray-50 rounded-lg p-4 mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Служба поддержки</h3>
                    <p class="text-xs text-gray-600">Возникли вопросы по процедуре?</p>
                </div>
                <a href="mailto:support@bizzio.ru?subject=RFQ {{ $rfq->number }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                    Написать в поддержку
                </a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // T1: Копирование ссылки на RFQ
    function copyRfqLink() {
        const linkInput = document.getElementById('rfq-link');
        const successMsg = document.getElementById('copy-success');
        if (linkInput) {
            navigator.clipboard.writeText(linkInput.value).then(() => {
                successMsg.classList.remove('hidden');
                setTimeout(() => successMsg.classList.add('hidden'), 2000);
            });
        }
    }

    // Переключение вкладок
    function showTab(tabName) {
        // Скрываем все вкладки
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Убираем активный класс у всех кнопок
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active', 'border-emerald-500', 'text-emerald-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Показываем выбранную вкладку
        document.getElementById('content-' + tabName).classList.remove('hidden');
        
        // Активируем кнопку
        const activeButton = document.getElementById('tab-' + tabName);
        activeButton.classList.remove('border-transparent', 'text-gray-500');
        activeButton.classList.add('active', 'border-emerald-500', 'text-emerald-600');
    }
</script>
@endpush
@endsection
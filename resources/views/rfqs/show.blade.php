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
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
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
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $rfq->type === 'open' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
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
                                   class="text-base font-semibold text-indigo-600 hover:text-indigo-500">
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
                        </div>

                        <!-- Техническое задание -->
                        @if($rfq->hasMedia('technical_specification'))
                            <a href="{{ $rfq->getFirstMediaUrl('technical_specification') }}" 
                               target="_blank"
                               class="block w-full text-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition mb-4">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Скачать ТЗ (PDF)
                            </a>
                        @endif

                        <!-- Служба поддержки -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Служба поддержки</h3>
                            <p class="text-xs text-gray-600 mb-3">Возникли вопросы по процедуре?</p>
                            <a href="mailto:support@bizzo.ru?subject=RFQ {{ $rfq->number }}" 
                               class="block w-full text-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                                Написать в поддержку
                            </a>
                        </div>
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
                            class="tab-button active border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
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
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Подать заявку</h3>
                            <form method="POST" action="{{ route('rfqs.bids.store', $rfq) }}">
                                @csrf

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <!-- Выбор компании -->
                                    <div class="md:col-span-2">
                                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Компания <span class="text-red-500">*</span>
                                        </label>
                                        <select name="company_id" id="company_id" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Выберите компанию</option>
                                            @foreach(auth()->user()->moderatedCompanies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Цена -->
                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                            Цена (руб. без НДС) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" name="price" id="price" required step="0.01" min="0"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <!-- Срок -->
                                    <div>
                                        <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                            Срок выполнения (дней) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" name="deadline" id="deadline" required min="1"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <!-- Аванс -->
                                    <div>
                                        <label for="advance_percent" class="block text-sm font-medium text-gray-700 mb-2">
                                            Размер аванса (%) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" name="advance_percent" id="advance_percent" required step="0.01" min="0" max="100"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <!-- Комментарий -->
                                    <div class="md:col-span-2">
                                        <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                                            Комментарий
                                        </label>
                                        <textarea name="comment" id="comment" rows="3" maxlength="1000"
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                    </div>
                                </div>

                                <!-- Уведомление -->
                                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                    <label class="flex items-start text-sm">
                                        <input type="checkbox" id="bid_agreement" required
                                               class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="ml-2 text-gray-700">
                                            Я уведомлён, что процедура не обязывает к заключению договора
                                        </span>
                                    </label>
                                </div>

                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                    Подать заявку
                                </button>
                            </form>
                        </div>
                    @elseif(!auth()->check())
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-center">
                            <p class="text-gray-700">
                                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500 font-semibold">Войдите</a> 
                                или 
                                <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-500 font-semibold">зарегистрируйтесь</a>, 
                                чтобы подать заявку
                            </p>
                        </div>
                    @endif

                    <!-- Список заявок -->
                    @if($rfq->bids->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Компания
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Цена (руб.)
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
                                    @foreach($rfq->bids->sortByDesc('total_score') as $bid)
                                        <tr class="{{ $bid->status === 'winner' ? 'bg-green-50' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('companies.show', $bid->company) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                                    {{ $bid->company->name }}
                                                </a>
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

                    <!-- Протокол (если завершён) -->
                    @if($rfq->status === 'closed' && $rfq->hasMedia('protocol'))
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
                                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
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

    </div>
</div>

@push('scripts')
<script>
    // Переключение вкладок
    function showTab(tabName) {
        // Скрываем все вкладки
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Убираем активный класс у всех кнопок
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Показываем выбранную вкладку
        document.getElementById('content-' + tabName).classList.remove('hidden');
        
        // Активируем кнопку
        const activeButton = document.getElementById('tab-' + tabName);
        activeButton.classList.remove('border-transparent', 'text-gray-500');
        activeButton.classList.add('active', 'border-indigo-500', 'text-indigo-600');
    }
</script>
@endpush
@endsection
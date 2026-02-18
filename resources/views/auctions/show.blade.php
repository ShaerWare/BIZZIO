@extends('layouts.app')

@section('title', 'Аукцион № ' . $auction->number)

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок с кнопками управления -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $auction->title }}</h1>
                <p class="mt-1 text-sm text-gray-500">Аукцион № {{ $auction->number }}</p>
            </div>
            
            @can('update', $auction)
                <div class="flex space-x-2">
                    <!-- Кнопка активации (только для черновиков) -->
                    @if($auction->status === 'draft')
                        <form method="POST" action="{{ route('auctions.activate', $auction) }}"
                            onsubmit="return confirm('Вы уверены, что хотите активировать аукцион? После активации редактирование будет ограничено.');">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Активировать аукцион
                            </button>
                        </form>
                    @endif

                    <!-- Кнопка редактирования (только для черновиков) -->
                    @if($auction->status === 'draft')
                        <a href="{{ route('auctions.edit', $auction) }}" 
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Редактировать
                        </a>
                    @endif
                    
                    <!-- Кнопка удаления (только для черновиков) -->
                    @can('delete', $auction)
                        @if($auction->status === 'draft')
                            <form method="POST" action="{{ route('auctions.destroy', $auction) }}" 
                                onsubmit="return confirm('Вы уверены, что хотите удалить этот аукцион?');">
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
                            $now = now();
                            $displayStatus = $auction->status;
                            $displayLabel = '';
                            $displayColor = '';
                            
                            // Определяем отображаемый статус
                            if ($auction->status === 'active') {
                                if ($auction->start_date->isFuture()) {
                                    $displayLabel = 'Ожидание начала приёма заявок';
                                    $displayColor = 'bg-yellow-100 text-yellow-800';
                                } elseif ($auction->end_date->isPast()) {
                                    $displayLabel = 'Приём заявок завершён';
                                    $displayColor = 'bg-orange-100 text-orange-800';
                                } else {
                                    $displayLabel = 'Приём заявок';
                                    $displayColor = 'bg-green-100 text-green-800';
                                }
                            } elseif ($auction->status === 'trading') {
                                $displayLabel = 'Торги';
                                $displayColor = 'bg-emerald-100 text-emerald-800';
                            } elseif ($auction->status === 'closed') {
                                $displayLabel = 'Завершён';
                                $displayColor = 'bg-gray-100 text-gray-800';
                            } elseif ($auction->status === 'cancelled') {
                                $displayLabel = 'Отменён';
                                $displayColor = 'bg-red-100 text-red-800';
                            } elseif ($auction->status === 'draft') {
                                $displayLabel = 'Черновик';
                                $displayColor = 'bg-yellow-100 text-yellow-800';
                            }
                        @endphp

                        <div class="flex items-center space-x-2 mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $displayColor }}">
                                {{ $displayLabel }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $auction->type === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $auction->type === 'open' ? 'Открытая процедура' : 'Закрытая процедура' }}
                            </span>
                            
                            <!-- Дополнительная информация о времени -->
                            @if($auction->status === 'active')
                                @if($auction->start_date->isFuture())
                                    <span class="text-xs text-gray-500">
                                        Начало через {{ $auction->start_date->diffForHumans() }}
                                    </span>
                                @elseif($auction->end_date->isFuture())
                                    <span class="text-xs text-gray-500">
                                        Завершится {{ $auction->end_date->diffForHumans() }}
                                    </span>
                                @endif
                            @endif
                            
                            @if($auction->status === 'trading' && $auction->last_bid_at)
                                <span class="text-xs text-gray-500">
                                    Закрытие через {{ Carbon\Carbon::parse($auction->last_bid_at)->addMinutes(20)->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <!-- Компания-организатор -->
                        <div class="flex items-center mb-4">
                            @if($auction->company->logo)
                                <img src="{{ asset('storage/' . $auction->company->logo) }}" 
                                     alt="{{ $auction->company->name }}" 
                                     class="w-12 h-12 rounded-full mr-3 object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                    <span class="text-sm text-gray-500 font-semibold">
                                        {{ strtoupper(substr($auction->company->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500">Организатор</p>
                                <a href="{{ route('companies.show', $auction->company) }}" 
                                   class="text-base font-semibold text-emerald-600 hover:text-emerald-500">
                                    {{ $auction->company->name }}
                                </a>
                            </div>
                        </div>

                        <!-- Сроки -->
                        <div class="flex items-center text-gray-600 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><strong>Приём заявок:</strong> {{ $auction->start_date->format('d.m.Y H:i') }} — {{ $auction->end_date->format('d.m.Y H:i') }} (МСК)</span>
                        </div>
                        <div class="flex items-center text-gray-600 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span><strong>Начало торгов:</strong> {{ $auction->trading_start->format('d.m.Y H:i') }} (МСК)</span>
                        </div>

                        <!-- Создатель -->
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Создатель: <strong>{{ $auction->creator->name }}</strong></span>
                        </div>
                    </div>

                    <!-- Боковая панель -->
                    <div class="w-full md:w-80">
                        <!-- Параметры аукциона -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Параметры аукциона:</h3>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• Начальная максимальная цена (НМЦ) — <strong>{{ number_format($auction->starting_price, 2, ',', ' ') }} {{ $auction->currency_symbol }}</strong></li>
                                <li>• Шаг снижения — <strong>0.5% — 5%</strong> от текущей цены</li>
                                @if($auction->isTrading())
                                    <li>• Текущая цена — <strong class="text-green-600">{{ number_format($currentPrice, 2, ',', ' ') }} {{ $auction->currency_symbol }}</strong></li>
                                @endif
                            </ul>
                        </div>

                        <!-- Техническое задание -->
                        @if($auction->hasMedia('technical_specification'))
                            <a href="{{ $auction->getFirstMediaUrl('technical_specification') }}" 
                               target="_blank"
                               class="block w-full text-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition mb-4">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Скачать ТЗ (PDF)
                            </a>
                        @endif

                        <!-- Протокол (если завершён) — A16: доступ только организатору и участникам -->
                        @if($auction->status === 'closed')
                            @php
                                $isParticipant = auth()->check() && $auction->bids->pluck('company_id')->intersect($userCompanies->pluck('id'))->isNotEmpty();
                                $isManager = auth()->check() && $auction->canManage(auth()->user());
                                $canViewProtocol = $isManager || $isParticipant;
                            @endphp
                            @if($canViewProtocol)
                                @if($auction->hasMedia('protocol'))
                                    <a href="{{ $auction->getFirstMediaUrl('protocol') }}"
                                       target="_blank"
                                       class="block w-full text-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition mb-4">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Скачать протокол (PDF)
                                    </a>
                                @elseif($isManager)
                                    <form method="POST" action="{{ route('auctions.protocol.generate', $auction) }}" class="mb-4">
                                        @csrf
                                        <button type="submit"
                                                class="block w-full text-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 transition">
                                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Сгенерировать протокол
                                        </button>
                                    </form>
                                @else
                                    <div class="mb-4 p-3 bg-gray-100 rounded text-center text-sm text-gray-600">
                                        Протокол ещё не сгенерирован
                                    </div>
                                @endif
                            @endif
                        @endif

                        <!-- Служба поддержки -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Служба поддержки</h3>
                            <p class="text-xs text-gray-600 mb-3">Возникли вопросы по процедуре?</p>
                            <a href="mailto:support@bizzo.ru?subject=Аукцион {{ $auction->number }}" 
                               class="block w-full text-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                                Написать в поддержку
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Предупреждение для черновиков -->
        @if($auction->status === 'draft')
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
                            <p>Этот аукцион находится в режиме черновика. Для начала приёма заявок активируйте его, нажав кнопку "Активировать аукцион".</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- A8: Панель торгов на главном экране (только для статуса trading) --}}
        @if($auction->isTrading())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row gap-6">
                        {{-- Левая колонка: Форма ставки --}}
                        <div class="lg:w-1/3">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Торги в реальном времени
                            </h3>

                            <div class="bg-emerald-50 rounded-lg p-4 mb-4">
                                <p class="text-sm text-gray-600">Текущая цена:</p>
                                <p class="text-3xl font-bold text-emerald-600 current-price">{{ number_format($currentPrice, 2, ',', ' ') }} {{ $auction->currency_symbol }}</p>
                                @if($auction->last_bid_at)
                                    <p class="text-xs text-gray-500 mt-1">
                                        Последняя ставка: {{ $auction->last_bid_at->format('H:i:s') }}
                                    </p>
                                @endif
                            </div>

                            @auth
                                @if($canBid)
                                    <form method="POST" action="{{ route('auctions.bids.store', $auction) }}" class="space-y-4">
                                        @csrf
                                        @if($userCompanies->count() > 1)
                                            <select name="company_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-blue-500 text-sm">
                                                <option value="">Выберите компанию...</option>
                                                @foreach($userCompanies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" name="company_id" value="{{ $userCompanies->first()->id }}">
                                        @endif

                                        <div>
                                            <p class="text-xs text-gray-500 mb-2">Снижение цены:</p>
                                            <div class="grid grid-cols-3 gap-1">
                                                @php $percentages = [0.5, 1, 2, 3, 4, 5]; @endphp
                                                @foreach($percentages as $pct)
                                                    @php $newPrice = round($currentPrice * (1 - $pct / 100), 2); @endphp
                                                    <button type="button" onclick="setMainBidPrice({{ $newPrice }})"
                                                            class="main-bid-btn px-2 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-emerald-50 hover:border-emerald-500 transition">
                                                        -{{ $pct }}%
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        <input type="number" name="price" id="main-price" step="0.01"
                                               min="{{ $currentPrice - $stepRange['max'] }}" max="{{ $currentPrice - $stepRange['min'] }}"
                                               required placeholder="Ваша ставка ({{ $auction->currency_symbol }})"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-blue-500">

                                        <label class="flex items-start text-xs text-gray-600">
                                            <input type="checkbox" name="acknowledgement" required class="mt-0.5 mr-2 rounded border-gray-300">
                                            Подтверждаю условия участия
                                        </label>

                                        <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-md hover:bg-emerald-700 transition">
                                            Сделать ставку
                                        </button>
                                    </form>
                                @elseif($existingBid && $existingBid->type === 'bid')
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                        <p class="text-sm text-green-800">Вы участвуете в торгах</p>
                                    </div>
                                @endif
                            @else
                                <div class="bg-gray-50 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-600">
                                        <a href="{{ route('login') }}" class="text-emerald-600 hover:underline">Войдите</a>, чтобы участвовать
                                    </p>
                                </div>
                            @endauth
                        </div>

                        {{-- Правая колонка: Таблица последних ставок --}}
                        <div class="lg:w-2/3">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                История ставок
                                <span class="text-sm font-normal text-gray-500">({{ $auction->tradingBids->count() }})</span>
                            </h3>

                            @if($auction->tradingBids->count() > 0)
                                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Участник</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Время</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @php $userCompanyIds = auth()->check() ? $userCompanies->pluck('id')->toArray() : []; @endphp
                                            @foreach($auction->tradingBids->take(20) as $bid)
                                                @php $isUserBid = in_array($bid->company_id, $userCompanyIds); @endphp
                                                <tr class="{{ $isUserBid ? 'bg-emerald-50' : '' }}">
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm {{ $isUserBid ? 'text-emerald-600 font-medium' : 'text-gray-900' }}">
                                                        {{ $bid->anonymous_code }}
                                                        @if($isUserBid) <span class="text-xs">(вы)</span> @endif
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                        {{ number_format($bid->price, 2, ',', ' ') }} {{ $auction->currency_symbol }}
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $bid->created_at->format('H:i:s') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    Ставок пока нет. Будьте первым!
                                </div>
                            @endif
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
                        @if($auction->status === 'active')
                            Заявки ({{ $auction->initialBids->count() }})
                        @else
                            Ставки ({{ $auction->tradingBids->count() }})
                        @endif
                    </button>
                    @if($auction->type === 'closed')
                        <button onclick="showTab('invitations')" 
                                id="tab-invitations"
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Приглашения ({{ $auction->invitations->count() }})
                        </button>
                    @endif
                </nav>
            </div>

            <!-- Контент вкладок -->
            <div class="p-6">
                <!-- Вкладка: Описание -->
                <div id="content-description" class="tab-content">
                    @if($auction->description)
                        <div class="prose max-w-none">
                            <div class="text-gray-700">{!! nl2br(e($auction->description)) !!}</div>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">Описание не добавлено</p>
                    @endif
                </div>

                <!-- Вкладка: Заявки/Ставки -->
                <div id="content-bids" class="tab-content hidden">

                    <!-- Форма подачи заявки/ставки -->
                    @auth
                        @if($canBid)
                            <div id="bid-form" class="bg-green-50 border-2 border-green-200 rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    @if($auction->isTrading())
                                        Сделать ставку
                                    @else
                                        Подать заявку на участие
                                    @endif
                                </h3>

                                <form method="POST" action="{{ route('auctions.bids.store', $auction) }}">
                                    @csrf

                                    <!-- Выбор компании -->
                                    @if($userCompanies->count() > 1)
                                        <div class="mb-4">
                                            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                                Компания <span class="text-red-500">*</span>
                                            </label>
                                            <select name="company_id" 
                                                    id="company_id" 
                                                    required
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                                <option value="">Выберите компанию...</option>
                                                @foreach($userCompanies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @else
                                        <input type="hidden" name="company_id" value="{{ $userCompanies->first()->id }}">
                                        <div class="mb-4 p-3 bg-white rounded border border-gray-200">
                                            <p class="text-sm text-gray-600">
                                                Заявка от компании: <strong>{{ $userCompanies->first()->name }}</strong>
                                            </p>
                                        </div>
                                    @endif

                                    @if($auction->isTrading())
                                        <!-- Цена ставки (для торгов) -->
                                        <div class="mb-4">
                                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                                Ваша ставка ({{ $auction->currency_symbol }}) <span class="text-red-500">*</span>
                                            </label>

                                            <!-- Кнопки быстрого выбора снижения -->
                                            <div class="mb-3">
                                                <p class="text-xs text-gray-500 mb-2">Выберите размер снижения:</p>
                                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                                    @php
                                                        $percentages = [0.5, 1, 2, 3, 4, 5];
                                                    @endphp
                                                    @foreach($percentages as $pct)
                                                        @php
                                                            $newPrice = round($currentPrice * (1 - $pct / 100), 2);
                                                        @endphp
                                                        <button type="button"
                                                                onclick="setBidPrice({{ $newPrice }})"
                                                                class="bid-percent-btn px-3 py-2 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-green-50 hover:border-green-500 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition">
                                                            -{{ $pct }}%
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <input type="number"
                                                   name="price"
                                                   id="price"
                                                   step="0.01"
                                                   min="{{ $currentPrice - $stepRange['max'] }}"
                                                   max="{{ $currentPrice - $stepRange['min'] }}"
                                                   required
                                                   placeholder="Введите цену или выберите снижение выше"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                            <p class="mt-1 text-xs text-gray-500">
                                                Текущая цена: <strong>{{ number_format($currentPrice, 2, ',', ' ') }} {{ $auction->currency_symbol }}</strong><br>
                                                Допустимый диапазон: {{ number_format($currentPrice - $stepRange['max'], 2, ',', ' ') }} — {{ number_format($currentPrice - $stepRange['min'], 2, ',', ' ') }} {{ $auction->currency_symbol }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Комментарий -->
                                    <div class="mb-4">
                                        <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                                            Комментарий (необязательно)
                                        </label>
                                        <textarea name="comment" 
                                                  id="comment"
                                                  rows="3"
                                                  placeholder="Дополнительная информация..."
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                                    </div>

                                    <!-- Подтверждение соответствия ТЗ -->
                                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                                        <div class="flex items-start">
                                            <input type="checkbox"
                                                   name="confirms_ts_compliance"
                                                   id="confirms_ts_compliance"
                                                   required
                                                   class="mt-1 rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring-green-500">
                                            <label for="confirms_ts_compliance" class="ml-3 text-sm text-gray-700">
                                                Настоящим подтверждаю соответствие своего предложения Техническому заданию (ТЗ).
                                            </label>
                                        </div>
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
                                                Я уведомлён, что процедура проведения Аукциона не является торгами и не обязывает к заключению договора.
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
                                        @if($auction->isTrading())
                                            Сделать ставку
                                        @else
                                            Подать заявку
                                        @endif
                                    </button>
                                </form>
                            </div>
                        @elseif($existingBid)
                            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-emerald-800 font-medium">Вы уже подали заявку на этот аукцион</p>
                                </div>
                            </div>
                        @elseif($auction->status === 'draft')
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <p class="text-gray-700">Аукцион находится в режиме черновика. Приём заявок начнётся после его активации.</p>
                                </div>
                            </div>
                        @elseif($auction->status === 'active' && $auction->start_date->isFuture())
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-yellow-800">Приём заявок начнётся <strong>{{ $auction->start_date->format('d.m.Y в H:i') }}</strong></p>
                                </div>
                            </div>
                        @elseif($auction->status === 'active' && $auction->end_date->isPast())
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-orange-800">Приём заявок завершён <strong>{{ $auction->end_date->format('d.m.Y в H:i') }}</strong></p>
                                </div>
                            </div>
                        @elseif($auction->status === 'closed')
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <p class="text-gray-700">Аукцион завершён</p>
                                </div>
                            </div>
                        @elseif($auction->type === 'closed' && $userCompanies->isNotEmpty())
                            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    <p class="text-red-800">Это закрытый аукцион. Подать заявку могут только приглашённые компании.</p>
                                </div>
                            </div>
                        @elseif($userCompanies->isEmpty())
                            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <p class="text-emerald-800">Для подачи заявки необходимо быть модератором компании. <a href="{{ route('companies.create') }}" class="underline font-semibold">Создайте компанию</a> или получите права модератора.</p>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6 text-center">
                            <p class="text-gray-700">
                                <a href="{{ route('login') }}" class="text-emerald-600 hover:text-emerald-500 font-semibold">Войдите</a> 
                                или 
                                <a href="{{ route('register') }}" class="text-emerald-600 hover:text-emerald-500 font-semibold">зарегистрируйтесь</a>, 
                                чтобы подать заявку
                            </p>
                        </div>
                    @endauth

                    <!-- Список заявок/ставок -->
                    @if($auction->bids->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @if($auction->isTrading())
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Участник
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Цена ({{ $auction->currency_symbol }})
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Время ставки
                                            </th>
                                        @else
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Компания
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Дата подачи
                                            </th>
                                        @endif
                                        @if($auction->status === 'closed')
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Статус
                                            </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @php
                                        // A7: Определяем компании текущего пользователя для подсветки его ставок
                                        $userCompanyIds = auth()->check() ? $userCompanies->pluck('id')->toArray() : [];
                                    @endphp
                                    @foreach($auction->bids->sortBy('created_at') as $bid)
                                        @php
                                            $isUserBid = in_array($bid->company_id, $userCompanyIds);
                                        @endphp
                                        <tr class="{{ $bid->status === 'winner' ? 'bg-green-50' : ($isUserBid ? 'bg-emerald-50' : '') }}">
                                            @if($auction->isTrading())
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{-- A10: Скрываем названия компаний от всех (включая организатора) до завершения аукциона --}}
                                                    <span class="text-sm font-medium {{ $isUserBid ? 'text-emerald-600' : 'text-gray-900' }}">
                                                        {{ $bid->anonymous_code }}
                                                        @if($isUserBid)
                                                            <span class="ml-1 text-xs text-emerald-500">(вы)</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    {{ number_format($bid->price, 2, ',', ' ') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $bid->created_at->format('d.m.Y H:i:s') }}
                                                </td>
                                            @else
                                                {{-- Для статуса active (приём заявок) или closed (завершён) --}}
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($auction->status === 'closed')
                                                        {{-- После закрытия показываем названия компаний --}}
                                                        <a href="{{ route('companies.show', $bid->company) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">
                                                            {{ $bid->company->name }}
                                                        </a>
                                                    @else
                                                        {{-- A15: На этапе приёма заявок скрываем названия от ВСЕХ (включая организатора) --}}
                                                        <span class="text-sm font-medium {{ $isUserBid ? 'text-emerald-600' : 'text-gray-900' }}">
                                                            Участник {{ $loop->iteration }}
                                                            @if($isUserBid)
                                                                <span class="ml-1 text-xs text-emerald-500">(вы)</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $bid->created_at->format('d.m.Y H:i') }}
                                                </td>
                                            @endif
                                            @if($auction->status === 'closed')
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
                </div>

                <!-- Вкладка: Приглашения (для закрытых процедур) -->
                @if($auction->type === 'closed')
                    <div id="content-invitations" class="tab-content hidden">
                        @if($auction->invitations->count() > 0)
                            <div class="space-y-4">
                                @foreach($auction->invitations as $invitation)
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

    </div>
</div>

@push('scripts')
<script>
    // Установка цены ставки (A5) - для формы во вкладке
    function setBidPrice(price) {
        const priceInput = document.getElementById('price');
        if (priceInput) {
            priceInput.value = price.toFixed(2);
            // Подсветка выбранной кнопки
            document.querySelectorAll('.bid-percent-btn').forEach(btn => {
                btn.classList.remove('bg-green-100', 'border-green-500', 'text-green-700');
            });
            event.target.classList.add('bg-green-100', 'border-green-500', 'text-green-700');
        }
    }

    // Установка цены ставки (A8) - для основной панели торгов
    function setMainBidPrice(price) {
        const priceInput = document.getElementById('main-price');
        if (priceInput) {
            priceInput.value = price.toFixed(2);
            // Подсветка выбранной кнопки
            document.querySelectorAll('.main-bid-btn').forEach(btn => {
                btn.classList.remove('bg-emerald-100', 'border-emerald-500', 'text-emerald-700');
            });
            event.target.classList.add('bg-emerald-100', 'border-emerald-500', 'text-emerald-700');
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

    @if($auction->isTrading())
        // Long polling для обновления торгов
        let pollingInterval;
        
        function updateAuctionState() {
            fetch('{{ route("auctions.state", $auction) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'trading') {
                        // Обновление текущей цены
                        document.querySelectorAll('.current-price').forEach(el => {
                            el.textContent = data.current_price_formatted;
                        });
                        
                        // TODO: Обновление таблицы ставок
                        // TODO: Обновление таймера
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Запуск polling каждые 10 секунд
        pollingInterval = setInterval(updateAuctionState, 10000);
        
        // Остановка при уходе со страницы
        window.addEventListener('beforeunload', () => {
            clearInterval(pollingInterval);
        });
    @endif
</script>
@endpush
@endsection
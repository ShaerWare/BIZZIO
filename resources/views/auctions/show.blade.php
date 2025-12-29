<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Аукцион {{ $auction->number }}
            </h2>
            
            @auth
                @if($auction->canManage(auth()->user()))
                    <div class="flex space-x-2">
                        @if($auction->status === 'draft')
                            <a href="{{ route('auctions.edit', $auction) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Редактировать
                            </a>
                            
                            <form method="POST" action="{{ route('auctions.activate', $auction) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                    Активировать
                                </button>
                            </form>
                            
                            <form method="POST" action="{{ route('auctions.destroy', $auction) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Вы уверены, что хотите удалить этот аукцион?')"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    Удалить
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Предупреждение для черновиков -->
            @if($auction->status === 'draft')
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <span class="font-medium">Этот аукцион находится в режиме черновика.</span>
                                Компании не могут подавать заявки, пока вы не активируете его.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Основная информация -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Левая колонка -->
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ $auction->title }}</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Статус:</span>
                                    @if($auction->status === 'draft')
                                        <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Черновик</span>
                                    @elseif($auction->status === 'active')
                                        <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Приём заявок</span>
                                    @elseif($auction->status === 'trading')
                                        <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Торги</span>
                                    @elseif($auction->status === 'closed')
                                        <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Завершён</span>
                                    @endif
                                </div>

                                <div>
                                    <span class="text-sm font-medium text-gray-500">Организатор:</span>
                                    <a href="{{ route('companies.show', $auction->company) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">
                                        {{ $auction->company->name }}
                                    </a>
                                </div>

                                <div>
                                    <span class="text-sm font-medium text-gray-500">Тип процедуры:</span>
                                    <span class="ml-2 text-gray-900">{{ $auction->type === 'open' ? 'Открытая' : 'Закрытая' }}</span>
                                </div>

                                <div>
                                    <span class="text-sm font-medium text-gray-500">Создатель:</span>
                                    <span class="ml-2 text-gray-900">{{ $auction->creator->name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Правая колонка -->
                        <div>
                            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">Начальная цена:</span>
                                    <span class="text-lg font-bold text-gray-900">{{ number_format($auction->starting_price, 2, '.', ' ') }} ₽</span>
                                </div>

                                @if($auction->isTrading())
                                    <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                        <span class="text-sm font-medium text-gray-500">Текущая цена:</span>
                                        <span id="current-price" class="text-2xl font-bold text-green-600">{{ number_format($currentPrice, 2, '.', ' ') }} ₽</span>
                                    </div>

                                    <!-- Таймер обратного отсчёта -->
                                    <div id="timer-container" class="pt-3 border-t border-gray-200">
                                        <span class="text-sm font-medium text-gray-500">Время до закрытия:</span>
                                        <div id="timer" class="text-xl font-bold text-red-600 mt-1">
                                            Загрузка...
                                        </div>
                                    </div>
                                @endif

                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">Шаг аукциона:</span>
                                    <span class="text-gray-900">{{ $auction->step_percent }}%</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">Диапазон шага:</span>
                                    <span class="text-gray-900">{{ number_format($stepRange['min'], 2) }} - {{ number_format($stepRange['max'], 2) }} ₽</span>
                                </div>
                            </div>

                            <!-- Даты -->
                            <div class="mt-4 space-y-2 text-sm">
                                <p><span class="font-medium">Приём заявок:</span> {{ $auction->start_date->format('d.m.Y H:i') }} — {{ $auction->end_date->format('d.m.Y H:i') }} (МСК+3)</p>
                                <p><span class="font-medium">Начало торгов:</span> {{ $auction->trading_start->format('d.m.Y H:i') }} (МСК+3)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладки -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{ tab: 'description' }">
                <!-- Навигация вкладок -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button @click="tab = 'description'" 
                                :class="tab === 'description' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Описание
                        </button>

                        <button @click="tab = 'bids'" 
                                :class="tab === 'bids' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            @if($auction->isTrading())
                                Ставки (<span id="bids-count">{{ $auction->tradingBids->count() }}</span>)
                            @else
                                Заявки на участие ({{ $auction->initialBids->count() }})
                            @endif
                        </button>

                        @if($auction->type === 'closed')
                            <button @click="tab = 'invitations'" 
                                    :class="tab === 'invitations' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Приглашения ({{ $auction->invitations->count() }})
                            </button>
                        @endif
                    </nav>
                </div>

                <!-- Содержимое вкладок -->
                <div class="p-6">
                    <!-- Вкладка: Описание -->
                    <div x-show="tab === 'description'" x-transition>
                        <div class="prose max-w-none">
                            {!! nl2br(e($auction->description)) !!}
                        </div>

                        <!-- Техническое задание -->
                        @if($auction->hasMedia('technical_specification'))
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-semibold text-gray-900 mb-2">Техническое задание</h4>
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

                        <!-- Протокол (если завершён) -->
                        @if($auction->status === 'closed' && $auction->hasMedia('protocol'))
                            <div class="mt-6 p-4 bg-green-50 rounded-lg">
                                <h4 class="font-semibold text-gray-900 mb-2">Протокол подведения итогов</h4>
                                <a href="{{ $auction->getFirstMediaUrl('protocol') }}" 
                                   target="_blank"
                                   class="inline-flex items-center text-green-600 hover:text-green-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Скачать протокол
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Вкладка: Заявки/Ставки -->
                    <div x-show="tab === 'bids'" x-transition>
                        @if($auction->isTrading())
                            <!-- Таблица ставок в торгах (с обезличиванием) -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200" id="bids-table">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Код участника
                                            </th>
                                            @if($auction->canManage(auth()->user()))
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Компания
                                                </th>
                                            @endif
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Цена, ₽
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Время ставки
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($auction->tradingBids as $bid)
                                            <tr class="{{ $bid->status === 'winner' ? 'bg-green-50' : '' }}">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-sm font-mono font-bold text-gray-900">{{ $bid->anonymous_code }}</span>
                                                    @if($bid->status === 'winner')
                                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Победитель</span>
                                                    @endif
                                                </td>
                                                @if($auction->canManage(auth()->user()))
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $bid->company->name }}
                                                    </td>
                                                @endif
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    {{ number_format($bid->price, 2, '.', ' ') }} ₽
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $bid->created_at->format('d.m.Y H:i:s') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $auction->canManage(auth()->user()) ? '4' : '3' }}" class="px-6 py-4 text-center text-gray-500">
                                                    Ставок пока нет
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <!-- Таблица заявок на участие -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Компания
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Дата подачи
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Статус
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($auction->initialBids as $bid)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $bid->company->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $bid->created_at->format('d.m.Y H:i') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Принята
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                                    Заявок пока нет
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <!-- Форма подачи заявки/ставки -->
                        @auth
                            @if($canBid && !$existingBid)
                                <div id="bid-form" class="mt-6 p-6 bg-blue-50 border border-blue-200 rounded-lg">
                                    <h4 class="font-semibold text-gray-900 mb-4">
                                        @if($auction->isTrading())
                                            Сделать ставку
                                        @else
                                            Подать заявку на участие
                                        @endif
                                    </h4>

                                    <form method="POST" action="{{ route('auctions.bids.store', $auction) }}">
                                        @csrf

                                        <!-- Выбор компании -->
                                        @if($userCompanies->count() > 1)
                                            <div class="mb-4">
                                                <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Компания *
                                                </label>
                                                <select name="company_id" 
                                                        id="company_id" 
                                                        required
                                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Выберите компанию</option>
                                                    @foreach($userCompanies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <input type="hidden" name="company_id" value="{{ $userCompanies->first()->id }}">
                                        @endif

                                        @if($auction->isTrading())
                                            <!-- Поле цены (для торгов) -->
                                            <div class="mb-4">
                                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Ваша цена, ₽ *
                                                </label>
                                                <input type="number" 
                                                       name="price" 
                                                       id="price" 
                                                       step="0.01"
                                                       min="{{ $currentPrice - $stepRange['max'] }}"
                                                       max="{{ $currentPrice - $stepRange['min'] }}"
                                                       required
                                                       placeholder="Введите цену"
                                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <p class="mt-1 text-xs text-gray-500">
                                                    Допустимый диапазон: {{ number_format($currentPrice - $stepRange['max'], 2) }} — {{ number_format($currentPrice - $stepRange['min'], 2) }} ₽
                                                </p>
                                            </div>
                                        @endif

                                        <!-- Комментарий -->
                                        <div class="mb-4">
                                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                                Комментарий (необязательно)
                                            </label>
                                            <textarea name="comment" 
                                                      id="comment" 
                                                      rows="3"
                                                      maxlength="{{ $auction->isTrading() ? '500' : '1000' }}"
                                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        </div>

                                        <!-- Чекбокс подтверждения -->
                                        <div class="mb-4">
                                            <label class="flex items-start">
                                                <input type="checkbox" 
                                                       name="agreement" 
                                                       required
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                                                <span class="ml-2 text-sm text-gray-700">
                                                    Я уведомлен, что процедура проведения Аукциона регулируется внутренними правилами платформы и не обязывает к заключению договора. *
                                                </span>
                                            </label>
                                        </div>

                                        <button type="submit" 
                                                class="w-full px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            @if($auction->isTrading())
                                                Сделать ставку
                                            @else
                                                Подать заявку
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            @elseif($existingBid)
                                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm text-green-700">
                                        @if($auction->isTrading())
                                            <span class="font-medium">Ваш код участника:</span> {{ $existingBid->anonymous_code }}
                                        @else
                                            <span class="font-medium">Вы уже подали заявку на участие.</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endauth
                    </div>

                    <!-- Вкладка: Приглашения (только для закрытых) -->
                    @if($auction->type === 'closed')
                        <div x-show="tab === 'invitations'" x-transition>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Компания
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Статус
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($auction->invitations as $invitation)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $invitation->company->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Приглашена
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Блок службы поддержки -->
            <div class="mt-6 bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="font-semibold text-gray-900 mb-2">Служба поддержки</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        Возникли вопросы по аукциону {{ $auction->number }}? Напишите нам:
                    </p>
                    <a href="mailto:support@bizzo.ru?subject=Вопрос по аукциону {{ $auction->number }}" 
                       class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        support@bizzo.ru
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($auction->isTrading())
        <!-- JavaScript для Long Polling -->
        <script>
            let pollingInterval;
            let lastUpdateTime = Date.now();

            function updateAuctionState() {
                fetch('{{ route('auctions.state', $auction) }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'trading') {
                            // Обновляем текущую цену
                            document.getElementById('current-price').textContent = data.current_price_formatted;
                            
                            // Обновляем счётчик ставок
                            document.getElementById('bids-count').textContent = data.bids_count;
                            
                            // Обновляем таблицу ставок
                            updateBidsTable(data.bids);
                            
                            // Обновляем таймер
                            if (data.time_remaining !== null) {
                                updateTimer(data.time_remaining);
                            }
                            
                            lastUpdateTime = Date.now();
                        } else {
                            // Аукцион завершён, останавливаем polling
                            clearInterval(pollingInterval);
                            location.reload(); // Перезагружаем страницу
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка при обновлении состояния:', error);
                    });
            }

            function updateBidsTable(bids) {
                const tbody = document.querySelector('#bids-table tbody');
                const isOrganizer = {{ $auction->canManage(auth()->user()) ? 'true' : 'false' }};
                
                if (bids.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="${isOrganizer ? '4' : '3'}" class="px-6 py-4 text-center text-gray-500">
                                Ставок пока нет
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                let html = '';
                bids.forEach(bid => {
                    const isWinner = bid.is_winner || false;
                    const rowClass = isWinner ? 'bg-green-50' : '';
                    
                    html += `
                        <tr class="${rowClass}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-bold text-gray-900">${bid.anonymous_code}</span>
                                ${isWinner ? '<span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Победитель</span>' : ''}
                            </td>
                            ${isOrganizer ? `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bid.company_name || ''}</td>` : ''}
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ${bid.price_formatted}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${bid.created_at}
                            </td>
                        </tr>
                    `;
                });
                
                tbody.innerHTML = html;
            }

            function updateTimer(seconds) {
                if (seconds <= 0) {
                    document.getElementById('timer').textContent = 'Аукцион завершён';
                    clearInterval(pollingInterval);
                    setTimeout(() => location.reload(), 2000);
                    return;
                }
                
                const minutes = Math.floor(seconds / 60);
                const secs = seconds % 60;
                document.getElementById('timer').textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
            }

            // Запускаем polling каждые 10 секунд
            pollingInterval = setInterval(updateAuctionState, 10000);
            
            // Первый запрос сразу
            updateAuctionState();
        </script>
    @endif
</x-app-layout>
@props(['auction'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
    <div class="p-6">
        <!-- Статус -->
        @php
            $now = now();
            
            // Определяем отображаемый статус
            if ($auction->status === 'active') {
                if ($auction->start_date->isFuture()) {
                    $statusLabel = 'Скоро';
                    $statusColor = 'bg-yellow-100 text-yellow-800';
                } elseif ($auction->end_date->isPast()) {
                    $statusLabel = 'Завершён приём';
                    $statusColor = 'bg-orange-100 text-orange-800';
                } else {
                    $statusLabel = 'Приём заявок';
                    $statusColor = 'bg-green-100 text-green-800';
                }
            } elseif ($auction->status === 'trading') {
                $statusLabel = 'Торги';
                $statusColor = 'bg-blue-100 text-blue-800';
            } elseif ($auction->status === 'closed') {
                $statusLabel = 'Завершён';
                $statusColor = 'bg-gray-100 text-gray-800';
            } elseif ($auction->status === 'cancelled') {
                $statusLabel = 'Отменён';
                $statusColor = 'bg-red-100 text-red-800';
            } elseif ($auction->status === 'draft') {
                $statusLabel = 'Черновик';
                $statusColor = 'bg-yellow-100 text-yellow-800';
            }
        @endphp

        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
            {{ $statusLabel }}
        </span>

        <!-- Название -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
            <a href="{{ route('auctions.show', $auction) }}" class="hover:text-indigo-600 transition-colors">
                {{ Str::limit($auction->title, 60) }}
            </a>
        </h3>

        <!-- Организатор -->
        <div class="mb-3">
            <p class="text-sm text-gray-600">
                <span class="font-medium">Организатор:</span> 
                <a href="{{ route('companies.show', $auction->company) }}" class="text-indigo-600 hover:text-indigo-800">
                    {{ $auction->company->name }}
                </a>
            </p>
        </div>

        <!-- Информация -->
        <div class="space-y-2 text-sm text-gray-600 mb-4">
            <div class="flex justify-between">
                <span>Начальная цена:</span>
                <span class="font-semibold text-gray-900">{{ number_format($auction->starting_price, 2, '.', ' ') }} ₽</span>
            </div>
            
            @if($auction->isTrading())
                <div class="flex justify-between">
                    <span>Текущая цена:</span>
                    <span class="font-semibold text-green-600">{{ number_format($auction->getCurrentPrice(), 2, '.', ' ') }} ₽</span>
                </div>
            @endif

            <div class="flex justify-between">
                <span>Тип:</span>
                <span>{{ $auction->type === 'open' ? 'Открытая' : 'Закрытая' }}</span>
            </div>

            <div class="flex justify-between">
                <span>Заявок/Ставок:</span>
                <span class="font-semibold">{{ $auction->bids->count() }}</span>
            </div>
        </div>

        <!-- Даты -->
        <div class="pt-3 border-t border-gray-200 text-xs text-gray-500">
            @if($auction->status === 'active')
                @if($auction->start_date->isFuture())
                    <p>Приём заявок с: <span class="font-medium">{{ $auction->start_date->format('d.m.Y H:i') }}</span></p>
                @elseif($auction->end_date->isPast())
                    <p>Приём заявок завершён: <span class="font-medium">{{ $auction->end_date->format('d.m.Y H:i') }}</span></p>
                @else
                    <p>Приём заявок до: <span class="font-medium">{{ $auction->end_date->format('d.m.Y H:i') }}</span></p>
                @endif
            @elseif($auction->status === 'trading')
                <p>Торги начались: <span class="font-medium">{{ $auction->trading_start->format('d.m.Y H:i') }}</span></p>
            @else
                <p>Создан: <span class="font-medium">{{ $auction->created_at->format('d.m.Y') }}</span></p>
            @endif
        </div>

        <!-- Кнопка просмотра -->
        <div class="mt-4">
            <a href="{{ route('auctions.show', $auction) }}" 
               class="block w-full text-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Подробнее
            </a>
        </div>
    </div>
</div>
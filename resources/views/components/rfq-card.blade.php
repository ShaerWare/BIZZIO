@props(['rfq'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
    <div class="p-6">
        <!-- Заголовок -->
        <div class="flex justify-between items-start mb-3">
            <h3 class="text-xl font-semibold text-gray-900">
                <a href="{{ route('rfqs.show', $rfq) }}" class="hover:text-indigo-600 transition">
                    {{ Str::limit($rfq->title, 60) }}
                </a>
            </h3>
        </div>

        <!-- Номер и статус -->
        <div class="flex items-center space-x-2 mb-4">
            <span class="text-sm text-gray-500 font-mono">{{ $rfq->number }}</span>
            @php
                // Определяем статус с учётом дат
                if ($rfq->status === 'active') {
                    if ($rfq->start_date->isFuture()) {
                        $statusColor = 'bg-yellow-100 text-yellow-800';
                        $statusLabel = 'Скоро';
                    } elseif ($rfq->end_date->isPast()) {
                        $statusColor = 'bg-orange-100 text-orange-800';
                        $statusLabel = 'Подведение итогов';
                    } else {
                        $statusColor = 'bg-green-100 text-green-800';
                        $statusLabel = 'Приём заявок';
                    }
                } else {
                    $statusColors = [
                        'closed' => 'bg-gray-100 text-gray-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        'draft' => 'bg-yellow-100 text-yellow-800',
                    ];
                    $statusLabels = [
                        'closed' => 'Завершён',
                        'cancelled' => 'Отменён',
                        'draft' => 'Черновик',
                    ];
                    $statusColor = $statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-800';
                    $statusLabel = $statusLabels[$rfq->status] ?? $rfq->status;
                }
            @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                {{ $statusLabel }}
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rfq->type === 'open' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                {{ $rfq->type === 'open' ? 'Открытая' : 'Закрытая' }}
            </span>
        </div>

        <!-- Организатор -->
        <div class="flex items-center mb-3">
            @if($rfq->company->logo)
                <img src="{{ asset('storage/' . $rfq->company->logo) }}" 
                     alt="{{ $rfq->company->name }}" 
                     class="w-10 h-10 rounded-full mr-3 object-cover">
            @else
                <div class="w-10 h-10 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                    <span class="text-sm text-gray-500 font-semibold">
                        {{ strtoupper(substr($rfq->company->name, 0, 2)) }}
                    </span>
                </div>
            @endif
            <div>
                <p class="text-xs text-gray-500">Организатор</p>
                <a href="{{ route('companies.show', $rfq->company) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    {{ Str::limit($rfq->company->name, 30) }}
                </a>
            </div>
        </div>

        <!-- Описание -->
        @if($rfq->description)
            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                {{ Str::limit($rfq->description, 100) }}
            </p>
        @endif

        <!-- Информация -->
        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                @if($rfq->status === 'active' && $rfq->start_date->isFuture())
                    <span>С {{ $rfq->start_date->format('d.m.Y') }}</span>
                @elseif($rfq->status === 'active' && $rfq->end_date->isPast())
                    <span>Завершён {{ $rfq->end_date->format('d.m.Y') }}</span>
                @else
                    <span>До {{ $rfq->end_date->format('d.m.Y') }}</span>
                @endif
            </div>
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span>Заявок: {{ $rfq->bids->count() }}</span>
            </div>
        </div>

        <!-- Кнопка -->
        <a href="{{ route('rfqs.show', $rfq) }}" 
           class="block w-full text-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
            Подробнее
        </a>
    </div>

    <div class="px-6 py-3 bg-gray-50 text-xs text-gray-500">
        Создан: {{ $rfq->created_at->format('d.m.Y H:i') }}
    </div>
</div>
@extends('layouts.app')

@section('title', 'Мои тендеры')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Мои тендеры</h1>
            @if(auth()->user()->isModeratorOfAnyCompany())
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Разместить
                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                         style="display: none;">
                        <div class="py-1">
                            <a href="{{ route('rfqs.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Запрос цен
                            </a>
                            <a href="{{ route('auctions.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Аукцион
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Список -->
        @if($items->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">У вас пока нет тендеров</h3>
                    <p class="mt-1 text-sm text-gray-500">Разместите запрос цен или аукцион</p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($items as $item)
                    @php
                        $model = $item['kind'] === 'rfq' ? $item['model'] : $item['model'];
                        $showRoute = $item['kind'] === 'rfq' ? route('rfqs.show', $model) : route('auctions.show', $model);
                        $editRoute = $item['kind'] === 'rfq' ? route('rfqs.edit', $model) : route('auctions.edit', $model);

                        $statusColors = [
                            'active' => 'bg-green-100 text-green-800',
                            'trading' => 'bg-emerald-100 text-emerald-800',
                            'closed' => 'bg-gray-100 text-gray-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            'draft' => 'bg-yellow-100 text-yellow-800',
                        ];
                        $statusLabels = [
                            'active' => 'Активный',
                            'trading' => 'Торги',
                            'closed' => 'Завершён',
                            'cancelled' => 'Отменён',
                            'draft' => 'Черновик',
                        ];
                    @endphp
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <!-- Информация -->
                                <div class="flex-1">
                                    <div class="flex items-center flex-wrap gap-2 mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['kind'] === 'rfq' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $item['kind'] === 'rfq' ? 'Запрос цен' : 'Аукцион' }}
                                        </span>
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <a href="{{ $showRoute }}" class="hover:text-emerald-600 transition">
                                                {{ $model->title }}
                                            </a>
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$model->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$model->status] ?? $model->status }}
                                        </span>
                                    </div>

                                    <p class="text-sm text-gray-500 mb-3">{{ $model->number }} &bull; {{ $model->company->name }}</p>

                                    <div class="flex items-center space-x-6 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>До {{ $model->end_date->format('d.m.Y H:i') }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <span>Заявок: {{ $model->bids->count() }}</span>
                                        </div>
                                        @if($model->status === 'closed' && $model->winnerBid)
                                            <div class="flex items-center text-green-600 font-semibold">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Победитель: {{ $model->winnerBid->company->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Кнопки действий -->
                                <div class="flex space-x-2 ml-4">
                                    <a href="{{ $showRoute }}"
                                       class="inline-flex items-center px-3 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                        Просмотр
                                    </a>
                                    @if($model->status === 'draft')
                                        <a href="{{ $editRoute }}"
                                           class="inline-flex items-center px-3 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                                            Редактировать
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $items->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

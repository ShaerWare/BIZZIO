@extends('layouts.app')

@section('title', 'Мои приглашения')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Мои приглашения</h1>
            <p class="mt-2 text-sm text-gray-600">Приглашения на участие в закрытых запросах котировок</p>
        </div>

        <!-- Список приглашений -->
        @if($invitations->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Нет приглашений</h3>
                    <p class="mt-1 text-sm text-gray-500">Вы не получали приглашения на участие в закрытых процедурах</p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($invitations as $invitation)
                    @php
                        $rfq = $invitation->rfq;
                        $hasAlreadyBid = $rfq->bids()->where('company_id', $invitation->company_id)->exists();
                        $canBidNow = $rfq->isActive() && !$rfq->isExpired() && !$hasAlreadyBid;
                    @endphp

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <!-- Информация о RFQ -->
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <a href="{{ route('rfqs.show', $rfq) }}" class="hover:text-indigo-600 transition">
                                                {{ $rfq->number }} — {{ $rfq->title }}
                                            </a>
                                        </h3>
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                'active' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-blue-100 text-blue-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusLabels = [
                                                'draft' => 'Черновик',
                                                'active' => 'Активный',
                                                'completed' => 'Завершён',
                                                'cancelled' => 'Отменён',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$rfq->status] }}">
                                            {{ $statusLabels[$rfq->status] }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Закрытая процедура
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-3">
                                        Организатор: <strong>{{ $rfq->company->name }}</strong>
                                    </p>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        Ваша компания: <strong>{{ $invitation->company->name }}</strong>
                                    </p>
                                    
                                    @if($rfq->description)
                                        <p class="text-sm text-gray-700 mb-3">{{ Str::limit($rfq->description, 150) }}</p>
                                    @endif
                                    
                                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Приём до: {{ $rfq->deadline->format('d.m.Y H:i') }}
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            Заявок: {{ $rfq->bids->count() }}
                                        </div>
                                    </div>
                                    
                                    @if($hasAlreadyBid)
                                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
                                            <div class="flex items-center text-blue-800">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-sm font-medium">Вы уже подали заявку</span>
                                            </div>
                                        </div>
                                    @elseif(!$canBidNow)
                                        <div class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded">
                                            <p class="text-sm text-gray-600">
                                                @if($rfq->isExpired())
                                                    Срок подачи заявок истёк
                                                @elseif($rfq->status === 'completed')
                                                    Запрос завершён
                                                @elseif($rfq->status === 'cancelled')
                                                    Запрос отменён
                                                @else
                                                    Подача заявок недоступна
                                                @endif
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Кнопки действий -->
                                <div class="ml-4 flex flex-col space-y-2">
                                    <a href="{{ route('rfqs.show', $rfq) }}" 
                                       class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                        Просмотр
                                    </a>
                                    
                                    @if($canBidNow)
                                        <a href="{{ route('rfqs.show', $rfq) }}#bid-form" 
                                           class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                            Подать заявку
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
                {{ $invitations->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
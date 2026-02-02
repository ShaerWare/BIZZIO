@extends('layouts.app')

@section('title', 'Мои приглашения')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Мои приглашения</h1>
            <p class="mt-2 text-sm text-gray-600">Приглашения на участие в закрытых тендерах</p>
        </div>

        <!-- Список приглашений -->
        @if($invitations->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">У вас пока нет приглашений</h3>
                    <p class="mt-1 text-sm text-gray-500">Когда вас пригласят на участие в закрытом тендере, приглашения появятся здесь</p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($invitations as $invitation)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300 {{ $invitation->rfq->isActive() ? 'border-l-4 border-emerald-500' : '' }}">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <!-- Информация -->
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <a href="{{ route('rfqs.show', $invitation->rfq) }}" class="hover:text-emerald-600 transition">
                                                {{ $invitation->rfq->title }}
                                            </a>
                                        </h3>
                                        @if($invitation->rfq->isActive())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse">
                                                Активный
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Завершён
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <p class="text-sm text-gray-500 mb-3">
                                        {{ $invitation->rfq->number }} • Организатор: {{ $invitation->rfq->company->name }}
                                    </p>
                                    
                                    <div class="flex items-center space-x-6 text-sm text-gray-600 mb-3">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>Окончание: {{ $invitation->rfq->end_date->format('d.m.Y H:i') }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>
                                                @if($invitation->rfq->isActive())
                                                    Осталось: {{ $invitation->rfq->end_date->diffForHumans() }}
                                                @else
                                                    Завершён
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <div class="text-sm">
                                        <span class="text-gray-600">Ваша компания:</span>
                                        <span class="font-semibold text-gray-900">{{ $invitation->company->name }}</span>
                                    </div>

                                    @if($invitation->rfq->bids()->where('company_id', $invitation->company_id)->exists())
                                        <div class="mt-3 flex items-center text-green-600">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-semibold">Заявка подана</span>
                                        </div>
                                    @elseif($invitation->rfq->isActive())
                                        <div class="mt-3 flex items-center text-yellow-600">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            <span class="text-sm font-semibold">Ожидает вашей заявки</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Кнопка -->
                                <div class="ml-4">
                                    <a href="{{ route('rfqs.show', $invitation->rfq) }}" 
                                       class="inline-flex items-center px-4 py-2 {{ $invitation->rfq->isActive() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-600 hover:bg-gray-700' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition">
                                        {{ $invitation->rfq->isActive() ? 'Подать заявку' : 'Просмотр' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 text-xs text-gray-500">
                            Приглашение получено: {{ $invitation->created_at->format('d.m.Y H:i') }}
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
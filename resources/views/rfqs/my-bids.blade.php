@extends('layouts.app')

@section('title', 'Мои заявки')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Мои заявки</h1>
            <p class="mt-2 text-sm text-gray-600">Заявки, поданные вашими компаниями</p>
        </div>

        <!-- Список заявок -->
        @if($bids->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">У вас пока нет заявок</h3>
                    <p class="mt-1 text-sm text-gray-500">Найдите подходящий тендер и подайте заявку</p>
                    <div class="mt-6">
                        <a href="{{ route('rfqs.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            Найти тендеры
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($bids as $bid)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <!-- Информация -->
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <a href="{{ route('rfqs.show', $bid->rfq) }}" class="hover:text-emerald-600 transition">
                                                {{ $bid->rfq->title }}
                                            </a>
                                        </h3>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'accepted' => 'bg-emerald-100 text-emerald-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'winner' => 'bg-green-100 text-green-800',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'На рассмотрении',
                                                'accepted' => 'Принята',
                                                'rejected' => 'Отклонена',
                                                'winner' => 'Победитель',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$bid->status] }}">
                                            {{ $statusLabels[$bid->status] }}
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-gray-500 mb-3">
                                        {{ $bid->rfq->number }} • Организатор: {{ $bid->rfq->company->name }}
                                    </p>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
                                        <div>
                                            <p class="text-xs text-gray-500">Ваша компания</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ $bid->company->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Цена</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ number_format($bid->price, 0, ',', ' ') }} {{ $bid->rfq->currency_symbol }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Срок</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ $bid->deadline }} дней</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Аванс</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ $bid->advance_percent }}%</p>
                                        </div>
                                    </div>

                                    @if($bid->rfq->status === 'closed' && $bid->total_score > 0)
                                        <div class="flex items-center space-x-6 text-sm">
                                            <div>
                                                <span class="text-gray-600">Итоговый балл:</span>
                                                <span class="font-semibold text-gray-900 ml-1">{{ number_format($bid->total_score, 2, ',', ' ') }}</span>
                                            </div>
                                            @if($bid->status === 'winner')
                                                <div class="flex items-center text-green-600 font-semibold">
                                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span>Вы победили!</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Кнопка -->
                                <div class="ml-4">
                                    <a href="{{ route('rfqs.show', $bid->rfq) }}" 
                                       class="inline-flex items-center px-3 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                        Просмотр тендера
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 text-xs text-gray-500">
                            Заявка подана: {{ $bid->created_at->format('d.m.Y H:i') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $bids->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
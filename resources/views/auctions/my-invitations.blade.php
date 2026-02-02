@extends('layouts.app')

@section('title', 'Мои приглашения в закрытые аукционы')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Мои приглашения в закрытые аукционы</h1>
            <p class="mt-1 text-sm text-gray-500">Аукционы, куда вас пригласили для участия</p>
        </div>

        @if($invitations->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                    </svg>
                    <p class="mt-4 text-gray-500">У вас пока нет приглашений в закрытые аукционы.</p>
                </div>
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Аукцион
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Организатор
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Компания
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Статус аукциона
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Действия
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invitations as $invitation)
                                    @php
                                        $auction = $invitation->auction;
                                        // Проверка: уже подана заявка?
                                        $existingBid = $auction->bids()
                                            ->where('company_id', $invitation->company_id)
                                            ->first();
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="font-medium">{{ $auction->number }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($auction->title, 40) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $auction->company->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $invitation->company->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'active' => 'bg-green-100 text-green-800',
                                                    'trading' => 'bg-emerald-100 text-emerald-800',
                                                    'closed' => 'bg-gray-100 text-gray-800',
                                                    'draft' => 'bg-yellow-100 text-yellow-800',
                                                ];
                                                $statusLabels = [
                                                    'active' => 'Приём заявок',
                                                    'trading' => 'Торги',
                                                    'closed' => 'Завершён',
                                                    'draft' => 'Черновик',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$auction->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $statusLabels[$auction->status] ?? ucfirst($auction->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($existingBid)
                                                <span class="text-green-600">✓ Заявка подана</span>
                                            @elseif($auction->isAcceptingApplications() || $auction->isTrading())
                                                <a href="{{ route('auctions.show', $auction) }}#bid-form" 
                                                   class="text-emerald-600 hover:text-emerald-900">
                                                    Подать заявку
                                                </a>
                                            @else
                                                <a href="{{ route('auctions.show', $auction) }}" 
                                                   class="text-gray-600 hover:text-gray-900">
                                                    Просмотр
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <div class="mt-6">
                        {{ $invitations->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
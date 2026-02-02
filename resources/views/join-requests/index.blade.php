@extends('layouts.app')

@section('title', 'Мои запросы на присоединение')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Мои запросы на присоединение</h1>
            <p class="mt-2 text-sm text-gray-600">Ваши запросы на присоединение к компаниям</p>
        </div>

        <!-- Список запросов -->
        @if($requests->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Нет запросов</h3>
                    <p class="mt-1 text-sm text-gray-500">Вы ещё не отправляли запросы на присоединение к компаниям</p>
                    <div class="mt-6">
                        <a href="{{ route('companies.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            Найти компанию
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($requests as $request)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <!-- Информация -->
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            <a href="{{ route('companies.show', $request->company) }}" class="hover:text-emerald-600 transition">
                                                {{ $request->company->name }}
                                            </a>
                                        </h3>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'approved' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'На рассмотрении',
                                                'approved' => 'Одобрен',
                                                'rejected' => 'Отклонён',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$request->status] }}">
                                            {{ $statusLabels[$request->status] }}
                                        </span>
                                    </div>
                                    
                                    @if($request->company->industry)
                                        <p class="text-sm text-gray-500 mb-3">{{ $request->company->industry->name }}</p>
                                    @endif
                                    
                                    @if($request->desired_role)
                                        <div class="mb-2">
                                            <span class="text-xs text-gray-500">Желаемая роль:</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 ml-1">
                                                {{ $request->desired_role }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if($request->message)
                                        <div class="mb-3 p-3 bg-gray-50 rounded">
                                            <p class="text-sm text-gray-700">{{ $request->message }}</p>
                                        </div>
                                    @endif
                                    
                                    <div class="text-xs text-gray-500">
                                        <p>Отправлен: {{ $request->created_at->format('d.m.Y H:i') }}</p>
                                        @if($request->reviewed_at)
                                            <p>Рассмотрен: {{ $request->reviewed_at->format('d.m.Y H:i') }}</p>
                                        @endif
                                    </div>
                                    
                                    @if($request->status === 'approved')
                                        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
                                            <div class="flex items-center text-green-800">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-sm font-medium">Вы добавлены как модератор компании!</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($request->status === 'rejected' && $request->review_comment)
                                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                                            <p class="text-sm text-red-800">
                                                <strong>Причина отклонения:</strong><br>
                                                {{ $request->review_comment }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Кнопки действий -->
                                <div class="ml-4">
                                    @if($request->status === 'pending')
                                        <form method="POST" action="{{ route('join-requests.destroy', $request) }}"
                                              onsubmit="return confirm('Отозвать запрос?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                                Отозвать
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('companies.show', $request->company) }}" 
                                           class="inline-flex items-center px-3 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                            Просмотр компании
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
                {{ $requests->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
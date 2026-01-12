<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Основная колонка: Лента активности -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                {{ __('Лента активности') }}
                            </h3>

                            <div id="activity-feed" class="space-y-4">
                                @include('partials.activity-items', ['activities' => $activities])
                            </div>

                            @if($activities->count() >= 20)
                                <div class="mt-6 text-center">
                                    <button
                                        id="load-more-btn"
                                        data-offset="20"
                                        class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:bg-gray-200 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <span id="load-more-text">{{ __('Загрузить ещё') }}</span>
                                        <svg id="load-more-spinner" class="hidden animate-spin ml-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Боковая колонка: Уведомления -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ __('Уведомления') }}
                                </h3>
                                @if($unreadNotificationsCount > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $unreadNotificationsCount }}
                                    </span>
                                @endif
                            </div>

                            @php
                                $notifications = auth()->user()->notifications()->latest()->take(5)->get();
                            @endphp

                            @if($notifications->isEmpty())
                                <p class="text-gray-500 text-sm">{{ __('Нет уведомлений') }}</p>
                            @else
                                <div class="space-y-3">
                                    @foreach($notifications as $notification)
                                        <div class="flex items-start space-x-3 p-3 rounded-lg {{ $notification->read_at ? 'bg-gray-50' : 'bg-blue-50' }}">
                                            <div class="flex-shrink-0">
                                                @switch($notification->data['type'] ?? '')
                                                    @case('project_invitation')
                                                        <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                                        </svg>
                                                        @break
                                                    @case('tender_invitation')
                                                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                        </svg>
                                                        @break
                                                    @case('tender_closed')
                                                        <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        @break
                                                    @case('new_comment')
                                                        <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                                        </svg>
                                                        @break
                                                    @default
                                                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                        </svg>
                                                @endswitch
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                @if(isset($notification->data['url']))
                                                    <a href="{{ $notification->data['url'] }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                                        @include('partials.notification-text', ['notification' => $notification])
                                                    </a>
                                                @else
                                                    <p class="text-sm font-medium text-gray-900">
                                                        @include('partials.notification-text', ['notification' => $notification])
                                                    </p>
                                                @endif
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($unreadNotificationsCount > 0)
                                    <div class="mt-4">
                                        <button
                                            id="mark-all-read-btn"
                                            class="text-sm text-indigo-600 hover:text-indigo-800"
                                        >
                                            {{ __('Отметить все как прочитанные') }}
                                        </button>
                                    </div>
                                @endif

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <a href="{{ route('notifications.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                        {{ __('Все уведомления') }} &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Загрузить ещё активности
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const offset = parseInt(this.dataset.offset);
                    const spinner = document.getElementById('load-more-spinner');
                    const text = document.getElementById('load-more-text');

                    spinner.classList.remove('hidden');
                    text.textContent = 'Загрузка...';

                    fetch(`{{ route('dashboard.activities') }}?offset=${offset}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        if (html.trim()) {
                            document.getElementById('activity-feed').insertAdjacentHTML('beforeend', html);
                            loadMoreBtn.dataset.offset = offset + 10;
                        } else {
                            loadMoreBtn.remove();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        spinner.classList.add('hidden');
                        text.textContent = 'Загрузить ещё';
                    });
                });
            }

            // Отметить все уведомления как прочитанные
            const markAllReadBtn = document.getElementById('mark-all-read-btn');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    fetch('{{ route('notifications.read-all') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Обновить UI
                            document.querySelectorAll('.bg-blue-50').forEach(el => {
                                el.classList.remove('bg-blue-50');
                                el.classList.add('bg-gray-50');
                            });
                            // Убрать бейдж и кнопку
                            const badge = document.querySelector('.bg-red-100');
                            if (badge) badge.remove();
                            markAllReadBtn.remove();
                            // Обновить бейдж в навигации
                            updateNotificationBadge(0);
                        }
                    });
                });
            }
        });

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }
    </script>
    @endpush
</x-app-layout>

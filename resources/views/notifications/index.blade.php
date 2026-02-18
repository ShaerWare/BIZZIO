<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Уведомления') }}
            </h2>
            @if(auth()->user()->unreadNotifications()->count() > 0)
                <button
                    id="mark-all-read-btn"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-emerald-700 bg-emerald-100 hover:bg-emerald-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                >
                    {{ __('Отметить все как прочитанные') }}
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($notifications->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('Нет уведомлений') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Когда появятся уведомления, они отобразятся здесь') }}</p>
                        </div>
                    @else
                        <div class="space-y-4" id="notifications-list">
                            @foreach($notifications as $notification)
                                <div
                                    class="notification-item flex items-start space-x-4 p-4 rounded-lg border {{ $notification->read_at ? 'bg-gray-50 border-gray-200' : 'bg-emerald-50 border-emerald-200' }}"
                                    data-id="{{ $notification->id }}"
                                >
                                    <div class="flex-shrink-0">
                                        @switch($notification->data['type'] ?? '')
                                            @case('project_invitation')
                                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                                    </svg>
                                                </div>
                                                @break
                                            @case('tender_invitation')
                                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                </div>
                                                @break
                                            @case('tender_closed')
                                                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                @break
                                            @case('new_comment')
                                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                                    </svg>
                                                </div>
                                                @break
                                            @case('auction_trading_started')
                                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                </div>
                                                @break
                                            @case('join_request')
                                                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                    </svg>
                                                </div>
                                                @break
                                            @default
                                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                    </svg>
                                                </div>
                                        @endswitch
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900">
                                            @if(isset($notification->data['url']))
                                                <a href="{{ $notification->data['url'] }}" class="hover:text-emerald-600">
                                                    @include('partials.notification-text', ['notification' => $notification])
                                                </a>
                                            @else
                                                @include('partials.notification-text', ['notification' => $notification])
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $notification->created_at->format('d.m.Y H:i') }} ({{ $notification->created_at->diffForHumans() }})
                                        </p>
                                    </div>

                                    <div class="flex-shrink-0">
                                        @if(!$notification->read_at)
                                            <button
                                                class="mark-read-btn text-xs text-emerald-600 hover:text-emerald-800"
                                                data-id="{{ $notification->id }}"
                                            >
                                                {{ __('Прочитано') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">{{ __('Прочитано') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Отметить одно уведомление как прочитанное
            document.querySelectorAll('.mark-read-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);

                    fetch(`/notifications/${id}/read`, {
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
                            item.classList.remove('bg-emerald-50', 'border-emerald-200');
                            item.classList.add('bg-gray-50', 'border-gray-200');
                            this.outerHTML = '<span class="text-xs text-gray-400">Прочитано</span>';
                            updateNotificationBadge(data.unreadCount);
                        }
                    });
                });
            });

            // Отметить все как прочитанные
            const markAllBtn = document.getElementById('mark-all-read-btn');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', function() {
                    fetch('/notifications/read-all', {
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
                            document.querySelectorAll('.notification-item').forEach(item => {
                                item.classList.remove('bg-emerald-50', 'border-emerald-200');
                                item.classList.add('bg-gray-50', 'border-gray-200');
                            });
                            document.querySelectorAll('.mark-read-btn').forEach(btn => {
                                btn.outerHTML = '<span class="text-xs text-gray-400">Прочитано</span>';
                            });
                            markAllBtn.remove();
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

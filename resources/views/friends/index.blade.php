<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Друзья
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('info'))
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Tabs --}}
            <div x-data="{ tab: '{{ $tab }}' }">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px overflow-x-auto">
                            <button @click="tab = 'friends'"
                                    :class="tab === 'friends' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition whitespace-nowrap">
                                Друзья
                                <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $friends->total() }}</span>
                            </button>
                            <button @click="tab = 'incoming'"
                                    :class="tab === 'incoming' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition whitespace-nowrap">
                                Входящие
                                @if($incoming->count() > 0)
                                    <span class="ml-1 text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">{{ $incoming->count() }}</span>
                                @endif
                            </button>
                            <button @click="tab = 'outgoing'"
                                    :class="tab === 'outgoing' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition whitespace-nowrap">
                                Отправленные
                                @if($outgoing->count() > 0)
                                    <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $outgoing->count() }}</span>
                                @endif
                            </button>
                            <button @click="tab = 'suggestions'"
                                    :class="tab === 'suggestions' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition whitespace-nowrap">
                                Рекомендации
                                @if($friendsOfFriends->count() > 0)
                                    <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $friendsOfFriends->count() }}</span>
                                @endif
                            </button>
                        </nav>
                    </div>

                    {{-- Friends Tab --}}
                    <div x-show="tab === 'friends'" class="p-6">
                        {{-- Search friends --}}
                        <form action="{{ route('friends.index') }}" method="GET" class="mb-4">
                            <input type="hidden" name="tab" value="friends">
                            <div class="relative">
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Поиск среди друзей..."
                                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                @if($search)
                                    <a href="{{ route('friends.index', ['tab' => 'friends']) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </form>

                        @if($friends->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($friends as $friend)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                        <a href="{{ route('users.show', $friend) }}" class="flex items-center space-x-3 flex-1 min-w-0">
                                            <img src="{{ $friend->avatar_url }}" alt=""
                                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $friend->name }}</p>
                                                @if($friend->position)
                                                    <p class="text-xs text-gray-500 truncate">{{ $friend->position }}</p>
                                                @endif
                                            </div>
                                        </a>
                                        <form action="{{ route('friends.remove', $friend) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition">
                                                Удалить
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4">
                                {{ $friends->appends(['tab' => 'friends', 'search' => $search])->links() }}
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-8">
                                @if($search)
                                    По запросу «{{ $search }}» ничего не найдено.
                                @else
                                    У вас пока нет друзей. Добавьте пользователей через их профили или раздел «Рекомендации».
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Incoming Requests Tab --}}
                    <div x-show="tab === 'incoming'" class="p-6">
                        @if($incoming->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($incoming as $request)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                        <a href="{{ route('users.show', $request->sender) }}" class="flex items-center space-x-3 flex-1 min-w-0">
                                            <img src="{{ $request->sender->avatar_url }}" alt=""
                                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $request->sender->name }}</p>
                                                @if($request->sender->position)
                                                    <p class="text-xs text-gray-500 truncate">{{ $request->sender->position }}</p>
                                                @endif
                                                <p class="text-xs text-gray-400">{{ $request->created_at->diffForHumans() }}</p>
                                            </div>
                                        </a>
                                        <div class="flex items-center space-x-2">
                                            <form action="{{ route('friends.accept', $request->sender) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                                    Принять
                                                </button>
                                            </form>
                                            <form action="{{ route('friends.remove', $request->sender) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition">
                                                    Отклонить
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-8">
                                Нет входящих заявок в друзья.
                            </p>
                        @endif
                    </div>

                    {{-- Outgoing Requests Tab --}}
                    <div x-show="tab === 'outgoing'" class="p-6">
                        @if($outgoing->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($outgoing as $request)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                        <a href="{{ route('users.show', $request->receiver) }}" class="flex items-center space-x-3 flex-1 min-w-0">
                                            <img src="{{ $request->receiver->avatar_url }}" alt=""
                                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $request->receiver->name }}</p>
                                                @if($request->receiver->position)
                                                    <p class="text-xs text-gray-500 truncate">{{ $request->receiver->position }}</p>
                                                @endif
                                                <p class="text-xs text-gray-400">{{ $request->created_at->diffForHumans() }}</p>
                                            </div>
                                        </a>
                                        <form action="{{ route('friends.remove', $request->receiver) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition">
                                                Отменить
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-8">
                                Нет отправленных заявок.
                            </p>
                        @endif
                    </div>

                    {{-- Friends of Friends (Suggestions) Tab --}}
                    <div x-show="tab === 'suggestions'" class="p-6">
                        @if($friendsOfFriends->isNotEmpty())
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($friendsOfFriends as $suggested)
                                    <div class="p-4 rounded-lg bg-gray-50 text-center">
                                        <a href="{{ route('users.show', $suggested) }}" class="block">
                                            <img src="{{ $suggested->avatar_url }}" alt=""
                                                 class="w-16 h-16 rounded-full object-cover mx-auto mb-2">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $suggested->name }}</p>
                                            @if($suggested->position)
                                                <p class="text-xs text-gray-500 truncate mb-1">{{ $suggested->position }}</p>
                                            @endif
                                            @php
                                                $mutual = auth()->user()->mutualFriendsCount($suggested);
                                            @endphp
                                            @if($mutual > 0)
                                                <p class="text-xs text-emerald-600 mb-2">{{ $mutual }} {{ trans_choice('общий друг|общих друга|общих друзей', $mutual) }}</p>
                                            @endif
                                        </a>
                                        <form action="{{ route('friends.request', $suggested) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition w-full justify-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                </svg>
                                                Добавить
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-8">
                                Пока нет рекомендаций. Добавьте друзей, и мы покажем их друзей здесь.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

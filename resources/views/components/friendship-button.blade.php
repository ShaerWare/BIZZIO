@auth
    @php
        $isSelf = auth()->id() === $user->id;
        $status = $isSelf ? null : auth()->user()->friendshipStatusWith($user);
    @endphp

    @unless($isSelf)
        <div x-data="{ loading: false }">
            @if($status === 'accepted')
                <form action="{{ route('friends.remove', $user) }}" method="POST" class="inline"
                      @submit="loading = true">
                    @csrf
                    @method('DELETE')
                    <button type="submit" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition group">
                        <svg class="w-4 h-4 mr-2 text-emerald-500 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="group-hover:hidden">В друзьях</span>
                        <span class="hidden group-hover:inline">Удалить</span>
                    </button>
                </form>

            @elseif($status === 'pending_sent')
                <form action="{{ route('friends.remove', $user) }}" method="POST" class="inline"
                      @submit="loading = true">
                    @csrf
                    @method('DELETE')
                    <button type="submit" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 hover:border-red-300 transition group">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="group-hover:hidden">Заявка отправлена</span>
                        <span class="hidden group-hover:inline">Отменить</span>
                    </button>
                </form>

            @elseif($status === 'pending_received')
                <div class="inline-flex items-center space-x-2">
                    <form action="{{ route('friends.accept', $user) }}" method="POST" class="inline"
                          @submit="loading = true">
                        @csrf
                        <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Принять
                        </button>
                    </form>
                    <form action="{{ route('friends.remove', $user) }}" method="POST" class="inline"
                          @submit="loading = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-3 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </form>
                </div>

            @else
                <form action="{{ route('friends.request', $user) }}" method="POST" class="inline"
                      @submit="loading = true">
                    @csrf
                    <button type="submit" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Добавить в друзья
                    </button>
                </form>
            @endif
        </div>
    @endunless
@endauth

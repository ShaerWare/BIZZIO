@auth
    @php
        $isSubscribed = auth()->user()->isSubscribedTo($target);
        if ($target instanceof \App\Models\User) {
            $storeRoute = route('subscriptions.users.store', $target);
            $destroyRoute = route('subscriptions.users.destroy', $target);
            $isSelf = auth()->id() === $target->id;
        } else {
            $storeRoute = route('subscriptions.companies.store', $target);
            $destroyRoute = route('subscriptions.companies.destroy', $target);
            $isSelf = false;
        }
    @endphp

    @unless($isSelf)
        @if($isSubscribed)
            <form action="{{ $destroyRoute }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition group">
                    <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                    </svg>
                    <span class="group-hover:hidden">Подписка</span>
                    <span class="hidden group-hover:inline">Отписаться</span>
                </button>
            </form>
        @else
            <form action="{{ $storeRoute }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Подписаться
                </button>
            </form>
        @endif
    @endunless
@endauth

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Мои подписки') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Подписки на пользователей -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ __('Пользователи') }}
                        <span class="text-sm font-normal text-gray-500">({{ $userSubscriptions->count() }})</span>
                    </h3>

                    @if($userSubscriptions->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($userSubscriptions as $subscription)
                                @if($subscription->subscribable)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                        <a href="{{ route('users.show', $subscription->subscribable) }}" class="flex items-center space-x-3 flex-1 min-w-0">
                                            <img src="{{ $subscription->subscribable->avatar_url }}" alt=""
                                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $subscription->subscribable->name }}</p>
                                                @if($subscription->subscribable->position)
                                                    <p class="text-xs text-gray-500 truncate">{{ $subscription->subscribable->position }}</p>
                                                @endif
                                            </div>
                                        </a>
                                        <form action="{{ route('subscriptions.users.destroy', $subscription->subscribable) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition">
                                                {{ __('Отписаться') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">{{ __('Вы пока не подписаны на пользователей.') }}</p>
                    @endif
                </div>
            </div>

            <!-- Подписки на компании -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ __('Компании') }}
                        <span class="text-sm font-normal text-gray-500">({{ $companySubscriptions->count() }})</span>
                    </h3>

                    @if($companySubscriptions->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($companySubscriptions as $subscription)
                                @if($subscription->subscribable)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                        <a href="{{ route('companies.show', $subscription->subscribable) }}" class="flex items-center space-x-3 flex-1 min-w-0">
                                            @if($subscription->subscribable->logo)
                                                <img src="{{ Storage::url($subscription->subscribable->logo) }}" alt="{{ $subscription->subscribable->name }}"
                                                     class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                            @else
                                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <span class="text-sm font-bold text-white">{{ mb_substr($subscription->subscribable->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $subscription->subscribable->name }}</p>
                                                    @if($subscription->subscribable->is_verified)
                                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @endif
                                                </div>
                                                @if($subscription->subscribable->short_description)
                                                    <p class="text-xs text-gray-500 truncate">{{ $subscription->subscribable->short_description }}</p>
                                                @endif
                                            </div>
                                        </a>
                                        <form action="{{ route('subscriptions.companies.destroy', $subscription->subscribable) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-red-100 hover:text-red-700 transition">
                                                {{ __('Отписаться') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">{{ __('Вы пока не подписаны на компании.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

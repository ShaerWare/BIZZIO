@if($recentPosts->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Посты') }}</h3>
            <a href="{{ route('subscriptions.index') }}" class="text-sm text-emerald-600 hover:text-emerald-800">
                {{ __('Мои подписки') }}
            </a>
        </div>
        <div class="space-y-4">
            @foreach($recentPosts as $post)
                <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50">
                    <a href="{{ route('users.show', $post->user) }}">
                        <img src="{{ $post->user->avatar_url }}" alt=""
                             class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                    </a>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('users.show', $post->user) }}" class="text-sm font-medium text-gray-900 hover:text-emerald-600">
                                {{ $post->user->name }}
                            </a>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">{{ $post->created_at->diffForHumans() }}</span>
                                @if($post->user_id === auth()->id())
                                    <form action="{{ route('posts.destroy', $post) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Удалить пост?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-500" title="{{ __('Удалить') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $post->body }}</p>
                        @if($post->getFirstMediaUrl('photos'))
                            <img src="{{ $post->getFirstMediaUrl('photos') }}" alt=""
                                 class="mt-2 rounded-lg max-h-64 object-cover">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@else
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="text-center py-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('Подпишитесь на коллег и компании') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('Чтобы видеть посты в ленте, подпишитесь на интересных вам пользователей и компании.') }}</p>
        </div>

        @if(isset($recommendedCompanies) && $recommendedCompanies->isNotEmpty())
            <div class="mt-4 border-t border-gray-200 pt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ __('Рекомендуемые компании') }}</h4>
                <div class="space-y-3">
                    @foreach($recommendedCompanies as $company)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                            <a href="{{ route('companies.show', $company) }}" class="flex items-center space-x-3 flex-1 min-w-0">
                                @if($company->logo)
                                    <img src="{{ Storage::url($company->logo) }}" alt="{{ $company->name }}"
                                         class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                @else
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-white">{{ mb_substr($company->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $company->name }}</p>
                                    @if($company->short_description)
                                        <p class="text-xs text-gray-500 truncate">{{ $company->short_description }}</p>
                                    @endif
                                </div>
                            </a>
                            @include('components.subscribe-button', ['target' => $company])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endif

@if($recentPosts->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Посты') }}</h3>
        <div class="space-y-4">
            @foreach($recentPosts as $post)
                <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50">
                    <img src="{{ $post->user->avatar_url }}" alt=""
                         class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">{{ $post->user->name }}</span>
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
@endif

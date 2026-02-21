@if($latestNews->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Новости') }}</h3>
            <a href="{{ route('news.index') }}" class="text-sm text-emerald-600 hover:text-emerald-800">
                {{ __('Все новости') }} &rarr;
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($latestNews as $news)
                <a href="{{ $news->link }}" target="_blank" rel="noopener"
                   class="block group">
                    @if($news->image)
                        <img src="{{ $news->image }}" alt=""
                             class="w-full h-32 object-cover rounded-lg mb-2">
                    @else
                        <div class="w-full h-32 bg-gray-100 rounded-lg mb-2 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                    @endif
                    <h4 class="text-sm font-medium text-gray-900 group-hover:text-emerald-600 line-clamp-2">
                        {{ $news->title }}
                    </h4>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $news->published_at?->diffForHumans() }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($latestNews->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Новости') }}</h3>
            <a href="{{ route('news.index') }}" class="text-sm text-emerald-600 hover:text-emerald-800">
                {{ __('Все новости') }} &rarr;
            </a>
        </div>
        {{-- #140: новости показываем списком заголовков, без картинок --}}
        <ul class="divide-y divide-gray-100">
            @foreach($latestNews as $news)
                <li>
                    <a href="{{ $news->link }}" target="_blank" rel="noopener"
                       class="block group py-2">
                        <h4 class="text-sm font-medium text-gray-900 group-hover:text-emerald-600 line-clamp-2">
                            {{ $news->title }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $news->published_at?->diffForHumans() }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

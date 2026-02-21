<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Мои закупки') }}</h4>
        @if($myTenders->isNotEmpty())
            <ul class="space-y-2">
                @foreach($myTenders as $tender)
                    <li class="text-sm">
                        <a href="{{ $tender['url'] }}" class="text-gray-700 hover:text-emerald-600 block truncate">
                            {{ $tender['title'] ?: $tender['number'] }}
                        </a>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <span class="text-xs px-1.5 py-0.5 rounded {{ $tender['type'] === 'rfq' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $tender['type'] === 'rfq' ? 'ЗЦ' : 'Аукцион' }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $tender['status'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-xs text-gray-500">{{ __('Нет закупок') }}</p>
        @endif
        <a href="{{ route('tenders.my') }}" class="mt-2 inline-block text-xs text-emerald-600 hover:text-emerald-800">
            {{ __('Все закупки') }} &rarr;
        </a>
    </div>
</div>

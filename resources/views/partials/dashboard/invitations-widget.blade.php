<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Приглашения') }}</h4>
        @if($myInvitations->isNotEmpty())
            <ul class="space-y-2">
                @foreach($myInvitations as $inv)
                    <li class="text-sm">
                        <a href="{{ $inv['url'] }}" class="text-gray-700 hover:text-emerald-600 block truncate">
                            {{ $inv['title'] ?: $inv['number'] }}
                        </a>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <span class="text-xs px-1.5 py-0.5 rounded {{ $inv['type'] === 'rfq' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $inv['type'] === 'rfq' ? 'ЗЦ' : 'Аукцион' }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $inv['status'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-xs text-gray-500">{{ __('Нет приглашений') }}</p>
        @endif
        <a href="{{ route('tenders.invitations.my') }}" class="mt-2 inline-block text-xs text-emerald-600 hover:text-emerald-800">
            {{ __('Все приглашения') }} &rarr;
        </a>
    </div>
</div>

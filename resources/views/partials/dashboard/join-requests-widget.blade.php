@if($pendingJoinRequests->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Заявки на вступление') }}</h4>
        @foreach($pendingJoinRequests as $request)
            <div class="flex items-center space-x-2 text-sm">
                <span class="inline-block w-2 h-2 bg-yellow-400 rounded-full flex-shrink-0"></span>
                <span class="text-gray-700 truncate">{{ $request->company->name ?? '' }}</span>
            </div>
        @endforeach
        <a href="{{ route('join-requests.index') }}"
           class="mt-2 inline-block text-xs text-emerald-600 hover:text-emerald-800">
            {{ __('Все заявки') }} &rarr;
        </a>
    </div>
</div>
@endif

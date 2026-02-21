<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <div class="flex flex-col items-center text-center">
            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                 class="w-16 h-16 rounded-full object-cover mb-3">
            <h3 class="text-sm font-semibold text-gray-900 truncate w-full">{{ auth()->user()->name }}</h3>
            @if(auth()->user()->position)
                <p class="text-xs text-gray-500 mt-0.5">{{ auth()->user()->position }}</p>
            @endif
            <a href="{{ route('profile.edit') }}"
               class="mt-2 text-xs text-emerald-600 hover:text-emerald-800">
                {{ __('Редактировать профиль') }}
            </a>
        </div>
    </div>
</div>

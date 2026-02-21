@if($userCompanies->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Мои компании') }}</h4>
        <ul class="space-y-2">
            @foreach($userCompanies as $company)
                <li class="flex items-center space-x-2 text-sm">
                    @if($company->is_verified)
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <span class="w-4 h-4 flex-shrink-0"></span>
                    @endif
                    <a href="{{ route('companies.show', $company) }}"
                       class="text-gray-700 hover:text-emerald-600 truncate">
                        {{ $company->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

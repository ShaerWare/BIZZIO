@props(['company'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
    <div class="p-6">
        <!-- Логотип и верификация -->
        <div class="flex items-start justify-between mb-4">
            @if($company->logo)
                <img src="{{ Storage::url($company->logo) }}" 
                     alt="{{ $company->name }}"
                     class="w-16 h-16 rounded-lg object-cover">
            @else
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-2xl font-bold text-white">
                        {{ substr($company->name, 0, 1) }}
                    </span>
                </div>
            @endif

            @if($company->is_verified)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Верифицирована
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    На проверке
                </span>
            @endif
        </div>

        <!-- Название -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
            <a href="{{ route('companies.show', $company) }}" class="hover:text-indigo-600 transition-colors">
                {{ $company->name }}
            </a>
        </h3>

        <!-- ИНН и форма -->
        <div class="flex items-center gap-3 mb-3 text-sm text-gray-500">
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                ИНН: {{ $company->inn }}
            </span>
            @if($company->legal_form)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $company->legal_form }}
                </span>
            @endif
        </div>

        <!-- Отрасль -->
        @if($company->industry)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                {{ $company->industry->name }}
            </span>
        @endif

        <!-- Краткое описание -->
        @if($company->short_description)
            <p class="mt-3 text-sm text-gray-600 line-clamp-2">
                {{ $company->short_description }}
            </p>
        @endif

        <!-- Футер карточки -->
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
            <div class="flex items-center text-xs text-gray-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ $company->creator->name }}
            </div>
            <a href="{{ route('companies.show', $company) }}" 
               class="text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center">
                Подробнее
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>
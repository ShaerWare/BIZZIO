<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
    <a href="{{ route('projects.show', $project->slug) }}" class="block">
        <!-- Аватар проекта -->
        @if($project->avatar)
            <img src="{{ $project->avatar_url }}" 
                 alt="{{ $project->name }}" 
                 class="w-full h-48 object-cover">
        @else
            <div class="w-full h-48 bg-gradient-to-br from-emerald-500 to-purple-600 flex items-center justify-center">
                <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        @endif
    </a>

    <div class="p-6">
        <!-- Название и статус -->
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-lg font-semibold text-gray-900 hover:text-emerald-600">
                <a href="{{ route('projects.show', $project->slug) }}">
                    {{ $project->name }}
                </a>
            </h3>
            
            @php
                $statusColors = [
                    'active' => 'bg-green-100 text-green-800',
                    'completed' => 'bg-emerald-100 text-emerald-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                ];
            @endphp
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$project->status] }}">
                {{ \App\Models\Project::getStatuses()[$project->status] }}
            </span>
        </div>

        <!-- Описание -->
        @if($project->description)
            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                {{ Str::limit(strip_tags($project->description), 120) }}
            </p>
        @endif

        <!-- Компания-заказчик -->
        <div class="flex items-center mb-3">
            @if($project->company->logo)
                <img src="{{ $project->company->logo_url }}" 
                     alt="{{ $project->company->name }}" 
                     class="w-8 h-8 rounded-full mr-2 object-cover">
            @else
                <div class="w-8 h-8 rounded-full mr-2 bg-gray-200 flex items-center justify-center">
                    <span class="text-xs text-gray-500 font-semibold">
                        {{ strtoupper(substr($project->company->name, 0, 2)) }}
                    </span>
                </div>
            @endif
            <div>
                <p class="text-xs text-gray-500">Заказчик</p>
                <p class="text-sm font-medium text-gray-900">{{ $project->company->name }}</p>
            </div>
        </div>

        <!-- Сроки -->
        <div class="flex items-center text-sm text-gray-500 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span>{{ $project->formatted_duration }}</span>
        </div>

        <!-- Участники -->
        @if($project->participants->isNotEmpty())
            <div class="flex items-center mb-4">
                <div class="flex -space-x-2 mr-2">
                    @foreach($project->participants->take(3) as $participant)
                        @if($participant->logo)
                            <img src="{{ $participant->logo_url }}" 
                                 alt="{{ $participant->name }}" 
                                 class="w-8 h-8 rounded-full border-2 border-white object-cover"
                                 title="{{ $participant->name }}">
                        @else
                            <div class="w-8 h-8 rounded-full border-2 border-white bg-gray-200 flex items-center justify-center"
                                 title="{{ $participant->name }}">
                                <span class="text-xs text-gray-500 font-semibold">
                                    {{ strtoupper(substr($participant->name, 0, 2)) }}
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
                <span class="text-sm text-gray-500">
                    {{ $project->participants->count() }} {{ Str::plural('участник', $project->participants->count()) }}
                </span>
            </div>
        @endif

        <!-- Кнопка "Подробнее" -->
        <a href="{{ route('projects.show', $project->slug) }}" 
           class="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-500">
            Подробнее
            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>
</div>
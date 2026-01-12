@forelse($activities as $activity)
    <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
        <!-- Иконка типа действия -->
        <div class="flex-shrink-0">
            @php
                $iconColor = 'text-gray-500';
                $bgColor = 'bg-gray-100';

                if ($activity->subject_type) {
                    $modelName = class_basename($activity->subject_type);
                    switch($modelName) {
                        case 'Company':
                            $iconColor = 'text-blue-500';
                            $bgColor = 'bg-blue-100';
                            break;
                        case 'Project':
                            $iconColor = 'text-green-500';
                            $bgColor = 'bg-green-100';
                            break;
                        case 'Rfq':
                            $iconColor = 'text-yellow-500';
                            $bgColor = 'bg-yellow-100';
                            break;
                        case 'Auction':
                            $iconColor = 'text-purple-500';
                            $bgColor = 'bg-purple-100';
                            break;
                        case 'Comment':
                            $iconColor = 'text-indigo-500';
                            $bgColor = 'bg-indigo-100';
                            break;
                    }
                }
            @endphp

            <div class="w-10 h-10 rounded-full {{ $bgColor }} flex items-center justify-center">
                @if($activity->subject_type && class_basename($activity->subject_type) === 'Company')
                    <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                @elseif($activity->subject_type && class_basename($activity->subject_type) === 'Project')
                    <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                @elseif($activity->subject_type && class_basename($activity->subject_type) === 'Rfq')
                    <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                @elseif($activity->subject_type && class_basename($activity->subject_type) === 'Auction')
                    <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                @elseif($activity->subject_type && class_basename($activity->subject_type) === 'Comment')
                    <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                @else
                    <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
            </div>
        </div>

        <!-- Содержимое -->
        <div class="flex-1 min-w-0">
            <div class="text-sm text-gray-900">
                @php
                    $description = $activity->description;
                    $subjectName = '';
                    $subjectUrl = null;

                    if ($activity->subject) {
                        $modelName = class_basename($activity->subject_type);
                        switch($modelName) {
                            case 'Company':
                                $subjectName = $activity->subject->name ?? '';
                                $subjectUrl = route('companies.show', $activity->subject);
                                break;
                            case 'Project':
                                $subjectName = $activity->subject->title ?? '';
                                $subjectUrl = route('projects.show', $activity->subject);
                                break;
                            case 'Rfq':
                                $subjectName = $activity->subject->number ?? '';
                                $subjectUrl = route('rfqs.show', $activity->subject);
                                break;
                            case 'Auction':
                                $subjectName = $activity->subject->number ?? '';
                                $subjectUrl = route('auctions.show', $activity->subject);
                                break;
                            case 'Comment':
                                $subjectName = Str::limit($activity->subject->content ?? '', 50);
                                if ($activity->subject->project) {
                                    $subjectUrl = route('projects.show', $activity->subject->project);
                                }
                                break;
                        }
                    }

                    // Переводим описание
                    $descriptionRu = match($description) {
                        'created' => 'создал(а)',
                        'updated' => 'обновил(а)',
                        'deleted' => 'удалил(а)',
                        default => $description,
                    };

                    // Тип сущности на русском
                    $modelName = $activity->subject_type ? class_basename($activity->subject_type) : '';
                    $typeRu = match($modelName) {
                        'Company' => 'компанию',
                        'Project' => 'проект',
                        'Rfq' => 'тендер',
                        'Auction' => 'аукцион',
                        'Comment' => 'комментарий',
                        default => 'запись',
                    };
                @endphp

                <!-- Кто -->
                <span class="font-medium">
                    @if($activity->causer)
                        {{ $activity->causer->name }}
                    @else
                        Система
                    @endif
                </span>

                <!-- Действие -->
                <span class="text-gray-600">{{ $descriptionRu }}</span>

                <!-- Что -->
                <span class="text-gray-600">{{ $typeRu }}</span>

                <!-- Ссылка на объект -->
                @if($subjectUrl && $subjectName)
                    <a href="{{ $subjectUrl }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                        "{{ $subjectName }}"
                    </a>
                @elseif($subjectName)
                    <span class="font-medium">"{{ $subjectName }}"</span>
                @endif
            </div>

            <!-- Время -->
            <p class="text-xs text-gray-500 mt-1">
                {{ $activity->created_at->diffForHumans() }}
            </p>
        </div>
    </div>
@empty
    <div class="text-center py-8 text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('Нет активности') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('Здесь будут отображаться действия пользователей') }}</p>
    </div>
@endforelse

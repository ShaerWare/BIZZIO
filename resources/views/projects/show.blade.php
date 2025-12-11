<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $project->name }}
            </h2>
            
            @auth
                @if($project->canManage(auth()->user()))
                    <div class="flex space-x-2">
                        <a href="{{ route('projects.edit', $project->slug) }}" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Редактировать
                        </a>
                        
                        @if($project->created_by === auth()->id() || auth()->user()->inRole('admin'))
                            <form method="POST" action="{{ route('projects.destroy', $project->slug) }}" 
                                  onsubmit="return confirm('Вы уверены, что хотите удалить этот проект?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Удалить
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Главная информация -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Аватар -->
                        <div class="flex-shrink-0">
                            @if($project->avatar)
                                <img src="{{ $project->avatar_url }}" 
                                     alt="{{ $project->name }}" 
                                     class="w-full md:w-64 h-48 object-cover rounded-lg">
                            @else
                                <div class="w-full md:w-64 h-48 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-24 h-24 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Основная информация -->
                        <div class="flex-1">
                            <!-- Статус -->
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$project->status] }} mb-4">
                                {{ \App\Models\Project::getStatuses()[$project->status] }}
                            </span>

                            <!-- Компания-заказчик -->
                            <div class="flex items-center mb-4">
                                @if($project->company->logo)
                                    <img src="{{ $project->company->logo_url }}" 
                                         alt="{{ $project->company->name }}" 
                                         class="w-12 h-12 rounded-full mr-3 object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                        <span class="text-sm text-gray-500 font-semibold">
                                            {{ strtoupper(substr($project->company->name, 0, 2)) }}
                                        </span>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-xs text-gray-500">Компания-заказчик</p>
                                    <a href="{{ route('companies.show', $project->company->slug) }}" 
                                       class="text-base font-semibold text-indigo-600 hover:text-indigo-500">
                                        {{ $project->company->name }}
                                    </a>
                                </div>
                            </div>

                            <!-- Сроки -->
                            <div class="flex items-center text-gray-600 mb-2">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>{{ $project->formatted_duration }}</span>
                            </div>

                            <!-- Создатель -->
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Создатель: <strong>{{ $project->creator->name }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладки -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Навигация по вкладкам -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button onclick="showTab('description')" 
                                id="tab-description"
                                class="tab-button active border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Описание
                        </button>
                        <button onclick="showTab('participants')" 
                                id="tab-participants"
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Участники ({{ $project->participants->count() }})
                        </button>
                        <button onclick="showTab('comments')" 
                                id="tab-comments"
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Комментарии ({{ $project->comments->count() }})
                        </button>
                    </nav>
                </div>

                <!-- Контент вкладок -->
                <div class="p-6">
                    <!-- Вкладка: Описание -->
                    <div id="content-description" class="tab-content">
                        @if($project->description)
                            <div class="prose max-w-none mb-6">
                                <h3 class="text-lg font-semibold mb-2">Краткое описание</h3>
                                {!! $project->description !!}
                            </div>
                        @endif

                        @if($project->full_description)
                            <div class="prose max-w-none">
                                <h3 class="text-lg font-semibold mb-2">Полное описание</h3>
                                {!! $project->full_description !!}
                            </div>
                        @endif

                        @if(!$project->description && !$project->full_description)
                            <p class="text-gray-500 text-center py-8">Описание проекта пока не добавлено</p>
                        @endif
                    </div>

                    <!-- Вкладка: Участники -->
                    <div id="content-participants" class="tab-content hidden">
                        @if($project->participants->isEmpty())
                            <p class="text-gray-500 text-center py-8">Участники проекта пока не добавлены</p>
                        @else
                            <div class="space-y-4">
                                @foreach($project->participants as $participant)
                                    <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                        @if($participant->logo)
                                            <img src="{{ $participant->logo_url }}" 
                                                 alt="{{ $participant->name }}" 
                                                 class="w-16 h-16 rounded-lg mr-4 object-cover">
                                        @else
                                            <div class="w-16 h-16 rounded-lg mr-4 bg-gray-200 flex items-center justify-center">
                                                <span class="text-lg text-gray-500 font-semibold">
                                                    {{ strtoupper(substr($participant->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="flex-1">
                                            <a href="{{ route('companies.show', $participant->slug) }}" 
                                               class="text-lg font-semibold text-indigo-600 hover:text-indigo-500">
                                                {{ $participant->name }}
                                            </a>
                                            
                                            <p class="text-sm text-gray-600 mt-1">
                                                <strong>Роль:</strong> {{ \App\Models\Project::getParticipantRoles()[$participant->pivot->role] }}
                                            </p>

                                            @if($participant->pivot->participation_description)
                                                <p class="text-sm text-gray-700 mt-2">
                                                    {{ $participant->pivot->participation_description }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Вкладка: Комментарии -->
                    <div id="content-comments" class="tab-content hidden">
                        @auth
                            <!-- Форма добавления комментария -->
                            <form method="POST" 
                                  action="{{ route('projects.comments.store', $project->slug) }}" 
                                  id="comment-form"
                                  class="mb-6">
                                @csrf
                                <textarea name="body" 
                                          id="comment-body"
                                          rows="3" 
                                          placeholder="Написать комментарий..."
                                          required
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                
                                @error('body')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror

                                <div class="mt-2 flex justify-end">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                        Отправить комментарий
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-center">
                                <p class="text-gray-600">
                                    <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500 font-semibold">Войдите</a> 
                                    или 
                                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-500 font-semibold">зарегистрируйтесь</a>, 
                                    чтобы оставить комментарий
                                </p>
                            </div>
                        @endauth

                        <!-- Список комментариев -->
                        <div id="comments-list" class="space-y-4">
                            @forelse($project->comments as $comment)
                                @include('projects.partials.comment', ['comment' => $comment])
                            @empty
                                <p class="text-gray-500 text-center py-8">Комментариев пока нет. Будьте первым!</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Переключение вкладок
        function showTab(tabName) {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Убираем активный класс у всех кнопок
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Показываем выбранную вкладку
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Активируем кнопку
            const activeButton = document.getElementById('tab-' + tabName);
            activeButton.classList.remove('border-transparent', 'text-gray-500');
            activeButton.classList.add('active', 'border-indigo-500', 'text-indigo-600');
        }
    </script>
    @endpush
</x-app-layout>
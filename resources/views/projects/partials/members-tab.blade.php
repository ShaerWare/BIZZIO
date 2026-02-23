@php
    $canManage = auth()->check() && $project->canManage(auth()->user());
    $isMember = auth()->check() && $project->isMember(auth()->user());
    $pendingRequests = $project->joinRequests()->with(['user', 'company'])->pending()->get();

    // Проверка: может ли текущий пользователь подать запрос
    $canRequestJoin = false;
    $hasPendingRequest = false;
    if (auth()->check() && !$isMember && !$canManage) {
        $participantCompanyIds = $project->participants->pluck('id')->toArray();
        $participantCompanyIds[] = $project->company_id;
        $userCompanyIds = auth()->user()->moderatedCompanies()->pluck('companies.id')->toArray();
        $canRequestJoin = count(array_intersect($userCompanyIds, $participantCompanyIds)) > 0;
        $hasPendingRequest = $project->hasPendingRequestFrom(auth()->user());
    }

    // Группируем участников по компаниям
    $membersByCompany = $project->members->groupBy('pivot.company_id');
@endphp

{{-- Форма приглашения пользователя (для менеджеров) --}}
@if($canManage)
    <div class="mb-6 p-4 bg-gray-50 rounded-lg" x-data="projectUserSearch()">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Пригласить пользователя</h4>
        <form method="POST" action="{{ route('projects.members.store', $project->slug) }}">
            @csrf
            <input type="hidden" name="user_id" x-model="selectedUserId">

            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Поиск пользователя --}}
                <div class="flex-1 relative">
                    <template x-if="!selectedUserId">
                        <div>
                            <input type="text"
                                   x-model="query"
                                   @input.debounce.300ms="search()"
                                   @focus="if(query.length >= 2) showResults = true"
                                   @click.away="showResults = false"
                                   placeholder="Поиск пользователя по имени или email..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">

                            {{-- Результаты поиска --}}
                            <div x-show="showResults"
                                 x-cloak
                                 class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                <template x-if="loading">
                                    <div class="p-3 text-center text-gray-500 text-sm">Поиск...</div>
                                </template>
                                <template x-if="!loading && results.length === 0 && query.length >= 2">
                                    <div class="p-3 text-center text-gray-500 text-sm">Пользователи не найдены</div>
                                </template>
                                <template x-for="user in results" :key="user.id">
                                    <button type="button"
                                            @click="selectUser(user)"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                        <div class="text-sm font-medium text-gray-900" x-text="user.title"></div>
                                        <div class="text-xs text-gray-500" x-text="user.subtitle"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Выбранный пользователь --}}
                    <template x-if="selectedUserId">
                        <div class="flex items-center gap-2 p-2 bg-white border border-gray-300 rounded-md">
                            <span class="text-sm text-gray-900 flex-1" x-text="selectedUserName"></span>
                            <button type="button" @click="clearSelection()" class="text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Выбор роли --}}
                <div>
                    <select name="role" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                        @foreach(\App\Models\Project::getUserRoles() as $value => $label)
                            <option value="{{ $value }}" {{ $value === 'member' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Кнопка --}}
                <div>
                    <button type="submit"
                            :disabled="!selectedUserId"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Пригласить
                    </button>
                </div>
            </div>
        </form>
    </div>
@endif

{{-- Кнопка "Присоединиться к проекту" для подходящих пользователей --}}
@auth
    @if($canRequestJoin && !$hasPendingRequest)
        <div class="mb-6 p-4 bg-emerald-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-emerald-800">Ваша компания участвует в этом проекте</h4>
                    <p class="text-xs text-emerald-600 mt-1">Вы можете подать запрос на присоединение</p>
                </div>
                <button type="button"
                        onclick="document.getElementById('join-project-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                    Присоединиться
                </button>
            </div>
        </div>

        {{-- Модальное окно запроса --}}
        <div id="join-project-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Запрос на присоединение</h3>
                <form method="POST" action="{{ route('projects.join-requests.store', $project->slug) }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Сообщение (необязательно)</label>
                        <textarea name="message" rows="3" maxlength="1000"
                                  placeholder="Расскажите, почему вы хотите присоединиться..."
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                                onclick="document.getElementById('join-project-modal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">
                            Отмена
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">
                            Отправить запрос
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($hasPendingRequest)
        <div class="mb-6 p-4 bg-yellow-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-yellow-800">Ваш запрос на рассмотрении</h4>
                    <p class="text-xs text-yellow-600 mt-1">Ожидайте решения модератора проекта</p>
                </div>
                @php
                    $myPendingRequest = $project->joinRequests()->where('user_id', auth()->id())->pending()->first();
                @endphp
                @if($myPendingRequest)
                    <form method="POST" action="{{ route('project-join-requests.destroy', $myPendingRequest) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 transition">
                            Отозвать запрос
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endauth

{{-- Список участников --}}
@if($project->members->isEmpty())
    <p class="text-gray-500 text-center py-8">Участники проекта пока не добавлены</p>
@else
    <div class="space-y-6">
        @foreach($membersByCompany as $companyId => $members)
            @php
                $memberCompany = \App\Models\Company::find($companyId);
            @endphp
            @if($memberCompany)
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        {{ $memberCompany->name }}
                    </h4>
                    <div class="space-y-3">
                        @foreach($members as $member)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    {{-- Аватар с инициалами --}}
                                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <span class="text-sm font-semibold text-emerald-700">
                                            {{ strtoupper(mb_substr($member->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $member->email }}</div>
                                    </div>
                                    {{-- Бейдж роли --}}
                                    @php
                                        $roleBadgeColors = [
                                            'admin' => 'bg-red-100 text-red-800',
                                            'moderator' => 'bg-blue-100 text-blue-800',
                                            'member' => 'bg-gray-100 text-gray-800',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleBadgeColors[$member->pivot->role] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ \App\Models\Project::getUserRoles()[$member->pivot->role] ?? $member->pivot->role }}
                                    </span>
                                </div>

                                {{-- Управление (для менеджеров) --}}
                                @if($canManage && $member->id !== $project->created_by)
                                    <div class="flex items-center gap-2">
                                        {{-- Изменение роли --}}
                                        <form method="POST" action="{{ route('projects.members.update', [$project->slug, $member->id]) }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <select name="role" onchange="this.form.submit()" class="text-xs rounded border-gray-300 py-1">
                                                @foreach(\App\Models\Project::getUserRoles() as $value => $label)
                                                    <option value="{{ $value }}" {{ $member->pivot->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                        {{-- Удаление --}}
                                        <form method="POST" action="{{ route('projects.members.destroy', [$project->slug, $member->id]) }}"
                                              onsubmit="return confirm('Удалить пользователя {{ $member->name }} из проекта?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600" title="Удалить из проекта">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif

{{-- Ожидающие запросы (для менеджеров) --}}
@if($canManage && $pendingRequests->isNotEmpty())
    <div class="mt-8">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Запросы на присоединение ({{ $pendingRequests->count() }})</h4>
        <div class="space-y-3">
            @foreach($pendingRequests as $request)
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                            <span class="text-sm font-semibold text-yellow-700">
                                {{ strtoupper(mb_substr($request->user->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $request->user->email }} &middot; {{ $request->company->name }}</div>
                            @if($request->message)
                                <div class="text-xs text-gray-600 mt-1 italic">{{ $request->message }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('project-join-requests.approve', $request) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1 bg-emerald-600 text-white text-xs font-semibold rounded hover:bg-emerald-700">
                                Принять
                            </button>
                        </form>
                        <form method="POST" action="{{ route('project-join-requests.reject', $request) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded hover:bg-red-700">
                                Отклонить
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@extends('layouts.app')

@section('title', $company->name)

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Хлебные крошки -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('companies.index') }}" class="text-sm text-gray-700 hover:text-gray-900">
                        Компании
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500">{{ $company->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Заголовок с кнопками -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                    <div class="flex items-start space-x-3 sm:space-x-4 flex-1 min-w-0">
                        @if($company->logo)
                            <img src="{{ $company->logo_url }}"
                                 alt="{{ $company->name }}"
                                 class="w-16 h-16 sm:w-24 sm:h-24 rounded-lg object-cover shadow-md flex-shrink-0">
                        @else
                            <div class="w-16 h-16 sm:w-24 sm:h-24 bg-gradient-to-br from-emerald-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md flex-shrink-0">
                                <span class="text-4xl font-bold text-white">
                                    {{ substr($company->name, 0, 1) }}
                                </span>
                            </div>
                        @endif

                        <div class="flex-1">
                            <div class="flex items-center flex-wrap gap-2 mb-2">
                                <h1 class="text-xl sm:text-3xl font-bold text-gray-900 break-words">{{ $company->name }}</h1>
                                @if($company->is_verified)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Верифицирована
                                    </span>
                                @endif
                            </div>
                            
                            <dl class="space-y-1">
                                <div class="flex items-center text-gray-600">
                                    <dt class="font-medium mr-2">ИНН:</dt>
                                    <dd>{{ $company->inn }}</dd>
                                </div>
                                
                                @if($company->legal_form)
                                    <div class="flex items-center text-gray-600">
                                        <dt class="font-medium mr-2">Форма:</dt>
                                        <dd>{{ $company->legal_form }}</dd>
                                    </div>
                                @endif
                                
                                @if($company->industry)
                                    <div class="flex items-center text-gray-600">
                                        <dt class="font-medium mr-2">Отрасль:</dt>
                                        <dd>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ $company->industry->name }}
                                            </span>
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="flex flex-wrap gap-2">
                        @auth
                            @unless($company->isModerator(auth()->user()))
                                @include('components.subscribe-button', ['target' => $company])
                            @endunless

                            @if($company->canManageModerators(auth()->user()))
                                <!-- Кнопка редактирования (для модераторов) -->
                                <a href="{{ route('companies.edit', $company) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Редактировать
                                </a>
                            @elseif(!$company->isModerator(auth()->user()))
                                <!-- Кнопка "Присоединиться" (для не-модераторов) -->
                                @if($company->hasPendingRequestFrom(auth()->user()))
                                    <!-- Уже отправлен запрос -->
                                    <button disabled
                                            class="inline-flex items-center px-4 py-2 bg-gray-400 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest cursor-not-allowed">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Запрос отправлен
                                    </button>
                                @else
                                    <!-- Открыть модальное окно для присоединения -->
                                    <button onclick="showJoinModal()" 
                                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                        Присоединиться
                                    </button>
                                @endif
                            @endif
                            
                            @if($company->created_by === auth()->id())
                                <!-- Кнопка удаления (только для создателя) -->
                                <form method="POST" action="{{ route('companies.destroy', $company) }}" 
                                      onsubmit="return confirm('Вы уверены, что хотите удалить эту компанию?');">
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
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <!-- Навигация по вкладкам -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="showTab('description')" 
                            id="tab-description"
                            class="tab-button border-emerald-500 text-emerald-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Описание
                    </button>
                    <button onclick="showTab('documents')"
                            id="tab-documents"
                            class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Документы
                    </button>
                    <button onclick="showTab('photos')"
                            id="tab-photos"
                            class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Фото
                        @if($company->getMedia('photos')->count() > 0)
                            <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-gray-200 text-gray-600 rounded-full">
                                {{ $company->getMedia('photos')->count() }}
                            </span>
                        @endif
                    </button>
                    <button onclick="showTab('people')" 
                            id="tab-people"
                            class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Люди
                    </button>
                </nav>
            </div>

            <!-- Контент вкладок -->
            <div class="p-6">
                <!-- Вкладка: Описание -->
                <div id="content-description" class="tab-content">
                    @if($company->short_description || $company->full_description)
                        @if($company->short_description)
                            <p class="text-gray-600 mb-4 font-medium">{{ $company->short_description }}</p>
                        @endif
                        
                        @if($company->full_description)
                            <div class="text-gray-600 prose max-w-none">
                                {!! nl2br(e($company->full_description)) !!}
                            </div>
                        @endif
                    @else
                        <p class="text-gray-500 text-center py-8">Описание не добавлено</p>
                    @endif
                </div>

                <!-- Вкладка: Документы -->
                <div id="content-documents" class="tab-content hidden">
                    @if($company->getMedia('documents')->count() > 0)
                        <div class="space-y-2">
                            @foreach($company->getMedia('documents') as $document)
                                <a href="{{ $document->getUrl() }}"
                                   target="_blank"
                                   class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg class="w-8 h-8 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $document->name }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($document->size / 1024, 2) }} KB</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">Документы не загружены</p>
                    @endif
                </div>

                <!-- Вкладка: Фото -->
                <div id="content-photos" class="tab-content hidden">
                    <!-- Форма загрузки фото (только для модераторов) -->
                    @auth
                        @if($company->isModerator(auth()->user()))
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <form action="{{ route('companies.photos.upload', $company) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Загрузить фотографии</label>
                                        <input
                                            type="file"
                                            name="photos[]"
                                            multiple
                                            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                            class="block w-full text-sm text-gray-500
                                                file:mr-4 file:py-2 file:px-4
                                                file:rounded-md file:border-0
                                                file:text-sm file:font-semibold
                                                file:bg-emerald-50 file:text-emerald-700
                                                hover:file:bg-emerald-100 cursor-pointer"
                                        >
                                        @error('photos.*')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500">Можно выбрать несколько файлов. JPG, PNG, GIF или WebP. Максимум 5 МБ каждый.</p>
                                    </div>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                        </svg>
                                        Загрузить
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endauth

                    <!-- Галерея фото -->
                    @if($company->getMedia('photos')->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($company->getMedia('photos') as $photo)
                                <div class="relative group aspect-square">
                                    <a href="{{ $photo->getUrl() }}" target="_blank" class="block w-full h-full">
                                        <img
                                            src="{{ $photo->hasGeneratedConversion('thumb') ? $photo->getUrl('thumb') : $photo->getUrl() }}"
                                            alt="Фото компании"
                                            class="w-full h-full object-cover rounded-lg shadow-sm hover:shadow-md transition-shadow"
                                            loading="lazy"
                                        >
                                    </a>
                                    @auth
                                        @if($company->isModerator(auth()->user()))
                                            <form action="{{ route('companies.photos.delete', [$company, $photo->id]) }}" method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Удалить это фото?')" class="p-1.5 bg-red-600 text-white rounded-full hover:bg-red-700 shadow-lg">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endauth
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Нет фотографий</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @auth
                                    @if($company->isModerator(auth()->user()))
                                        Загрузите фотографии компании выше.
                                    @else
                                        Компания пока не добавила фотографии.
                                    @endif
                                @else
                                    Компания пока не добавила фотографии.
                                @endauth
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Вкладка: Люди -->
                <div id="content-people" class="tab-content hidden">
                    @php
                        $canManagePeople = auth()->check() && $company->canManageModerators(auth()->user());
                        $roleLabels = ['owner' => 'Владелец', 'admin' => 'Админ', 'moderator' => 'Модератор', 'member' => 'Участник'];
                        $roleBadgeColors = [
                            'owner' => 'bg-green-100 text-green-800',
                            'admin' => 'bg-red-100 text-red-800',
                            'moderator' => 'bg-blue-100 text-blue-800',
                            'member' => 'bg-gray-100 text-gray-800',
                        ];
                        $editableRoles = ['admin' => 'Админ', 'moderator' => 'Модератор', 'member' => 'Участник'];
                    @endphp

                    {{-- #71: Форма добавления участника (по аналогии с проектами) --}}
                    @if($canManagePeople)
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg" x-data="companyUserSearch()">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Добавить участника</h4>
                            <form method="POST" action="{{ route('companies.moderators.store', $company) }}">
                                @csrf
                                <input type="hidden" name="user_id" x-model="selectedUserId">
                                <div class="flex flex-col sm:flex-row gap-3">
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
                                                <div x-show="showResults" x-cloak
                                                     class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                                    <template x-if="loading">
                                                        <div class="p-3 text-center text-gray-500 text-sm">Поиск...</div>
                                                    </template>
                                                    <template x-if="!loading && results.length === 0 && query.length >= 2">
                                                        <div class="p-3 text-center text-gray-500 text-sm">Пользователи не найдены</div>
                                                    </template>
                                                    <template x-for="user in results" :key="user.id">
                                                        <button type="button" @click="selectUser(user)"
                                                                class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                                            <div class="text-sm font-medium text-gray-900" x-text="user.title"></div>
                                                            <div class="text-xs text-gray-500" x-text="user.subtitle"></div>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
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
                                    <div>
                                        <select name="role" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                            @foreach($editableRoles as $value => $label)
                                                <option value="{{ $value }}" {{ $value === 'member' ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <button type="submit" :disabled="!selectedUserId"
                                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                            Добавить
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Список участников с inline-управлением (#71 + #72) --}}
                    @if($company->moderators->count() > 0)
                        <div class="space-y-3">
                            @foreach($company->moderators as $moderator)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                            <span class="text-sm font-semibold text-emerald-700">
                                                {{ strtoupper(mb_substr($moderator->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $moderator->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $moderator->email }}</div>
                                        </div>
                                        @php $role = $moderator->pivot->role ?? 'member'; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleBadgeColors[$role] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $roleLabels[$role] ?? $role }}
                                        </span>
                                        @if($company->created_by === $moderator->id)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Создатель
                                            </span>
                                        @endif
                                    </div>

                                    {{-- #72: Inline управление ролями (для менеджеров, кроме создателя) --}}
                                    @if($canManagePeople && $moderator->id !== $company->created_by)
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('companies.moderators.update', [$company, $moderator]) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <select name="role" onchange="this.form.submit()" class="text-xs rounded border-gray-300 py-1">
                                                    @foreach($editableRoles as $value => $label)
                                                        <option value="{{ $value }}" {{ $role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                            <form method="POST" action="{{ route('companies.moderators.destroy', [$company, $moderator]) }}"
                                                  onsubmit="return confirm('Удалить пользователя {{ $moderator->name }} из компании?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600" title="Удалить из компании">
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
                    @else
                        <p class="text-gray-500 text-center py-8">Участники не назначены</p>
                    @endif

                    {{-- C6: Запросы на присоединение (перенесены из «Управление» во «Люди») --}}
                    @auth
                        @if($company->canManageModerators(auth()->user()))
                            @php
                                $pendingRequests = $company->joinRequests()->pending()->with('user')->get();
                            @endphp

                            @if($pendingRequests->isNotEmpty())
                                <div class="mt-8 border-t border-gray-200 pt-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Запросы на присоединение ({{ $pendingRequests->count() }})
                                    </h3>

                                    <div class="space-y-4">
                                        @foreach($pendingRequests as $joinRequest)
                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                                    <div class="flex items-start flex-1 min-w-0">
                                                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-emerald-100 flex items-center justify-center mr-3 sm:mr-4 flex-shrink-0">
                                                            <span class="text-lg font-semibold text-emerald-600">
                                                                {{ strtoupper(substr($joinRequest->user->name, 0, 2)) }}
                                                            </span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <h4 class="text-base font-semibold text-gray-900">
                                                                {{ $joinRequest->user->name }}
                                                            </h4>
                                                            <p class="text-sm text-gray-600">{{ $joinRequest->user->email }}</p>

                                                            @if($joinRequest->desired_role)
                                                                <div class="mt-2">
                                                                    <span class="text-xs text-gray-500">Желаемая роль:</span>
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-800 ml-1">
                                                                        {{ $joinRequest->desired_role }}
                                                                    </span>
                                                                </div>
                                                            @endif

                                                            @if($joinRequest->message)
                                                                <div class="mt-2 p-3 bg-white rounded border border-gray-200">
                                                                    <p class="text-sm text-gray-700">{{ $joinRequest->message }}</p>
                                                                </div>
                                                            @endif

                                                            <p class="text-xs text-gray-500 mt-2">
                                                                Запрос отправлен: {{ $joinRequest->created_at->format('d.m.Y H:i') }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Кнопки действий -->
                                                    <div class="flex gap-2 sm:ml-4">
                                                        <button onclick="showApproveModal({{ $joinRequest->id }}, {{ Js::from($joinRequest->user->name) }}, {{ Js::from($joinRequest->desired_role) }})"
                                                                class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                                            Одобрить
                                                        </button>
                                                        <button onclick="showRejectModal({{ $joinRequest->id }}, {{ Js::from($joinRequest->user->name) }})"
                                                                class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                                            Отклонить
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endauth
                </div>

            </div>
        </div>
    </div>
</div>


<!-- Модальное окно: Присоединиться к компании -->
<div id="join-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Присоединиться к компании</h3>
                <button onclick="closeJoinModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="{{ route('companies.join-requests.store', $company) }}">
                @csrf
                
                <p class="text-sm text-gray-600 mb-4">
                    Отправьте запрос на присоединение к компании <strong>{{ $company->name }}</strong>.
                </p>
                
                <div class="mb-4">
                    <label for="desired_role" class="block text-sm font-medium text-gray-700 mb-2">
                        Желаемая роль/должность
                    </label>
                    <input type="text" 
                           name="desired_role" 
                           id="desired_role"
                           placeholder="Например: Менеджер по продажам"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                        Сообщение
                    </label>
                    <textarea name="message" 
                              id="message"
                              rows="4"
                              placeholder="Расскажите о себе..."
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" 
                            onclick="closeJoinModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                        Отмена
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        Отправить запрос
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно: Одобрить запрос -->
<div id="approve-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Одобрить запрос</h3>
                <button onclick="closeApproveModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="approve-form" method="POST" action="">
                @csrf

                <p class="text-sm text-gray-600 mb-4">
                    Одобрить запрос от <strong id="approve-user-name"></strong>?
                </p>

                <div class="mb-4">
                    <label for="approve_role" class="block text-sm font-medium text-gray-700 mb-2">
                        Назначить роль
                    </label>
                    <input type="text"
                           name="role"
                           id="approve_role"
                           placeholder="Например: Менеджер"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <p class="text-xs text-gray-500 mt-1">Оставьте пустым, чтобы использовать роль из запроса</p>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button"
                            onclick="closeApproveModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                        Отмена
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        Одобрить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно: Отклонить запрос -->
<div id="reject-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Отклонить запрос</h3>
                <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="reject-form" method="POST" action="">
                @csrf

                <p class="text-sm text-gray-600 mb-4">
                    Отклонить запрос от <strong id="reject-user-name"></strong>?
                </p>

                <div class="mb-4">
                    <label for="review_comment" class="block text-sm font-medium text-gray-700 mb-2">
                        Причина отклонения (необязательно)
                    </label>
                    <textarea name="review_comment"
                              id="review_comment"
                              rows="3"
                              placeholder="Укажите причину..."
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button"
                            onclick="closeRejectModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                        Отмена
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                        Отклонить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Переключение вкладок
function showTab(tabName) {
    // Скрыть все вкладки
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Убрать активное состояние у всех кнопок
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-emerald-500', 'text-emerald-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Показать выбранную вкладку
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Активировать выбранную кнопку
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-emerald-500', 'text-emerald-600');
}

// Модальное окно присоединения
function showJoinModal() {
    document.getElementById('join-modal').classList.remove('hidden');
}

function closeJoinModal() {
    document.getElementById('join-modal').classList.add('hidden');
}

// Модальное окно одобрения
function showApproveModal(requestId, userName, desiredRole) {
    document.getElementById('approve-form').action = '/join-requests/' + requestId + '/approve';
    document.getElementById('approve-user-name').textContent = userName;
    if (desiredRole) {
        document.getElementById('approve_role').value = desiredRole;
    }
    document.getElementById('approve-modal').classList.remove('hidden');
}

function closeApproveModal() {
    document.getElementById('approve-modal').classList.add('hidden');
    document.getElementById('approve_role').value = '';
}

// Модальное окно отклонения
function showRejectModal(requestId, userName) {
    document.getElementById('reject-form').action = '/join-requests/' + requestId + '/reject';
    document.getElementById('reject-user-name').textContent = userName;
    document.getElementById('reject-modal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
    document.getElementById('review_comment').value = '';
}

// Alpine.js компонент поиска пользователей (вкладка Люди)
function companyUserSearch() {
    const moderatorIds = @json($company->moderators->pluck('id'));

    return {
        query: '',
        results: [],
        showResults: false,
        loading: false,
        selectedUserId: '',
        selectedUserName: '',

        async search() {
            if (this.query.length < 2) {
                this.results = [];
                this.showResults = false;
                return;
            }

            this.loading = true;
            this.showResults = true;

            try {
                const response = await fetch(`/search/quick?q=${encodeURIComponent(this.query)}`);
                const data = await response.json();

                this.results = data
                    .filter(item => item.type === 'user')
                    .filter(item => !moderatorIds.includes(item.id));
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        selectUser(user) {
            this.selectedUserId = user.id;
            this.selectedUserName = user.title + (user.subtitle ? ' (' + user.subtitle + ')' : '');
            this.query = '';
            this.results = [];
            this.showResults = false;
        },

        clearSelection() {
            this.selectedUserId = '';
            this.selectedUserName = '';
            this.query = '';
        }
    };
}

// Закрытие по клику вне окна
window.onclick = function(event) {
    if (event.target.id === 'join-modal') {
        closeJoinModal();
    }
    if (event.target.id === 'approve-modal') {
        closeApproveModal();
    }
    if (event.target.id === 'reject-modal') {
        closeRejectModal();
    }
}
</script>
@endpush
@endsection
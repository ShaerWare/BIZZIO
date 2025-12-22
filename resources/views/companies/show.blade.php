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

        <!-- Шапка профиля -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex flex-col md:flex-row items-start justify-between gap-6">
                    <!-- Логотип и основная информация -->
                    <div class="flex items-start space-x-4 flex-1">
                        @if($company->logo)
                            <img src="{{ Storage::url($company->logo) }}" 
                                 alt="{{ $company->name }}"
                                 class="w-24 h-24 rounded-lg object-cover shadow-md">
                        @else
                            <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                <span class="text-4xl font-bold text-white">
                                    {{ substr($company->name, 0, 1) }}
                                </span>
                            </div>
                        @endif

                        <div class="flex-1">
                            <div class="flex items-start gap-2 mb-2">
                                <h1 class="text-3xl font-bold text-gray-900">
                                    {{ $company->name }}
                                </h1>
                                @if($company->is_verified)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
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
                    @auth
                        @if($company->created_by === auth()->id() || $company->isModerator(auth()->user()))
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="{{ route('companies.edit', $company) }}" 
                                   class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Редактировать
                                </a>
                                
                                @if($company->created_by === auth()->id())
                                    <form method="POST" action="{{ route('companies.destroy', $company) }}" 
                                          onsubmit="return confirm('Вы уверены, что хотите удалить компанию?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Удалить
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Основная колонка -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Описание -->
                @if($company->short_description || $company->full_description)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                О компании
                            </h2>
                            
                            @if($company->short_description)
                                <p class="text-gray-600 mb-4 font-medium">{{ $company->short_description }}</p>
                            @endif
                            
                            @if($company->full_description)
                                <div class="text-gray-600 prose max-w-none">
                                    {!! nl2br(e($company->full_description)) !!}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Документы -->
                @if($company->getMedia('documents')->count() > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Документы
                            </h2>
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
                        </div>
                    </div>
                @endif
            </div>

            <!-- Боковая колонка -->
            <div class="space-y-6">
                <!-- Создатель -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Создатель
                        </h3>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-indigo-600 font-medium">
                                        {{ substr($company->creator->name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $company->creator->name }}</p>
                                <p class="text-xs text-gray-500">{{ $company->creator->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Модераторы -->
                @if($company->moderators->count() > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Модераторы
                            </h3>
                            <div class="space-y-3">
                                @foreach($company->moderators as $moderator)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                    <span class="text-gray-600 text-sm font-medium">
                                                        {{ substr($moderator->name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $moderator->name }}</p>
                                                <p class="text-xs text-gray-500 capitalize">{{ $moderator->pivot->role }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Информация -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Информация
                        </h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Дата создания</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $company->created_at->format('d.m.Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Последнее обновление</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $company->updated_at->diffForHumans() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно: Одобрить запрос -->
<div id="approve-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Одобрить запрос</h3>
            
            <form id="approve-form" method="POST" action="">
                @csrf
                
                <p class="text-sm text-gray-600 mb-4">
                    Вы собираетесь добавить пользователя <strong id="approve-user-name"></strong> как модератора компании.
                </p>
                
                <div class="mb-4">
                    <label for="approve-role" class="block text-sm font-medium text-gray-700 mb-2">
                        Роль
                    </label>
                    <input type="text" 
                           name="role" 
                           id="approve-role"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="can_manage_moderators" 
                               value="1"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Может управлять модераторами</span>
                    </label>
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
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Отклонить запрос</h3>
            
            <form id="reject-form" method="POST" action="">
                @csrf
                
                <p class="text-sm text-gray-600 mb-4">
                    Вы собираетесь отклонить запрос пользователя <strong id="reject-user-name"></strong>.
                </p>
                
                <div class="mb-4">
                    <label for="review_comment" class="block text-sm font-medium text-gray-700 mb-2">
                        Причина отклонения (необязательно)
                    </label>
                    <textarea name="review_comment" 
                              id="review_comment"
                              rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="Укажите причину..."></textarea>
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

<!-- Модальное окно: Изменить роль модератора -->
<div id="edit-moderator-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Изменить роль модератора</h3>
            
            <form id="edit-moderator-form" method="POST" action="">
                @csrf
                @method('PUT')
                
                <p class="text-sm text-gray-600 mb-4">
                    Изменение роли для: <strong id="edit-moderator-name"></strong>
                </p>
                
                <div class="mb-4">
                    <label for="edit-role" class="block text-sm font-medium text-gray-700 mb-2">
                        Роль
                    </label>
                    <input type="text" 
                           name="role" 
                           id="edit-role"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="can_manage_moderators" 
                               id="edit-can-manage"
                               value="1"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Может управлять модераторами</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" 
                            onclick="closeEditModeratorModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                        Отмена
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                        Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Модальное окно: Одобрить запрос
function showApproveModal(requestId, userName, desiredRole) {
    document.getElementById('approve-modal').classList.remove('hidden');
    document.getElementById('approve-user-name').textContent = userName;
    document.getElementById('approve-role').value = desiredRole || '';
    document.getElementById('approve-form').action = `/join-requests/${requestId}/approve`;
}

function closeApproveModal() {
    document.getElementById('approve-modal').classList.add('hidden');
}

// Модальное окно: Отклонить запрос
function showRejectModal(requestId, userName) {
    document.getElementById('reject-modal').classList.remove('hidden');
    document.getElementById('reject-user-name').textContent = userName;
    document.getElementById('reject-form').action = `/join-requests/${requestId}/reject`;
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
}

// Модальное окно: Изменить роль модератора
function showEditModeratorModal(userId, userName, role, canManage) {
    document.getElementById('edit-moderator-modal').classList.remove('hidden');
    document.getElementById('edit-moderator-name').textContent = userName;
    document.getElementById('edit-role').value = role || '';
    document.getElementById('edit-can-manage').checked = canManage;
    document.getElementById('edit-moderator-form').action = `/companies/{{ $company->id }}/moderators/${userId}`;
}

function closeEditModeratorModal() {
    document.getElementById('edit-moderator-modal').classList.add('hidden');
}

// Закрытие модальных окон по клику вне их
window.onclick = function(event) {
    if (event.target.id === 'approve-modal') {
        closeApproveModal();
    }
    if (event.target.id === 'reject-modal') {
        closeRejectModal();
    }
    if (event.target.id === 'edit-moderator-modal') {
        closeEditModeratorModal();
    }
}
</script>
@endpush
@endsection
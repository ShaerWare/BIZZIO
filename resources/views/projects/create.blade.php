
@extends('layouts.app')

@section('title', 'Создание проекта')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Создание проекта</h1>
            <a href="{{ route('projects.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Назад к списку
            </a>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Название проекта -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Название проекта <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name"
                               value="{{ old('name') }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Компания-заказчик -->
                    <div class="mb-6">
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Компания-заказчик <span class="text-red-500">*</span>
                        </label>
                        <select name="company_id" id="company_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Выберите компанию</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Краткое описание -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Краткое описание</label>
                        <textarea name="description" id="description" rows="3" maxlength="500"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                    </div>

                    <!-- Полное описание -->
                    <div class="mb-6">
                        <label for="full_description" class="block text-sm font-medium text-gray-700 mb-2">Полное описание</label>
                        <textarea name="full_description" id="full_description" rows="8"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('full_description') }}</textarea>
                    </div>

                    <!-- Аватар -->
                    <div class="mb-6">
                        <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">Аватар проекта</label>
                        <input type="file" name="avatar" id="avatar" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                    <!-- Даты -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Дата начала <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" id="start_date" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Дата окончания</label>
                            <input type="date" name="end_date" id="end_date"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Проект продолжается -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_ongoing" id="is_ongoing" value="1"
                                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <span class="ml-2 text-sm text-gray-700">Проект выполняется по настоящее время</span>
                        </label>
                    </div>

                    <!-- Статус -->
                    <div class="mb-6">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Статус проекта <span class="text-red-500">*</span></label>
                        <select name="status" id="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach(\App\Models\Project::getStatuses() as $value => $label)
                                <option value="{{ $value }}" {{ old('status', 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Участники -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Компании-участники (необязательно)</label>
                        <div id="participants-container" class="space-y-3"></div>
                        <button type="button" id="add-participant"
                                class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Добавить участника
                        </button>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('projects.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Создать проект
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const allCompanies = @json($allCompanies);
    const participantRoles = @json(\App\Models\Project::getParticipantRoles());
    let participantIndex = 0;

    document.getElementById('add-participant').addEventListener('click', function() {
        const container = document.getElementById('participants-container');
        const html = `
            <div class="participant-item border border-gray-300 rounded-lg p-4 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Компания</label>
                        <select name="participants[${participantIndex}][company_id]" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                            <option value="">Выберите компанию</option>
                            ${allCompanies.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Роль</label>
                        <select name="participants[${participantIndex}][role]" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                            ${Object.entries(participantRoles).map(([v, l]) => `<option value="${v}">${l}</option>`).join('')}
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="this.closest('.participant-item').remove()"
                                class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 text-sm font-medium">
                            Удалить
                        </button>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Описание участия</label>
                    <textarea name="participants[${participantIndex}][participation_description]" rows="2"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        participantIndex++;
    });

    document.getElementById('is_ongoing').addEventListener('change', function() {
        const endDate = document.getElementById('end_date');
        if (this.checked) {
            endDate.value = '';
            endDate.disabled = true;
        } else {
            endDate.disabled = false;
        }
    });
</script>
@endpush
@endsection

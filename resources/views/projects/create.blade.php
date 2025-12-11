<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Создание проекта') }}
            </h2>
            <a href="{{ route('projects.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Назад к списку
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Компания-заказчик -->
                        <div class="mb-6">
                            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Компания-заказчик <span class="text-red-500">*</span>
                            </label>
                            <select name="company_id" 
                                    id="company_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('company_id') border-red-500 @enderror">
                                <option value="">Выберите компанию</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-sm text-gray-500 mt-1">
                                Вы можете создать проект только от имени компании, где являетесь модератором
                            </p>
                        </div>

                        <!-- Краткое описание -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Краткое описание
                            </label>
                            <textarea name="description" 
                                      id="description"
                                      rows="3"
                                      maxlength="500"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-sm text-gray-500 mt-1">До 500 символов. Будет отображаться в каталоге проектов.</p>
                        </div>

                        <!-- Полное описание -->
                        <div class="mb-6">
                            <label for="full_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Полное описание
                            </label>
                            <textarea name="full_description" 
                                      id="full_description"
                                      rows="8"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('full_description') border-red-500 @enderror">{{ old('full_description') }}</textarea>
                            @error('full_description')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-sm text-gray-500 mt-1">Подробное описание целей, задач и результатов проекта.</p>
                        </div>

                        <!-- Аватар проекта -->
                        <div class="mb-6">
                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">
                                Аватар проекта (обложка)
                            </label>
                            <input type="file" 
                                   name="avatar" 
                                   id="avatar"
                                   accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 @error('avatar') border-red-500 @enderror">
                            @error('avatar')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-sm text-gray-500 mt-1">Рекомендуемый размер: 800x600px. Максимум 2MB. Форматы: JPG, PNG, WebP.</p>
                        </div>

                        <!-- Сроки проекта -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Дата начала -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Дата начала <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="start_date" 
                                       id="start_date"
                                       value="{{ old('start_date') }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-500 @enderror">
                                @error('start_date')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Дата окончания -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Дата окончания
                                </label>
                                <input type="date" 
                                       name="end_date" 
                                       id="end_date"
                                       value="{{ old('end_date') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror">
                                @error('end_date')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Проект продолжается -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="is_ongoing" 
                                       id="is_ongoing"
                                       value="1"
                                       {{ old('is_ongoing') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Проект выполняется по настоящее время
                                </span>
                            </label>
                        </div>

                        <!-- Статус проекта -->
                        <div class="mb-6">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Статус проекта <span class="text-red-500">*</span>
                            </label>
                            <select name="status" 
                                    id="status"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-500 @enderror">
                                @foreach(\App\Models\Project::getStatuses() as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', 'active') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Участники проекта (опционально) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Компании-участники (необязательно)
                            </label>
                            <div id="participants-container" class="space-y-3">
                                <!-- Шаблон участника будет добавлен через JavaScript -->
                            </div>
                            <button type="button" 
                                    id="add-participant"
                                    class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Добавить участника
                            </button>
                            <p class="text-sm text-gray-500 mt-2">Вы можете добавить компании-участники сейчас или позже.</p>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('projects.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                                Отмена
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
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
        // Данные компаний для участников
        const allCompanies = @json($allCompanies);
        const participantRoles = @json(\App\Models\Project::getParticipantRoles());

        let participantIndex = 0;

        // Добавление участника
        document.getElementById('add-participant').addEventListener('click', function() {
            const container = document.getElementById('participants-container');
            const participantHtml = `
                <div class="participant-item border border-gray-300 rounded-lg p-4 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Компания</label>
                            <select name="participants[${participantIndex}][company_id]" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    required>
                                <option value="">Выберите компанию</option>
                                ${allCompanies.map(company => `<option value="${company.id}">${company.name}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Роль</label>
                            <select name="participants[${participantIndex}][role]" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    required>
                                ${Object.entries(participantRoles).map(([value, label]) => `<option value="${value}">${label}</option>`).join('')}
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" 
                                    onclick="this.closest('.participant-item').remove()"
                                    class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 text-sm font-medium">
                                Удалить
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Описание участия (необязательно)</label>
                        <textarea name="participants[${participantIndex}][participation_description]" 
                                  rows="2"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', participantHtml);
            participantIndex++;
        });

        // Управление чекбоксом "Проект продолжается"
        const isOngoingCheckbox = document.getElementById('is_ongoing');
        const endDateInput = document.getElementById('end_date');

        isOngoingCheckbox.addEventListener('change', function() {
            if (this.checked) {
                endDateInput.value = '';
                endDateInput.disabled = true;
                endDateInput.classList.add('bg-gray-100');
            } else {
                endDateInput.disabled = false;
                endDateInput.classList.remove('bg-gray-100');
            }
        });
    </script>
    @endpush
</x-app-layout>
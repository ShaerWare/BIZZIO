<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Разместить аукцион') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('auctions.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Компания-организатор -->
                        <div class="mb-6">
                            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Компания-организатор *
                            </label>
                            @if($companies->count() === 1)
                                <input type="hidden" name="company_id" value="{{ $companies->first()->id }}">
                                <div class="p-3 bg-gray-50 rounded-md">
                                    <p class="text-sm font-medium text-gray-900">{{ $companies->first()->name }}</p>
                                </div>
                            @else
                                <select name="company_id" 
                                        id="company_id" 
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Выберите компанию</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @error('company_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Название аукциона -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Название аукциона *
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   value="{{ old('title') }}"
                                   required
                                   maxlength="255"
                                   placeholder="Например: Поставка офисной мебели"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Описание -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Описание
                            </label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="5"
                                      placeholder="Подробное описание предмета аукциона"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Тип процедуры -->
                        <div class="mb-6">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                Тип процедуры *
                            </label>
                            <select name="type" 
                                    id="type" 
                                    required
                                    onchange="toggleInvitations()"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="open" {{ old('type') === 'open' ? 'selected' : '' }}>Открытая (любая компания может участвовать)</option>
                                <option value="closed" {{ old('type') === 'closed' ? 'selected' : '' }}>Закрытая (участвуют только приглашённые)</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Приглашения (только для закрытых) -->
                        <div id="invitations-block" class="mb-6" style="display: none;">
                            <label for="invited_companies" class="block text-sm font-medium text-gray-700 mb-2">
                                Приглашённые компании *
                            </label>
                            <select name="invited_companies[]" 
                                    id="invited_companies" 
                                    multiple
                                    size="10"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Удерживайте Ctrl (Cmd на Mac) для выбора нескольких компаний</p>
                            @error('invited_companies')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Даты -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Начало приёма заявок *
                                </label>
                                <input type="datetime-local" 
                                       name="start_date" 
                                       id="start_date" 
                                       value="{{ old('start_date') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Время МСК+3</p>
                                @error('start_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Окончание приёма заявок *
                                </label>
                                <input type="datetime-local" 
                                       name="end_date" 
                                       id="end_date" 
                                       value="{{ old('end_date') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Время МСК+3</p>
                                @error('end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="trading_start" class="block text-sm font-medium text-gray-700 mb-2">
                                    Начало торгов *
                                </label>
                                <input type="datetime-local" 
                                       name="trading_start" 
                                       id="trading_start" 
                                       value="{{ old('trading_start') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Время МСК+3</p>
                                @error('trading_start')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Начальная цена и шаг -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="starting_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Начальная (максимальная) цена, ₽ *
                                </label>
                                <input type="number" 
                                       name="starting_price" 
                                       id="starting_price" 
                                       value="{{ old('starting_price') }}"
                                       step="0.01"
                                       min="1"
                                       required
                                       placeholder="100000.00"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('starting_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="step_percent" class="block text-sm font-medium text-gray-700 mb-2">
                                    Шаг аукциона, % *
                                </label>
                                <input type="number" 
                                       name="step_percent" 
                                       id="step_percent" 
                                       value="{{ old('step_percent', '1.00') }}"
                                       step="0.01"
                                       min="0.5"
                                       max="5"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">От 0.5% до 5%</p>
                                @error('step_percent')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Техническое задание -->
                        <div class="mb-6">
                            <label for="technical_specification" class="block text-sm font-medium text-gray-700 mb-2">
                                Техническое задание (PDF)
                            </label>
                            <input type="file" 
                                   name="technical_specification" 
                                   id="technical_specification" 
                                   accept=".pdf"
                                   class="w-full">
                            <p class="mt-1 text-xs text-gray-500">Максимальный размер: 10 МБ</p>
                            @error('technical_specification')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Статус -->
                        <div class="mb-6">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Статус *
                            </label>
                            <select name="status" 
                                    id="status" 
                                    required
                                    onchange="showStatusWarning()"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Черновик (можно редактировать позже)</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Активный (сразу опубликовать)</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Предупреждение при выборе "Активный" -->
                        <div id="active-warning" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400" style="display: none;">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Аукцион будет опубликован сразу после создания и станет доступен участникам. Убедитесь, что все данные указаны корректно.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Чекбокс уведомления -->
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <label class="flex items-start">
                                <input type="checkbox" 
                                       name="notification_agreement" 
                                       required
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                                <span class="ml-3 text-sm text-gray-700">
                                    Я уведомлен, что процедура проведения Аукциона регулируется внутренними правилами платформы и не обязывает к заключению договора. 
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800">Правила проведения аукционов</a>. *
                                </span>
                            </label>
                            @error('notification_agreement')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('auctions.index') }}" 
                               class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Отмена
                            </a>
                            
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Создать аукцион
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Показать/скрыть блок приглашений
        function toggleInvitations() {
            const type = document.getElementById('type').value;
            const invitationsBlock = document.getElementById('invitations-block');
            
            if (type === 'closed') {
                invitationsBlock.style.display = 'block';
            } else {
                invitationsBlock.style.display = 'none';
            }
        }

        // Показать предупреждение при выборе статуса "Активный"
        function showStatusWarning() {
            const status = document.getElementById('status').value;
            const warning = document.getElementById('active-warning');
            
            if (status === 'active') {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            toggleInvitations();
            showStatusWarning();
        });
    </script>
</x-app-layout>
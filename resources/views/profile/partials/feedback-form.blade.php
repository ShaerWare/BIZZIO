<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Обратная связь
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Сообщите о проблеме или предложите улучшение. Ваше сообщение будет отправлено администрации платформы.
        </p>
    </header>

    <form method="POST" action="{{ route('profile.feedback') }}" class="mt-6 space-y-6">
        @csrf

        <!-- ФИО (автозаполнение) -->
        <div>
            <label for="feedback_name" class="block text-sm font-medium text-gray-700">ФИО</label>
            <input id="feedback_name"
                   type="text"
                   name="name"
                   value="{{ old('name', auth()->user()->name) }}"
                   required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Компания (опционально) -->
        <div>
            <label for="feedback_company" class="block text-sm font-medium text-gray-700">Компания <span class="text-gray-400">(необязательно)</span></label>
            @php
                $userCompanies = auth()->user()->moderatedCompanies;
            @endphp
            @if($userCompanies->count() > 0)
                <select id="feedback_company"
                        name="company"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— Не указывать —</option>
                    @foreach($userCompanies as $company)
                        <option value="{{ $company->name }}" {{ old('company') === $company->name ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            @else
                <input id="feedback_company"
                       type="text"
                       name="company"
                       value="{{ old('company') }}"
                       placeholder="Название компании"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @endif
        </div>

        <!-- Текст обращения -->
        <div>
            <label for="feedback_message" class="block text-sm font-medium text-gray-700">Сообщение <span class="text-red-500">*</span></label>
            <textarea id="feedback_message"
                      name="message"
                      rows="5"
                      required
                      placeholder="Опишите проблему или предложение..."
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('message') }}</textarea>
            @error('message')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                Отправить
            </button>

            @if (session('status') === 'feedback-sent')
                <p x-data="{ show: true }"
                   x-show="show"
                   x-transition
                   x-init="setTimeout(() => show = false, 5000)"
                   class="text-sm text-green-600">
                    Сообщение отправлено! Спасибо за обратную связь.
                </p>
            @endif
        </div>
    </form>
</section>

@extends('layouts.app')

@section('title', 'Тендеры')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Тендеры</h1>
                <p class="mt-1 text-sm text-gray-500">Запросы цен и аукционы</p>
            </div>
            @auth
                @if(auth()->user()->isModeratorOfAnyCompany())
                    {{-- #282: размещается только двухэтапная закупка (коммерческий аукцион) --}}
                    <a href="{{ route('rfqs.create', ['procedure' => 'commercial']) }}"
                       class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Создать закупку
                    </a>
                @endif
            @endauth
        </div>

        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                {{-- #285 Автопоиск: фильтры применяются сами, кнопки «Применить» нет --}}
                <form method="GET" action="{{ route('tenders.index') }}" class="space-y-4" x-data="{}" data-autofilter>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <!-- Поиск -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                            <input type="text" name="search" id="search"
                                   value="{{ request('search') }}"
                                   @input.debounce.600ms="$el.form.requestSubmit()"
                                   placeholder="Название или номер"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        {{-- #284 Компания-организатор: подсказки появляются при вводе от 2 символов --}}
                        <div x-data="organizerFilter()" class="relative">
                            <label for="organizer" class="block text-sm font-medium text-gray-700 mb-2">Компания-организатор</label>
                            <input type="text" name="organizer" id="organizer"
                                   x-model="query"
                                   @input.debounce.300ms="fetchSuggestions()"
                                   @focus="if (suggestions.length) open = true"
                                   @keydown.escape="open = false"
                                   autocomplete="off"
                                   placeholder="Название компании"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <input type="hidden" name="organizer_id" x-ref="organizerId" value="{{ request('organizer_id') }}">

                            <div x-show="open && suggestions.length > 0"
                                 @click.away="open = false"
                                 x-transition
                                 class="absolute z-50 mt-1 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 max-h-60 overflow-auto"
                                 style="display: none;">
                                <template x-for="company in suggestions" :key="company.id">
                                    <button type="button"
                                            @click="select(company)"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            x-text="company.name"></button>
                                </template>
                            </div>
                        </div>

                        <!-- Вид процедуры -->
                        <div>
                            <label for="kind" class="block text-sm font-medium text-gray-700 mb-2">Вид процедуры</label>
                            <select name="kind" id="kind"
                                    @change="$el.form.requestSubmit()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все виды</option>
                                <option value="rfq" {{ request('kind') === 'rfq' ? 'selected' : '' }}>Запрос цен</option>
                                <option value="auction" {{ request('kind') === 'auction' ? 'selected' : '' }}>Аукцион</option>
                            </select>
                        </div>

                        <!-- Статус -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <select name="status" id="status"
                                    @change="$el.form.requestSubmit()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все статусы</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Приём заявок</option>
                                <option value="trading" {{ request('status') === 'trading' ? 'selected' : '' }}>Торги (аукционы)</option>
                                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Завершённые</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Черновики</option>
                            </select>
                        </div>

                        <!-- Тип -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Тип процедуры</label>
                            <select name="type" id="type"
                                    @change="$el.form.requestSubmit()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Все типы</option>
                                <option value="open" {{ request('type') === 'open' ? 'selected' : '' }}>Открытые</option>
                                <option value="closed" {{ request('type') === 'closed' ? 'selected' : '' }}>Закрытые</option>
                            </select>
                        </div>

                        {{-- #285 Кнопка «Применить» убрана: поиск идёт автоматически --}}
                        <div class="flex items-end">
                            <a href="{{ route('tenders.index') }}"
                               class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                                Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список тендеров -->
        @if($items->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Тендеры не найдены</h3>
                    <p class="mt-1 text-sm text-gray-500">Попробуйте изменить параметры поиска</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($items as $item)
                    @if($item['kind'] === 'rfq')
                        <x-rfq-card :rfq="$item['model']" />
                    @else
                        <x-auction-card :auction="$item['model']" />
                    @endif
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="mt-6">
                {{ $items->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    // #284 Автокомплит по компаниям-организаторам в фильтрах закупок.
    function organizerFilter() {
        return {
            query: @json(request('organizer', '')),
            suggestions: [],
            open: false,

            fetchSuggestions() {
                // Ручная правка текста сбрасывает ранее выбранную компанию:
                // иначе фильтр продолжил бы искать по старому organizer_id.
                this.$refs.organizerId.value = '';

                if (this.query.trim().length < 2) {
                    this.suggestions = [];
                    this.open = false;
                    return;
                }

                fetch('{{ route('tenders.organizers') }}?q=' + encodeURIComponent(this.query))
                    .then(response => response.json())
                    .then(data => {
                        this.suggestions = Array.isArray(data) ? data : [];
                        this.open = this.suggestions.length > 0;
                    })
                    .catch(() => {
                        this.suggestions = [];
                        this.open = false;
                    });
            },

            select(company) {
                this.query = company.name;
                this.$refs.organizerId.value = company.id;
                this.open = false;
                // #285 Выбор компании сразу применяет фильтр
                this.$el.closest('form').requestSubmit();
            },
        };
    }

    // #285 Автопоиск в фильтрах закупок: форма отправляется сама, без кнопки подтверждения.
    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (! form.matches('form[data-autofilter]')) {
            return;
        }

        // Перезагрузка сбивает фокус — запоминаем поле и позицию курсора, чтобы вернуть их.
        const active = document.activeElement;
        if (active && active.name && form.contains(active)) {
            sessionStorage.setItem('tendersFilterFocus', JSON.stringify({
                name: active.name,
                position: typeof active.selectionStart === 'number' ? active.selectionStart : null,
            }));
        }

        // Пустые фильтры не должны попадать в строку запроса.
        Array.from(form.elements).forEach(function (element) {
            if (element.name && element.value === '') {
                element.disabled = true;
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[data-autofilter]');
        const saved = sessionStorage.getItem('tendersFilterFocus');
        sessionStorage.removeItem('tendersFilterFocus');

        if (! form || ! saved) {
            return;
        }

        try {
            const state = JSON.parse(saved);
            const field = form.elements[state.name];

            if (! field) {
                return;
            }

            field.focus();

            if (state.position !== null && typeof field.setSelectionRange === 'function') {
                field.setSelectionRange(state.position, state.position);
            }
        } catch (error) {
            // повреждённое значение в sessionStorage не должно ломать страницу
        }
    });
</script>
@endpush

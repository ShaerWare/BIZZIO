@extends('layouts.app')

@section('title', 'Редактировать Запрос цен')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Заголовок -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Редактировать Запрос цен</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $rfq->number }} — {{ $rfq->title }}</p>
        </div>

        <!-- Форма -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('rfqs.update', $rfq) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Название -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Название <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               required
                               value="{{ old('title', $rfq->title) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('title') border-red-500 @enderror">
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
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('description') border-red-500 @enderror">{{ old('description', $rfq->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Дата окончания -->
                    <div class="mb-6">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Дата окончания приёма заявок <span class="text-red-500">*</span>
                        </label>
                        <x-datetime-input name="end_date"
                                          :value="old('end_date', $rfq->end_date->format('Y-m-d\TH:i'))"
                                          :required="true"
                                          :error="$errors->has('end_date')" />
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">UTC +3 (Москва). Текущее значение: {{ $rfq->end_date->format('d.m.Y H:i') }}</p>
                    </div>

                    <!-- Техническое задание (замена) -->
                    <div class="mb-6">
                        <label for="technical_specification" class="block text-sm font-medium text-gray-700 mb-2">
                            Заменить техническое задание (PDF)
                        </label>
                        
                        @if($rfq->hasMedia('technical_specification'))
                            <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Текущий файл</p>
                                        <p class="text-xs text-gray-500">{{ $rfq->getFirstMedia('technical_specification')->file_name }}</p>
                                    </div>
                                </div>
                                <a href="{{ $rfq->getFirstMediaUrl('technical_specification') }}" 
                                   target="_blank"
                                   class="text-sm text-emerald-600 hover:text-emerald-500">
                                    Просмотр
                                </a>
                            </div>
                        @endif
                        
                        <input type="file" 
                               name="technical_specification" 
                               id="technical_specification" 
                               accept="application/pdf"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('technical_specification') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Оставьте пустым, если не хотите заменять файл. Максимальный размер: 10 МБ</p>
                        @error('technical_specification')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Кнопки -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('rfqs.show', $rfq) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                            Отмена
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- #90: Управление приглашениями компаний (AJAX, вне основной формы) --}}
        @if($rfq->status === 'draft')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6" x-data="editCompanyInviter()">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Приглашённые компании</h2>

                    {{-- Поиск компаний --}}
                    <div class="relative mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пригласить компанию</label>
                        <input type="text"
                               x-model="query"
                               @input.debounce.300ms="search()"
                               @click.away="showResults = false"
                               @focus="if (results.length) showResults = true"
                               placeholder="Поиск по названию или ИНН..."
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">

                        <div x-show="showResults && results.length > 0" x-cloak
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="company in results" :key="company.id">
                                <button type="button"
                                        @click="invite(company)"
                                        class="w-full text-left px-3 py-2 hover:bg-emerald-50 border-b border-gray-100 last:border-0">
                                    <p class="text-sm font-medium text-gray-900" x-text="company.title"></p>
                                    <p class="text-xs text-gray-500" x-text="company.subtitle"></p>
                                </button>
                            </template>
                        </div>

                        <div x-show="searching" x-cloak class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg p-3 text-center text-xs text-gray-500">
                            Поиск...
                        </div>
                    </div>

                    <p x-show="message" x-cloak x-text="message" class="text-sm mb-3"
                       :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'"></p>

                    {{-- Список приглашённых --}}
                    @if($rfq->invitations->count() > 0)
                        <div class="space-y-2">
                            <p class="text-sm text-gray-500 font-medium">Приглашённые ({{ $rfq->invitations->count() }}):</p>
                            @foreach($rfq->invitations as $inv)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs text-gray-500 font-semibold">
                                                {{ strtoupper(substr($inv->company->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $inv->company->name }}</span>
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $inv->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ($inv->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                                {{ $inv->status === 'pending' ? 'Ожидает' : ($inv->status === 'accepted' ? 'Принято' : 'Отклонено') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Приглашений нет. Используйте поиск выше для приглашения компаний.</p>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    function editCompanyInviter() {
        const rfqCompanyId = {{ $rfq->company_id }};
        const existingInvitationIds = @json($rfq->invitations->pluck('company_id'));

        return {
            query: '',
            results: [],
            showResults: false,
            searching: false,
            message: '',
            messageType: 'success',

            async search() {
                if (this.query.length < 2) {
                    this.results = [];
                    this.showResults = false;
                    return;
                }
                this.searching = true;
                this.message = '';
                try {
                    const res = await fetch(`{{ route('search.quick') }}?q=${encodeURIComponent(this.query)}`);
                    const data = await res.json();
                    this.results = data
                        .filter(r => r.type === 'company')
                        .filter(r => r.id !== rfqCompanyId)
                        .filter(r => !existingInvitationIds.includes(r.id));
                    this.showResults = true;
                } catch (e) {
                    this.results = [];
                } finally {
                    this.searching = false;
                }
            },

            async invite(company) {
                this.showResults = false;
                this.query = '';
                this.message = '';
                try {
                    const res = await fetch(`{{ route('rfqs.invitations.store', $rfq) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ company_id: company.id }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.message = `${company.title} — приглашение отправлено`;
                        this.messageType = 'success';
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.message = data.error || 'Ошибка при отправке';
                        this.messageType = 'error';
                    }
                } catch (e) {
                    this.message = 'Ошибка сети';
                    this.messageType = 'error';
                }
            }
        };
    }
</script>
@endpush
@endsection
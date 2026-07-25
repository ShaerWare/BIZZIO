{{-- #185 Конкурсная документация: Извещение / ТЗ / Проект договора / Прочие файлы (только PDF, всего ≤ 20 МБ).
     Параметры: $model (Rfq|Auction|null — для показа уже загруженных файлов), $tzRequired (bool).
     Файлы загружаются во временное хранилище сразу (AJAX) и сохраняются при ошибке валидации формы. --}}
@php
    use App\Support\ProcurementDocuments;

    $model = $model ?? null;
    $tzRequired = $tzRequired ?? false;
    $routePrefix = ($model instanceof \App\Models\Auction) ? 'auctions' : 'rfqs';

    $temp = ProcurementDocuments::tempFiles();

    $fmtSize = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1).' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1).' KB';
        return $bytes.' B';
    };

    // Начальное состояние temp-файлов для Alpine.
    $singleInit = [];
    foreach (ProcurementDocuments::SINGLE_COLLECTIONS as $c) {
        $singleInit[$c] = isset($temp[$c])
            ? ['id' => $temp[$c]['id'] ?? '', 'name' => $temp[$c]['original_name'] ?? '', 'size' => $fmtSize($temp[$c]['size'] ?? 0)]
            : null;
    }
    $otherInit = array_map(
        fn ($f) => ['id' => $f['id'] ?? '', 'name' => $f['original_name'] ?? '', 'size' => $fmtSize($f['size'] ?? 0)],
        $temp['other_documents'] ?? []
    );
@endphp

<div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg"
     x-data="{
        notice: @js($singleInit['notice']),
        technical_specification: @js($singleInit['technical_specification']),
        contract_draft: @js($singleInit['contract_draft']),
        other_documents: @js($otherInit),
        errors: { notice: null, technical_specification: null, contract_draft: null, other_documents: null },
        uploading: { notice: false, technical_specification: false, contract_draft: false, other_documents: false },

        async uploadSingle(collection, event) {
            const file = event.target.files[0];
            if (!file) return;
            this.errors[collection] = null;
            this.uploading[collection] = true;
            const data = await this.send(file, collection);
            this.uploading[collection] = false;
            event.target.value = '';
            if (data && data.success) {
                this[collection] = { id: data.id, name: data.filename, size: data.size };
            } else {
                this.errors[collection] = (data && data.message) || 'Ошибка загрузки';
            }
        },

        async uploadOther(event) {
            const files = Array.from(event.target.files || []);
            this.errors.other_documents = null;
            for (const file of files) {
                this.uploading.other_documents = true;
                const data = await this.send(file, 'other_documents');
                this.uploading.other_documents = false;
                if (data && data.success) {
                    this.other_documents.push({ id: data.id, name: data.filename, size: data.size });
                } else {
                    this.errors.other_documents = (data && data.message) || 'Ошибка загрузки';
                    break;
                }
            }
            event.target.value = '';
        },

        async send(file, collection) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('collection', collection);
            try {
                const res = await fetch('{{ route('procurement-temp-upload.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                return await res.json();
            } catch (e) {
                return { success: false, message: 'Ошибка сети' };
            }
        },

        async removeSingle(collection) {
            await this.remove(collection, null);
            this[collection] = null;
        },

        async removeOther(id) {
            await this.remove('other_documents', id);
            this.other_documents = this.other_documents.filter(f => f.id !== id);
        },

        async remove(collection, id) {
            try {
                await fetch('{{ route('procurement-temp-upload.destroy') }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ collection, id }),
                });
            } catch (e) {}
        }
     }">
    <h3 class="text-lg font-semibold text-gray-900 mb-1">Конкурсная документация</h3>
    <p class="text-sm text-gray-600 mb-4">Только формат PDF. Общий объём всех файлов — не более 20 МБ. Файлы сохраняются при ошибке заполнения формы — повторно прикреплять не нужно.</p>

    {{-- Извещение --}}
    <div class="mb-4">
        <label for="notice" class="block text-sm font-medium text-gray-700 mb-1">Извещение (PDF)</label>
        @if($model && $model->getFirstMedia('notice'))
            <p class="text-xs text-gray-500 mb-1">Текущий файл:
                <a href="{{ route($routePrefix.'.documents.file', [$model, $model->getFirstMedia('notice')]) }}"
                   class="text-emerald-600 underline" target="_blank">{{ $model->getFirstMedia('notice')->file_name }}</a>
            </p>
        @endif

        {{-- Загруженный temp-файл --}}
        <div x-show="notice" x-cloak class="flex items-center justify-between p-2 mb-1 bg-green-50 border border-green-200 rounded">
            <span class="text-sm text-gray-800 truncate" x-text="notice ? (notice.name + ' (' + notice.size + ')') : ''"></span>
            <button type="button" @click="removeSingle('notice')" class="ml-2 text-red-600 hover:text-red-800 text-sm">Удалить</button>
        </div>

        <input type="file" name="notice" id="notice" accept="application/pdf" x-show="!notice"
               @change="uploadSingle('notice', $event)"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('notice') border border-red-500 rounded @enderror">
        <span x-show="uploading.notice" x-cloak class="text-xs text-gray-500">Загрузка…</span>
        <p x-show="errors.notice" x-cloak x-text="errors.notice" class="mt-1 text-sm text-red-600"></p>
        @error('notice')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Техническое задание (ТЗ) --}}
    <div class="mb-4">
        <label for="technical_specification" class="block text-sm font-medium text-gray-700 mb-1">
            Техническое задание (ТЗ) (PDF) @if($tzRequired)<span class="text-red-500">*</span>@endif
        </label>
        @if($model && $model->getFirstMedia('technical_specification'))
            <p class="text-xs text-gray-500 mb-1">Текущий файл:
                <a href="{{ route($routePrefix.'.documents.file', [$model, $model->getFirstMedia('technical_specification')]) }}"
                   class="text-emerald-600 underline" target="_blank">{{ $model->getFirstMedia('technical_specification')->file_name }}</a>
            </p>
        @endif

        <div x-show="technical_specification" x-cloak class="flex items-center justify-between p-2 mb-1 bg-green-50 border border-green-200 rounded">
            <span class="text-sm text-gray-800 truncate" x-text="technical_specification ? (technical_specification.name + ' (' + technical_specification.size + ')') : ''"></span>
            <button type="button" @click="removeSingle('technical_specification')" class="ml-2 text-red-600 hover:text-red-800 text-sm">Удалить</button>
        </div>

        <input type="file" name="technical_specification" id="technical_specification" accept="application/pdf" x-show="!technical_specification"
               @change="uploadSingle('technical_specification', $event)"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('technical_specification') border border-red-500 rounded @enderror">
        <span x-show="uploading.technical_specification" x-cloak class="text-xs text-gray-500">Загрузка…</span>
        <p x-show="errors.technical_specification" x-cloak x-text="errors.technical_specification" class="mt-1 text-sm text-red-600"></p>
        @error('technical_specification')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Проект договора --}}
    <div class="mb-4">
        <label for="contract_draft" class="block text-sm font-medium text-gray-700 mb-1">Проект договора (PDF)</label>
        @if($model && $model->getFirstMedia('contract_draft'))
            <p class="text-xs text-gray-500 mb-1">Текущий файл:
                <a href="{{ route($routePrefix.'.documents.file', [$model, $model->getFirstMedia('contract_draft')]) }}"
                   class="text-emerald-600 underline" target="_blank">{{ $model->getFirstMedia('contract_draft')->file_name }}</a>
            </p>
        @endif

        <div x-show="contract_draft" x-cloak class="flex items-center justify-between p-2 mb-1 bg-green-50 border border-green-200 rounded">
            <span class="text-sm text-gray-800 truncate" x-text="contract_draft ? (contract_draft.name + ' (' + contract_draft.size + ')') : ''"></span>
            <button type="button" @click="removeSingle('contract_draft')" class="ml-2 text-red-600 hover:text-red-800 text-sm">Удалить</button>
        </div>

        <input type="file" name="contract_draft" id="contract_draft" accept="application/pdf" x-show="!contract_draft"
               @change="uploadSingle('contract_draft', $event)"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('contract_draft') border border-red-500 rounded @enderror">
        <span x-show="uploading.contract_draft" x-cloak class="text-xs text-gray-500">Загрузка…</span>
        <p x-show="errors.contract_draft" x-cloak x-text="errors.contract_draft" class="mt-1 text-sm text-red-600"></p>
        @error('contract_draft')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Прочие файлы (несколько) --}}
    <div class="mb-1">
        <label for="other_documents" class="block text-sm font-medium text-gray-700 mb-1">Прочие файлы (PDF, можно несколько)</label>
        @if($model && $model->getMedia('other_documents')->isNotEmpty())
            <ul class="text-xs text-gray-500 mb-1 list-disc list-inside">
                @foreach($model->getMedia('other_documents') as $doc)
                    <li><a href="{{ route($routePrefix.'.documents.file', [$model, $doc]) }}"
                           class="text-emerald-600 underline" target="_blank">{{ $doc->file_name }}</a></li>
                @endforeach
            </ul>
        @endif

        {{-- Загруженные temp-файлы --}}
        <template x-for="doc in other_documents" :key="doc.id">
            <div class="flex items-center justify-between p-2 mb-1 bg-green-50 border border-green-200 rounded">
                <span class="text-sm text-gray-800 truncate" x-text="doc.name + ' (' + doc.size + ')'"></span>
                <button type="button" @click="removeOther(doc.id)" class="ml-2 text-red-600 hover:text-red-800 text-sm">Удалить</button>
            </div>
        </template>

        <input type="file" name="other_documents[]" id="other_documents" accept="application/pdf" multiple
               @change="uploadOther($event)"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('other_documents.*') border border-red-500 rounded @enderror">
        <span x-show="uploading.other_documents" x-cloak class="text-xs text-gray-500">Загрузка…</span>
        <p x-show="errors.other_documents" x-cloak x-text="errors.other_documents" class="mt-1 text-sm text-red-600"></p>
        @error('other_documents')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('other_documents.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

{{-- #185 Конкурсная документация: Извещение / ТЗ / Проект договора / Прочие файлы (только PDF, всего ≤ 20 МБ).
     Параметры: $model (Rfq|Auction|null — для показа уже загруженных файлов), $tzRequired (bool). --}}
@php
    $model = $model ?? null;
    $tzRequired = $tzRequired ?? false;
    $routePrefix = ($model instanceof \App\Models\Auction) ? 'auctions' : 'rfqs';
@endphp

<div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
    <h3 class="text-lg font-semibold text-gray-900 mb-1">Конкурсная документация</h3>
    <p class="text-sm text-gray-600 mb-4">Только формат PDF. Общий объём всех файлов — не более 20 МБ.</p>

    {{-- Извещение --}}
    <div class="mb-4">
        <label for="notice" class="block text-sm font-medium text-gray-700 mb-1">Извещение (PDF)</label>
        @if($model && $model->getFirstMedia('notice'))
            <p class="text-xs text-gray-500 mb-1">Текущий файл:
                <a href="{{ route($routePrefix.'.documents.file', [$model, $model->getFirstMedia('notice')]) }}"
                   class="text-emerald-600 underline" target="_blank">{{ $model->getFirstMedia('notice')->file_name }}</a>
            </p>
        @endif
        <input type="file" name="notice" id="notice" accept="application/pdf"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('notice') border border-red-500 rounded @enderror">
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
        <input type="file" name="technical_specification" id="technical_specification" accept="application/pdf"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('technical_specification') border border-red-500 rounded @enderror">
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
        <input type="file" name="contract_draft" id="contract_draft" accept="application/pdf"
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('contract_draft') border border-red-500 rounded @enderror">
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
        <input type="file" name="other_documents[]" id="other_documents" accept="application/pdf" multiple
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('other_documents.*') border border-red-500 rounded @enderror">
        @error('other_documents')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('other_documents.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

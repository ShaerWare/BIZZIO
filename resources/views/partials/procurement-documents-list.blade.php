{{-- #185 Список конкурсной документации на странице процедуры + скачивание архивом.
     Параметр: $model (Rfq|Auction). Доступ проверяется через documentsAccessibleBy(). --}}
@php
    $routePrefix = ($model instanceof \App\Models\Auction) ? 'auctions' : 'rfqs';
    $docs = $model->allProcurementDocuments();
    $canAccessDocs = $model->documentsAccessibleBy(auth()->user());
    $docLabels = \App\Support\ProcurementDocuments::COLLECTIONS;
    // #296 Срок, до которого документация доступна после завершения процедуры
    $availableUntil = $model->documentsAvailableUntil();
    $retentionExpired = $model->documentsRetentionExpired();
@endphp

@if($docs->isNotEmpty())
    <div class="bg-gray-50 rounded-lg p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-2">Конкурсная документация</h3>

        @if($canAccessDocs)
            <ul class="space-y-1 mb-3">
                @foreach($docs as $doc)
                    <li class="text-sm flex items-start">
                        <svg class="w-4 h-4 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <a href="{{ route($routePrefix.'.documents.file', [$model, $doc]) }}" target="_blank"
                           class="text-emerald-600 hover:text-emerald-500">
                            <span class="text-gray-500">{{ $docLabels[$doc->collection_name] ?? 'Документ' }}:</span> {{ $doc->file_name }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route($routePrefix.'.documents.archive', $model) }}"
               class="block w-full text-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                Скачать всё архивом (ZIP)
            </a>
            @if($availableUntil)
                {{-- #296 После завершения документация доступна ограниченное время --}}
                <p class="mt-2 text-xs text-gray-500">
                    Документация доступна для скачивания до {{ $availableUntil->format('d.m.Y') }}, затем удаляется с сервера.
                </p>
            @endif
        @elseif($retentionExpired)
            <p class="text-sm text-gray-500">
                Конкурсная документация удалена: срок хранения после завершения процедуры истёк
                {{ $availableUntil->format('d.m.Y') }}.
            </p>
        @else
            <p class="text-sm text-gray-500">
                Доступ к конкурсной документации закрыт после завершения процедуры —
                файлы доступны только организатору и компаниям-участникам
                @if($availableUntil)
                    до {{ $availableUntil->format('d.m.Y') }}
                @endif
                .
            </p>
        @endif
    </div>
@endif

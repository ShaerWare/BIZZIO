{{-- #193 Кнопка «Поделиться»: готовый текст приглашения сторонней компании (email/мессенджер).
     Параметр: $model (Rfq|Auction). Показывать только организатору. --}}
@php
    $isAuction = $model instanceof \App\Models\Auction;
    $routePrefix = $isAuction ? 'auctions' : 'rfqs';
    $kind = $isAuction ? 'Аукцион' : ($model->isCommercial() ? 'Коммерческий аукцион' : 'Запрос цен');
    $showUrl = route($routePrefix.'.show', $model);
    $docsUrl = $model->hasAnyProcurementDocument() ? route($routePrefix.'.documents.archive', $model) : null;

    $lines = [
        'Здравствуйте,',
        '',
        'Приглашаем Вашу компанию принять участие в '.$kind.' — «'.$model->title.'».',
        'Ссылка на закупку: '.$showUrl,
    ];
    if ($docsUrl) {
        $lines[] = 'Ссылка на скачивание конкурсной документации: '.$docsUrl;
    }
    $lines[] = 'Приём заявок: '.$model->start_date->format('d.m.Y H:i').' — '.$model->end_date->format('d.m.Y H:i').' (МСК)';
    if ($isAuction && $model->trading_start) {
        $lines[] = 'Начало торгов: '.$model->trading_start->format('d.m.Y H:i').' (МСК)';
    } elseif (! $isAuction && $model->isCommercial() && $model->trading_start) {
        $lines[] = 'Начало аукциона (этап 2): '.$model->trading_start->format('d.m.Y H:i').' (МСК)';
    }
    $lines[] = 'Организатор: '.$model->company->name;
    if ($model->description) {
        $lines[] = '';
        $lines[] = 'Описание: '.$model->description;
    }
    $lines[] = '';
    $lines[] = 'С уважением,';
    if (optional($model->creator)->name) {
        $lines[] = $model->creator->name;
    }
    $lines[] = $model->company->name;

    $shareText = implode("\n", $lines);
    $mailtoHref = 'mailto:?subject='.rawurlencode($kind.' — '.$model->title).'&body='.rawurlencode($shareText);
@endphp

<div x-data="{
        open: false,
        copied: false,
        text: @js($shareText),
        copy() {
            navigator.clipboard.writeText(this.text).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        }
     }"
     class="bg-gray-50 rounded-lg p-4 mb-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-2">Пригласить стороннюю компанию</h3>
    <p class="text-xs text-gray-600 mb-3">Готовый текст приглашения для компании, ещё не зарегистрированной в Bizzio — скопируйте и отправьте по email или в мессенджер.</p>

    <button type="button" @click="open = !open"
            class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
        </svg>
        Поделиться
    </button>

    <div x-show="open" x-cloak class="mt-3">
        <textarea readonly rows="11" x-text="text"
                  class="w-full text-xs rounded-md border-gray-300 bg-white shadow-sm font-mono focus:border-emerald-500 focus:ring-emerald-500"></textarea>
        <div class="flex flex-col sm:flex-row gap-2 mt-2">
            <button type="button" @click="copy()"
                    class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                <span x-show="!copied">Копировать текст</span>
                <span x-show="copied" x-cloak>Скопировано!</span>
            </button>
            <a href="{{ $mailtoHref }}"
               class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                Отправить по email
            </a>
        </div>
    </div>
</div>

{{-- #270 Параметры торгов коммерческого аукциона, заданные организатором: шаги изменения
     трёх критериев и максимумы срока/аванса. Показываются и на этапе 1 (Rfq), и во время
     самих торгов (Auction) — участник должен видеть правила до подачи предложения.
     Параметр: $procedure (Rfq|Auction — оба хранят одни и те же поля). --}}
@php
    $num = fn ($v, int $decimals = 2) => rtrim(rtrim(number_format((float) $v, $decimals, ',', "\u{00A0}"), '0'), ',');
@endphp

@if($procedure->step_price || $procedure->step_deadline || $procedure->step_advance)
    <div class="bg-gray-50 rounded-lg p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-2">Параметры торгов (этап 2):</h3>
        <ul class="text-sm text-gray-700 space-y-1">
            @if($procedure->step_price)
                <li>• Шаг изменения цены — <strong>{{ $num($procedure->step_price) }}%</strong></li>
            @endif
            @if($procedure->step_deadline)
                <li>• Шаг изменения срока — <strong>{{ (int) $procedure->step_deadline }} дн.</strong></li>
            @endif
            @if($procedure->step_advance)
                <li>• Шаг изменения аванса — <strong>{{ $num($procedure->step_advance) }}%</strong></li>
            @endif
            @if($procedure->max_deadline)
                <li>• Макс. срок выполнения — <strong>{{ (int) $procedure->max_deadline }} дн.</strong></li>
            @endif
            @if($procedure->max_advance)
                <li>• Макс. размер аванса — <strong>{{ $num($procedure->max_advance) }}%</strong></li>
            @endif
        </ul>
        <p class="mt-2 text-xs text-gray-500">
            Каждое следующее предложение должно улучшать хотя бы один критерий не менее чем на его шаг.
            Срок и аванс оцениваются от указанных максимумов.
        </p>
    </div>
@endif

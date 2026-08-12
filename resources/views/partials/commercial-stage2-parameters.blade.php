{{-- #270 Максимумы и шаги критериев, заданные организатором. Выводится компактно —
     двумя строками, чтобы блок параметров не растягивался по вертикали.
     Возвращает готовые <li>: подключать внутри <ul> блока параметров.
     Параметр: $procedure (Rfq|Auction — оба хранят одни и те же поля). --}}
@php
    $num = fn ($v, int $decimals = 2) => rtrim(rtrim(number_format((float) $v, $decimals, ',', "\u{00A0}"), '0'), ',');

    $maxParts = array_filter([
        $procedure->max_deadline ? 'срок '.(int) $procedure->max_deadline.' дн.' : null,
        $procedure->max_advance ? 'аванс '.$num($procedure->max_advance).'%' : null,
    ]);

    $stepParts = array_filter([
        $procedure->step_price ? 'цена '.$num($procedure->step_price).'%' : null,
        $procedure->step_deadline ? 'срок '.(int) $procedure->step_deadline.' дн.' : null,
        $procedure->step_advance ? 'аванс '.$num($procedure->step_advance).'%' : null,
    ]);
@endphp

@if($maxParts)
    <li>• Макс.: {{ implode(' / ', $maxParts) }}</li>
@endif

@if($stepParts)
    <li>• Шаг: {{ implode(' / ', $stepParts) }}</li>
@endif

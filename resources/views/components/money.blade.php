@props([
    'value',
    'currency' => null,
    'decimals' => 2,
])

{{-- #269 Денежная сумма без переносов: разряды разделяются неразрывным пробелом,
     символ валюты не отрывается от числа. Раньше НМЦ рвалась на две строки посреди цифр. --}}
<span {{ $attributes->merge(['class' => 'whitespace-nowrap']) }}>{{ number_format((float) $value, (int) $decimals, ',', "\u{00A0}") }}@if($currency){!! "\u{00A0}" !!}{{ $currency }}@endif</span>

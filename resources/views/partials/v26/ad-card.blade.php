{{-- #181 Рекламный блок эталона v26 (карточка «Реклама» с иллюстрацией рукопожатия).

     Параметры:
       $ctaUrl   — куда ведёт кнопка;
       $ctaLabel — подпись кнопки (по умолчанию «Подробнее»);
       $gradientId — id градиента: на странице может быть несколько экземпляров блока. --}}
@php
    $ctaLabel = $ctaLabel ?? 'Подробнее';
    $gradientId = $gradientId ?? 'handFill';
@endphp
<article class="card ad">
    <div class="ad-label">Реклама</div>
    <h3>Найдите надёжных<br>партнёров для бизнеса<br>на Bizzio</h3>
    <a class="ad-cta" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
    <svg class="handshake" viewBox="0 0 220 180" aria-label="Деловое партнёрство">
        <defs><linearGradient id="{{ $gradientId }}" x1="0" x2="1"><stop offset="0" stop-color="#079b38"/><stop offset="1" stop-color="#62cfa1"/></linearGradient></defs>
        <circle cx="110" cy="88" r="72" fill="#f4fbf8" stroke="#c7daee" stroke-dasharray="6 7"/>
        <circle cx="110" cy="88" r="55" fill="#e4f6ee" stroke="none"/>
        <svg x="43" y="48" width="134" height="107" viewBox="0 0 640 512" aria-hidden="true"><path class="fa-hand" style="fill:url(#{{ $gradientId }})" d="M323.4 85.2l-96.8 78.4c-16.1 13-19.2 36.4-7 53.1c12.9 17.8 38 21.3 55.3 7.8l99.3-77.2c7-5.4 17-4.2 22.5 2.8s4.2 17-2.8 22.5l-20.9 16.2L550.2 352H592c26.5 0 48-21.5 48-48V176c0-26.5-21.5-48-48-48H516h-4-.7l-3.9-2.5L434.8 79c-15.3-9.8-33.2-15-51.4-15c-21.8 0-43 7.5-60 21.2zm22.8 124.4l-51.7 40.2C263 274.4 217.3 268 193.7 235.6c-22.2-30.5-16.6-73.1 12.7-96.8l83.2-67.3c-11.6-4.9-24.1-7.4-36.8-7.4C234 64 215.7 69.6 200 80l-72 48H48c-26.5 0-48 21.5-48 48V304c0 26.5 21.5 48 48 48H156.2l91.4 83.4c19.6 17.9 49.9 16.5 67.8-3.1c5.5-6.1 9.2-13.2 11.1-20.6l17 15.6c19.5 17.9 49.9 16.6 67.8-2.9c4.5-4.9 7.8-10.6 9.9-16.5c19.4 13 45.8 10.3 62.1-7.5c17.9-19.5 16.6-49.9-2.9-67.8l-134.2-123z"/></svg>
        <circle cx="36" cy="47" r="11" fill="#fff" stroke="#d7e3f2"/><circle cx="184" cy="43" r="11" fill="#fff" stroke="#d7e3f2"/><circle cx="176" cy="143" r="11" fill="#fff" stroke="#d7e3f2"/>
        <path d="M32 48c0-5 8-5 8 0M30 53c3-4 9-4 12 0M180 44c0-5 8-5 8 0M178 49c3-4 9-4 12 0M172 144c0-5 8-5 8 0M170 149c3-4 9-4 12 0" stroke="#173263"/>
    </svg>
</article>

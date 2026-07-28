{{-- #189 Живой таймер обратного отсчёта. Параметры:
     $target (Carbon) — момент, до которого считаем; $label (string) — подпись;
     $color (опц., tailwind-классы фона/текста). Самодостаточный (inline Alpine) — можно включать несколько раз. --}}
@php
    $cdColor = $color ?? 'bg-orange-100 text-orange-800';
@endphp
<div x-data="{
        target: new Date('{{ $target->toIso8601String() }}').getTime(),
        now: Date.now(),
        timer: null,
        get expired() { return this.now >= this.target; },
        get display() {
            let s = Math.max(0, Math.floor((this.target - this.now) / 1000));
            const d = Math.floor(s / 86400); s %= 86400;
            const h = Math.floor(s / 3600); s %= 3600;
            const m = Math.floor(s / 60); const sec = s % 60;
            return (d > 0 ? d + ' дн ' : '') + String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
        },
        init() { this.timer = setInterval(() => { this.now = Date.now(); }, 1000); }
     }"
     x-init="init()"
     x-show="!expired"
     class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium {{ $cdColor }}">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span>{{ $label }}:</span>
    <span x-text="display" class="font-mono font-semibold"></span>
</div>

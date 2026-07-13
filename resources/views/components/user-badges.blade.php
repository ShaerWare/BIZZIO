@props(['user' => null])
@php
    $badges = $user?->badges ?? collect();
    $labelled = $badges->filter(fn ($b) => filled($b->label));
@endphp
@if($labelled->isNotEmpty())
    <span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1.5 align-middle']) }}>
        @foreach($labelled as $b)
            <span class="inline-flex items-center rounded-full text-xs font-semibold px-2 py-0.5 leading-tight bg-white"
                  style="border: 2px solid {{ $b->color }}; color: {{ $b->color }};"
                  title="{{ $b->label }}">
                {{ $b->label }}
            </span>
        @endforeach
    </span>
@endif

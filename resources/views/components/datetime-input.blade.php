@props([
    'name',
    'value' => '',
    'required' => false,
    'label' => '',
    'error' => false,
])

@php
    $dateValue = '';
    $timeValue = '';
    if ($value) {
        $parts = explode('T', $value);
        $dateValue = $parts[0] ?? '';
        $timeValue = $parts[1] ?? '';
    }
    $inputId = str_replace(['[', ']', '.'], '_', $name);
@endphp

{{-- F1: Раздельные поля дата + время вместо datetime-local --}}
<div x-data="{
    date: '{{ $dateValue }}',
    time: '{{ $timeValue }}',
    get combined() { return this.date && this.time ? this.date + 'T' + this.time : ''; }
}">
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" :value="combined">
    <div class="grid grid-cols-2 gap-3">
        <div>
            <input type="date"
                   x-model="date"
                   {{ $required ? 'required' : '' }}
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm {{ $error ? 'border-red-500' : '' }}">
        </div>
        <div>
            <input type="time"
                   x-model="time"
                   {{ $required ? 'required' : '' }}
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm {{ $error ? 'border-red-500' : '' }}">
        </div>
    </div>
</div>

{{-- #181 Блок «Bizzio в соцсетях» в меню «Сервисы».

     Аккаунтов ещё нет, поэтому ссылки пустые (как в эталоне v26) и помечены
     data-inactive-feature: клик никуда не ведёт, но уходит в Метрику.
     $scope — 'auth' или 'guest': классы блока в эталоне разные. --}}
@php
    $prefix = ($scope ?? 'auth') === 'guest' ? 'guest' : 'auth';
    $networks = [
        ['id' => 'vk_video', 'label' => 'VK Видео', 'mark' => 'VK', 'color' => '#1674e8'],
        ['id' => 'rutube', 'label' => 'RUTUBE', 'mark' => 'R', 'color' => '#121728'],
        ['id' => 'youtube', 'label' => 'YouTube', 'mark' => '▶', 'color' => '#e21b23'],
        ['id' => 'telegram', 'label' => 'Telegram', 'mark' => '➤', 'color' => '#26a5e4'],
    ];
@endphp
<div class="{{ $prefix }}-social">
    <strong>Bizzio в соцсетях</strong>
    <div class="{{ $prefix }}-social-row">
        @foreach($networks as $network)
            <a href="#"
               data-inactive-feature="social_{{ $network['id'] }}"
               data-feature-label="{{ $network['label'] }}"
               data-placement="{{ $prefix }}_services_drawer">
                <span class="{{ $prefix }}-social-mark" style="background:{{ $network['color'] }}">{{ $network['mark'] }}</span>{{ $network['label'] }}
            </a>
        @endforeach
    </div>
</div>

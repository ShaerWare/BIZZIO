@props([
    'name' => 'avatar',
    'current' => null,
    'aspectRatio' => 1,
    'label' => 'Выберите изображение',
    'hint' => 'JPG, PNG, GIF или WebP. Изображение можно приблизить и обрезать.',
])

{{-- #146: выбор фото + зум/обрезка (Cropper.js). Обрезанное фото уходит в скрытый input[name] --}}
<div x-data="avatarCropper({ aspectRatio: {{ $aspectRatio }} })" class="flex items-start gap-4">
    <img
        x-ref="preview"
        src="{{ $current }}"
        @if(! $current) style="display:none" @endif
        class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 flex-shrink-0"
        alt=""
    >

    <div class="flex-1">
        <x-input-label :value="$label" />
        <input
            type="file"
            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            x-ref="picker"
            @change="pick($event)"
            class="mt-1 block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700
                hover:file:bg-emerald-100 cursor-pointer"
        >
        {{-- именно этот input отправляется на сервер (с обрезанным изображением) --}}
        <input type="file" name="{{ $name }}" x-ref="fileInput" class="hidden">
        <x-input-error class="mt-2" :messages="$errors->get($name)" />
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    </div>

    {{-- Модалка кропа --}}
    <div
        x-show="open"
        @keydown.escape.window="close()"
        style="display:none"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    >
        <div class="bg-white rounded-lg shadow-xl p-4 w-full max-w-lg" @click.outside="close()">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Обрезка изображения</h3>
            <div class="max-h-[60vh] overflow-hidden bg-gray-50">
                <img x-ref="image" :src="imgSrc" class="block max-w-full">
            </div>
            <p class="mt-2 text-xs text-gray-500">Колесо мыши — масштаб, перетаскивание — положение.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="close()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">
                    Отмена
                </button>
                <button type="button" @click="apply()"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">
                    Применить
                </button>
            </div>
        </div>
    </div>
</div>

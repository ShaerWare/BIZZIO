@props([
    'name',
    'collection',
    'accept' => '*',
    'multiple' => false,
    'required' => false,
    'maxSize' => '10MB',
    'label' => 'Выберите файл',
    'hint' => null,
])

@php
    $tempFile = session('temp_uploads.' . $collection);
    $hasTemp = !empty($tempFile);

    // Форматирование размера файла
    $formattedSize = '';
    if ($hasTemp && isset($tempFile['size'])) {
        $bytes = $tempFile['size'];
        if ($bytes >= 1048576) {
            $formattedSize = number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            $formattedSize = number_format($bytes / 1024, 1) . ' KB';
        } else {
            $formattedSize = $bytes . ' B';
        }
    }
@endphp

{{-- F3: Компонент загрузки файла с сохранением при ошибке валидации --}}
<div x-data="{
    uploading: false,
    progress: 0,
    uploaded: {{ $hasTemp ? 'true' : 'false' }},
    filename: '{{ $hasTemp ? addslashes($tempFile['original_name']) : '' }}',
    filesize: '{{ $formattedSize }}',
    error: null,

    async uploadFile(event) {
        const file = event.target.files[0];
        if (!file) return;

        this.uploading = true;
        this.progress = 0;
        this.error = null;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('collection', '{{ $collection }}');

        try {
            const response = await fetch('{{ route('temp-upload.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.uploaded = true;
                this.filename = data.filename;
                this.filesize = data.size;
            } else {
                this.error = data.message || 'Ошибка загрузки';
            }
        } catch (e) {
            this.error = 'Ошибка сети';
        } finally {
            this.uploading = false;
        }
    },

    async removeFile() {
        try {
            await fetch('{{ route('temp-upload.destroy') }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ collection: '{{ $collection }}' }),
            });
        } catch (e) {}

        this.uploaded = false;
        this.filename = '';
        this.filesize = '';
        this.$refs.fileInput.value = '';
    }
}" class="space-y-2">

    {{-- Скрытое поле для отправки информации о temp-файле --}}
    <input type="hidden" name="{{ $name }}_temp" :value="uploaded ? '{{ $collection }}' : ''">

    {{-- Загруженный файл --}}
    <div x-show="uploaded" x-cloak class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center space-x-3">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-sm font-medium text-gray-900" x-text="filename"></p>
                <p class="text-xs text-gray-500" x-text="filesize"></p>
            </div>
        </div>
        <button type="button" @click="removeFile()" class="text-red-600 hover:text-red-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    {{-- Поле загрузки --}}
    <div x-show="!uploaded" x-cloak>
        <input type="file"
               x-ref="fileInput"
               name="{{ $name }}"
               accept="{{ $accept }}"
               {{ $multiple ? 'multiple' : '' }}
               {{ $required && !$hasTemp ? 'required' : '' }}
               @change="uploadFile($event)"
               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
    </div>

    {{-- Прогресс загрузки --}}
    <div x-show="uploading" x-cloak class="flex items-center space-x-3">
        <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm text-gray-600">Загрузка...</span>
    </div>

    {{-- Ошибка --}}
    <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600"></p>

    {{-- Подсказка --}}
    @if($hint)
        <p class="text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>

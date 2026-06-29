@props([
    'label' => 'Документы (PDF)',
    'hint' => 'Максимум 10 файлов по 10MB',
])

{{-- #176: Поочерёдное добавление PDF-документов без зажатия Ctrl.
     Каждый выбор файла добавляется в общий список (а не заменяет его),
     лишние можно убрать до отправки формы. --}}
<div x-data="pdfDocumentsInput()">
    <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>

    <input type="file" name="documents[]" accept=".pdf" multiple
           x-ref="input" class="hidden" @change="addFiles($event)">

    <div class="mt-1 flex items-center gap-3">
        <button type="button" @click="$refs.input.click()"
                class="inline-flex items-center px-4 py-2 bg-emerald-50 text-emerald-700 rounded-md text-sm font-semibold hover:bg-emerald-100 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Добавить файл
        </button>
        <span class="text-xs text-gray-500" x-show="files.length === 0">Файлы не выбраны</span>
    </div>

    <ul class="mt-2 space-y-2" x-show="files.length > 0" x-cloak>
        <template x-for="(file, index) in files" :key="index">
            <li class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                <div class="flex items-center min-w-0">
                    <svg class="w-5 h-5 text-red-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                    </svg>
                    <span class="text-sm text-gray-900 truncate" x-text="file.name"></span>
                </div>
                <button type="button" @click="removeFile(index)"
                        class="text-red-600 hover:text-red-900 text-sm ml-3 flex-shrink-0">
                    Удалить
                </button>
            </li>
        </template>
    </ul>

    <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @error('documents.*')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
function pdfDocumentsInput() {
    return {
        files: [],
        addFiles(event) {
            for (const file of event.target.files) {
                // Избегаем дублей по имени и размеру
                if (! this.files.some(f => f.name === file.name && f.size === file.size)) {
                    this.files.push(file);
                }
            }
            this.sync();
        },
        removeFile(index) {
            this.files.splice(index, 1);
            this.sync();
        },
        // Синхронизируем накопленный список с реальным input через DataTransfer,
        // чтобы форма отправила все выбранные файлы.
        sync() {
            const dataTransfer = new DataTransfer();
            this.files.forEach(file => dataTransfer.items.add(file));
            this.$refs.input.files = dataTransfer.files;
        },
    };
}
</script>
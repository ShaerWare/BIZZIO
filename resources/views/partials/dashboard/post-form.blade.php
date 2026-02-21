<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data"
              x-data="{ preview: null }">
            @csrf
            <div class="flex items-start space-x-3">
                <img src="{{ auth()->user()->avatar_url }}" alt=""
                     class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                <div class="flex-1">
                    <textarea name="body" rows="2" placeholder="{{ __('Что нового?') }}"
                              class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 resize-none"
                              required maxlength="2000">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror

                    <template x-if="preview">
                        <div class="mt-2 relative inline-block">
                            <img :src="preview" class="h-20 rounded-lg object-cover">
                            <button type="button" @click="preview = null; $refs.photoInput.value = ''"
                                    class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                &times;
                            </button>
                        </div>
                    </template>

                    <div class="mt-2 flex items-center justify-between">
                        <label class="cursor-pointer text-gray-500 hover:text-emerald-600">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="file" name="photo" accept="image/*" class="hidden" x-ref="photoInput"
                                   @change="if($event.target.files[0]) { const r = new FileReader(); r.onload = e => preview = e.target.result; r.readAsDataURL($event.target.files[0]); }">
                        </label>
                        @error('photo')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="px-4 py-1.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition">
                            {{ __('Опубликовать') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

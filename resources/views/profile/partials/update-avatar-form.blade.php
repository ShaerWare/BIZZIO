<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Аватар') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Загрузите фото профиля. Максимальный размер: 2 МБ.') }}
        </p>
    </header>

    <div class="mt-6 flex items-start gap-6">
        <!-- Текущий аватар -->
        <div class="flex-shrink-0">
            <img
                src="{{ $user->avatar_url }}"
                alt="{{ $user->name }}"
                class="w-24 h-24 rounded-full object-cover border-2 border-gray-200"
            >
        </div>

        <div class="flex-1 space-y-4">
            <!-- Форма загрузки нового аватара -->
            <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="avatar" :value="__('Выберите изображение')" />
                    <input
                        type="file"
                        id="avatar"
                        name="avatar"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        class="mt-1 block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-emerald-50 file:text-emerald-700
                            hover:file:bg-emerald-100
                            cursor-pointer"
                    >
                    <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, GIF или WebP. Максимум 2 МБ.</p>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Загрузить') }}</x-primary-button>

                    @if (session('status') === 'avatar-updated')
                        <p
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            x-init="setTimeout(() => show = false, 2000)"
                            class="text-sm text-gray-600"
                        >{{ __('Аватар обновлён.') }}</p>
                    @endif
                </div>
            </form>

            <!-- Кнопка удаления аватара -->
            @if($user->avatar)
                <form method="post" action="{{ route('profile.avatar.destroy') }}" class="mt-4">
                    @csrf
                    @method('delete')

                    <button
                        type="submit"
                        class="text-sm text-red-600 hover:text-red-800 underline"
                        onclick="return confirm('Удалить аватар?')"
                    >
                        {{ __('Удалить аватар') }}
                    </button>

                    @if (session('status') === 'avatar-removed')
                        <p
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            x-init="setTimeout(() => show = false, 2000)"
                            class="inline-block ml-2 text-sm text-gray-600"
                        >{{ __('Аватар удалён.') }}</p>
                    @endif
                </form>
            @endif
        </div>
    </div>
</section>

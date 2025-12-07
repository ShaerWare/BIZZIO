@extends('layouts.app')

@section('title', 'Редактировать компанию')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Хлебные крошки -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('companies.index') }}" class="text-sm text-gray-700 hover:text-gray-900">
                        Компании
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <a href="{{ route('companies.show', $company) }}" class="ml-1 text-sm text-gray-700 hover:text-gray-900">
                            {{ $company->name }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500">Редактирование</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Редактирование компании</h1>

                <form method="POST" action="{{ route('companies.update', $company) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Название -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Название компании <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $company->name) }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ИНН -->
                    <div>
                        <label for="inn" class="block text-sm font-medium text-gray-700">
                            ИНН <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="inn" 
                               id="inn" 
                               value="{{ old('inn', $company->inn) }}"
                               maxlength="12"
                               pattern="[0-9]{12}"
                               required
                               placeholder="123456789012"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('inn') border-red-500 @enderror">
                        <p class="mt-1 text-xs text-gray-500">ИНН должен содержать 12 цифр</p>
                        @error('inn')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Организационно-правовая форма -->
                    <div>
                        <label for="legal_form" class="block text-sm font-medium text-gray-700">
                            Организационно-правовая форма
                        </label>
                        <select name="legal_form" 
                                id="legal_form"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Не выбрано</option>
                            <option value="ООО" {{ old('legal_form', $company->legal_form) == 'ООО' ? 'selected' : '' }}>ООО</option>
                            <option value="АО" {{ old('legal_form', $company->legal_form) == 'АО' ? 'selected' : '' }}>АО</option>
                            <option value="ПАО" {{ old('legal_form', $company->legal_form) == 'ПАО' ? 'selected' : '' }}>ПАО</option>
                            <option value="ИП" {{ old('legal_form', $company->legal_form) == 'ИП' ? 'selected' : '' }}>ИП</option>
                            <option value="ЗАО" {{ old('legal_form', $company->legal_form) == 'ЗАО' ? 'selected' : '' }}>ЗАО</option>
                        </select>
                        @error('legal_form')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Отрасль -->
                    <div>
                        <label for="industry_id" class="block text-sm font-medium text-gray-700">
                            Отрасль
                        </label>
                        <select name="industry_id" 
                                id="industry_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Не выбрано</option>
                            @foreach($industries as $industry)
                                <option value="{{ $industry->id }}" {{ old('industry_id', $company->industry_id) == $industry->id ? 'selected' : '' }}>
                                    {{ $industry->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('industry_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Краткое описание -->
                    <div>
                        <label for="short_description" class="block text-sm font-medium text-gray-700">
                            Краткое описание
                        </label>
                        <textarea name="short_description" 
                                  id="short_description" 
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Кратко опишите деятельность компании (до 500 символов)"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('short_description') border-red-500 @enderror">{{ old('short_description', $company->short_description) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Максимум 500 символов</p>
                        @error('short_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Полное описание -->
                    <div>
                        <label for="full_description" class="block text-sm font-medium text-gray-700">
                            Полное описание
                        </label>
                        <textarea name="full_description" 
                                  id="full_description" 
                                  rows="6"
                                  placeholder="Подробное описание компании, её деятельности, достижений и преимуществ"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('full_description') border-red-500 @enderror">{{ old('full_description', $company->full_description) }}</textarea>
                        @error('full_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Текущий логотип -->
                    @if($company->logo)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Текущий логотип
                            </label>
                            <img src="{{ Storage::url($company->logo) }}" 
                                 alt="{{ $company->name }}"
                                 class="w-24 h-24 rounded-lg object-cover">
                        </div>
                    @endif

                    <!-- Новый логотип -->
                    <div>
                        <label for="logo" class="block text-sm font-medium text-gray-700">
                            {{ $company->logo ? 'Заменить логотип' : 'Логотип' }}
                        </label>
                        <input type="file" 
                               name="logo" 
                               id="logo" 
                               accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-gray-500">Форматы: JPG, PNG. Максимальный размер: 2MB</p>
                        @error('logo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Текущие документы -->
                    @if($company->getMedia('documents')->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Загруженные документы
                            </label>
                            <div class="space-y-2">
                                @foreach($company->getMedia('documents') as $document)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $document->name }}</p>
                                                <p class="text-xs text-gray-500">{{ number_format($document->size / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <a href="{{ $document->getUrl() }}" 
                                           target="_blank"
                                           class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            Скачать
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Новые документы -->
                    <div>
                        <label for="documents" class="block text-sm font-medium text-gray-700">
                            Добавить документы (PDF)
                        </label>
                        <input type="file" 
                               name="documents[]" 
                               id="documents" 
                               accept=".pdf"
                               multiple
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-gray-500">Максимум 10 файлов по 10MB</p>
                        @error('documents.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center justify-end gap-4 pt-4 border-t">
                        <a href="{{ route('companies.show', $company) }}" 
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Отмена
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
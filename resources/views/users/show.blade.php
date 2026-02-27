@extends('layouts.app')

@section('title', $user->name)

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Профиль -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-start space-x-6">
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                         class="w-24 h-24 rounded-full object-cover shadow-md">

                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                                @if($user->position)
                                    <p class="text-gray-600 mt-1">{{ $user->position }}</p>
                                @endif
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-sm text-gray-500">
                                    {{ $subscribersCount }} {{ trans_choice('подписчик|подписчика|подписчиков', $subscribersCount) }}
                                </span>
                                @include('components.subscribe-button', ['target' => $user])
                            </div>
                        </div>

                        @if($user->bio)
                            <p class="text-gray-700 mt-3">{{ $user->bio }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Компании пользователя -->
        @if($companies->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Компании</h2>
                    <div class="space-y-3">
                        @foreach($companies as $company)
                            <a href="{{ route('companies.show', $company) }}"
                               class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                @if($company->logo)
                                    <img src="{{ Storage::url($company->logo) }}" alt="{{ $company->name }}"
                                         class="w-10 h-10 rounded-lg object-cover">
                                @else
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-sm font-bold text-white">{{ mb_substr($company->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $company->name }}</span>
                                        @if($company->is_verified)
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>
                                    @if($company->pivot && $company->pivot->role)
                                        <span class="text-xs text-gray-500">{{ $company->pivot->role }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Последние посты -->
        @if($recentPosts->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Посты</h2>
                    <div class="space-y-4">
                        @foreach($recentPosts as $post)
                            <div class="p-3 rounded-lg bg-gray-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs text-gray-500">{{ $post->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $post->body }}</p>
                                @if($post->getFirstMediaUrl('photos'))
                                    <img src="{{ $post->getFirstMediaUrl('photos') }}" alt=""
                                         class="mt-2 rounded-lg max-h-64 object-cover">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

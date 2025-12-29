<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Аукционы') }}
            </h2>
            @auth
                @if(auth()->user()->isModeratorOfAnyCompany())
                    <a href="{{ route('auctions.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Разместить аукцион
                    </a>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Фильтры -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('auctions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Поиск -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Название или номер"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Статус -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                            <select name="status" 
                                    id="status" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Все статусы</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Приём заявок</option>
                                <option value="trading" {{ request('status') === 'trading' ? 'selected' : '' }}>Торги</option>
                                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Завершён</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Черновик</option>
                            </select>
                        </div>

                        <!-- Тип процедуры -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Тип процедуры</label>
                            <select name="type" 
                                    id="type" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Все типы</option>
                                <option value="open" {{ request('type') === 'open' ? 'selected' : '' }}>Открытая</option>
                                <option value="closed" {{ request('type') === 'closed' ? 'selected' : '' }}>Закрытая</option>
                            </select>
                        </div>

                        <!-- Кнопка поиска -->
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Применить фильтры
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Список аукционов -->
            @if($auctions->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        Аукционов не найдено.
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($auctions as $auction)
                        <x-auction-card :auction="$auction" />
                    @endforeach
                </div>

                <!-- Пагинация -->
                <div class="mt-6">
                    {{ $auctions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                {{-- Левая колонка --}}
                <div class="lg:col-span-1 space-y-4">
                    @include('partials.dashboard.profile-card')
                    @include('partials.dashboard.join-requests-widget')
                    @include('partials.dashboard.my-companies-widget')
                    @include('partials.dashboard.my-projects-widget')
                </div>

                {{-- Центральная колонка --}}
                <div class="lg:col-span-3 space-y-6">
                    @include('partials.dashboard.news-widget')
                    @include('partials.dashboard.post-form')
                    @include('partials.dashboard.posts-feed')
                    @include('partials.dashboard.activity-feed')
                </div>

                {{-- Правая колонка --}}
                <div class="lg:col-span-1 space-y-4">
                    @include('partials.dashboard.tenders-widget')
                    @include('partials.dashboard.invitations-widget')
                    @include('partials.dashboard.bids-widget')
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const offset = parseInt(this.dataset.offset);
                    const spinner = document.getElementById('load-more-spinner');
                    const text = document.getElementById('load-more-text');

                    spinner.classList.remove('hidden');
                    text.textContent = 'Загрузка...';

                    fetch(`{{ route('dashboard.activities') }}?offset=${offset}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        if (html.trim()) {
                            document.getElementById('activity-feed').insertAdjacentHTML('beforeend', html);
                            loadMoreBtn.dataset.offset = offset + 10;
                        } else {
                            loadMoreBtn.remove();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        spinner.classList.add('hidden');
                        text.textContent = 'Загрузить ещё';
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>

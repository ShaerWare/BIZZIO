@if($userProjects->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Мои проекты') }}</h4>
        <ul class="space-y-2">
            @foreach($userProjects as $project)
                <li>
                    <a href="{{ route('projects.show', ['project' => $project->slug]) }}"
                       class="text-sm text-gray-700 hover:text-emerald-600 truncate block">
                        {{ $project->name }}
                    </a>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('projects.index') }}"
           class="mt-2 inline-block text-xs text-emerald-600 hover:text-emerald-800">
            {{ __('Все проекты') }} &rarr;
        </a>
    </div>
</div>
@endif

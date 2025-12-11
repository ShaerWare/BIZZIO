<div class="comment bg-white border border-gray-200 rounded-lg p-4" id="comment-{{ $comment->id }}">
    <div class="flex items-start">
        <div class="flex-shrink-0 mr-3">
            @if($comment->user->avatar)
                <img src="{{ asset('storage/' . $comment->user->avatar) }}" 
                     alt="{{ $comment->user->name }}" 
                     class="w-10 h-10 rounded-full object-cover">
            @else
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-sm text-gray-500 font-semibold">
                        {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                    </span>
                </div>
            @endif
        </div>

        <div class="flex-1">
            <div class="flex items-center justify-between mb-1">
                <h4 class="text-sm font-semibold text-gray-900">{{ $comment->user->name }}</h4>
                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
            </div>

            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $comment->body }}</div>

            @auth
                @if($comment->canManage(auth()->user()))
                    <div class="mt-2 flex space-x-3">
                        <button onclick="editComment({{ $comment->id }})" 
                                class="text-xs text-indigo-600 hover:text-indigo-500">
                            Редактировать
                        </button>
                        <form method="POST" 
                              action="{{ route('comments.destroy', $comment) }}" 
                              onsubmit="return confirm('Удалить комментарий?');"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-500">
                                Удалить
                            </button>
                        </form>
                    </div>
                @endif
            @endauth

            <!-- Вложенные комментарии (ответы) -->
            @if($comment->replies->isNotEmpty())
                <div class="mt-4 ml-6 space-y-3 border-l-2 border-gray-200 pl-4">
                    @foreach($comment->replies as $reply)
                        @include('projects.partials.comment', ['comment' => $reply])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
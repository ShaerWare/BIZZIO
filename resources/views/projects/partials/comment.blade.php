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

            <!-- Текст комментария (по умолчанию видим) -->
            <div class="comment-body-{{ $comment->id }} text-sm text-gray-700 whitespace-pre-wrap">{{ $comment->body }}</div>

            <!-- Форма редактирования (скрыта по умолчанию) -->
            <form method="POST" 
                  action="{{ route('comments.update', $comment) }}" 
                  class="comment-edit-form-{{ $comment->id }} hidden"
                  id="edit-form-{{ $comment->id }}">
                @csrf
                @method('PUT')
                <textarea name="body" 
                          rows="3"
                          required
                          class="w-full mt-2 rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">{{ $comment->body }}</textarea>
                <div class="mt-2 flex space-x-2">
                    <button type="submit" 
                            class="inline-flex items-center px-3 py-1.5 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                        Сохранить
                    </button>
                    <button type="button" 
                            onclick="cancelEdit({{ $comment->id }})"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 transition">
                        Отмена
                    </button>
                </div>
            </form>

            @auth
                @if($comment->canManage(auth()->user()))
                    <!-- Кнопки управления (скрыты при редактировании) -->
                    <div class="comment-actions-{{ $comment->id }} mt-2 flex space-x-3">
                        <button onclick="editComment({{ $comment->id }})" 
                                class="text-xs text-emerald-600 hover:text-emerald-500 font-medium">
                            Редактировать
                        </button>
                        <form method="POST" 
                              action="{{ route('comments.destroy', $comment) }}" 
                              onsubmit="return confirm('Удалить комментарий?');"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-500 font-medium">
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
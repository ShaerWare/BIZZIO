<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        if ($request->hasFile('photo')) {
            $post->addMediaFromRequest('photo')->toMediaCollection('photos');
        }

        return redirect()->route('dashboard')->with('success', 'Пост опубликован');
    }

    public function destroy(Post $post)
    {
        abort_unless($post->user_id === auth()->id(), 403);

        $post->delete();

        return redirect()->route('dashboard')->with('success', 'Пост удалён');
    }
}

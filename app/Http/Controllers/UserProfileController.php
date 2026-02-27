<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;

class UserProfileController extends Controller
{
    public function show(User $user)
    {
        $companies = $user->moderatedCompanies()->get();

        $recentPosts = Post::with(['user', 'media'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $subscribersCount = $user->subscribers()->count();

        return view('users.show', compact('user', 'companies', 'recentPosts', 'subscribersCount'));
    }
}

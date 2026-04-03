<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestSent;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    /**
     * Страница «Друзья» с вкладками
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->get('tab', 'friends');
        $search = $request->get('search', '');

        $friendsQuery = $user->friends();

        if ($search) {
            $op = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $friendsQuery->where(function ($q) use ($op, $search) {
                $q->where('name', $op, "%{$search}%")
                    ->orWhere('email', $op, "%{$search}%")
                    ->orWhere('position', $op, "%{$search}%");
            });
        }

        $friends = $friendsQuery->paginate(20, ['*'], 'friends_page');

        $incoming = $user->pendingFriendRequests()
            ->with('sender')
            ->latest()
            ->get();

        $outgoing = $user->sentFriendRequests()
            ->where('status', 'pending')
            ->with('receiver')
            ->latest()
            ->get();

        $friendsOfFriends = $user->friendsOfFriends(12);

        return view('friends.index', compact('friends', 'incoming', 'outgoing', 'friendsOfFriends', 'tab', 'search'));
    }

    /**
     * Отправить заявку в друзья
     */
    public function sendRequest(User $user)
    {
        $sender = auth()->user();

        if ($sender->id === $user->id) {
            return back()->with('error', 'Нельзя добавить себя в друзья.');
        }

        // Проверяем, есть ли уже связь
        $existing = Friendship::where(function ($q) use ($sender, $user) {
            $q->where('sender_id', $sender->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($sender, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $sender->id);
        })->first();

        if ($existing) {
            if ($existing->isAccepted()) {
                return back()->with('info', 'Вы уже друзья.');
            }

            // Если другой пользователь уже отправил нам заявку — автоматически принимаем
            if ($existing->sender_id === $user->id && $existing->isPending()) {
                $existing->update(['status' => 'accepted']);
                FriendRequestAccepted::dispatch($existing);

                return back()->with('success', 'Вы теперь друзья!');
            }

            return back()->with('info', 'Заявка уже отправлена.');
        }

        $friendship = Friendship::create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'status' => 'pending',
        ]);

        FriendRequestSent::dispatch($friendship);

        return back()->with('success', 'Заявка в друзья отправлена!');
    }

    /**
     * Принять заявку в друзья
     */
    public function accept(User $user)
    {
        $receiver = auth()->user();

        $friendship = Friendship::where('sender_id', $user->id)
            ->where('receiver_id', $receiver->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->update(['status' => 'accepted']);
        FriendRequestAccepted::dispatch($friendship);

        return back()->with('success', 'Заявка принята! Вы теперь друзья.');
    }

    /**
     * Удалить из друзей / отменить заявку
     */
    public function remove(User $user)
    {
        $current = auth()->user();

        $friendship = Friendship::where(function ($q) use ($current, $user) {
            $q->where('sender_id', $current->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($current, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $current->id);
        })->first();

        if ($friendship) {
            $friendship->delete();
        }

        return back()->with('success', 'Пользователь удалён из друзей.');
    }
}

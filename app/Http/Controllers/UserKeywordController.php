<?php

namespace App\Http\Controllers;

use App\Models\UserKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserKeywordController extends Controller
{
    const MAX_KEYWORDS = 20;

    /**
     * Страница управления ключевыми словами
     */
    public function index()
    {
        $keywords = Auth::user()->keywords()->orderBy('keyword')->get();
        
        return view('profile.keywords', compact('keywords'));
    }

    /**
     * Добавление ключевого слова
     */
    public function store(Request $request)
    {
        // Проверка лимита
        if (Auth::user()->keywords()->count() >= self::MAX_KEYWORDS) {
            return back()->withErrors([
                'keyword' => "Максимальное количество ключевых слов — " . self::MAX_KEYWORDS
            ]);
        }

        // Валидация
        $request->validate([
            'keyword' => [
                'required',
                'string',
                'max:50',
                'unique:user_keywords,keyword,NULL,id,user_id,' . Auth::id(),
            ],
        ], [
            'keyword.required' => 'Введите ключевое слово',
            'keyword.max' => 'Ключевое слово не должно превышать 50 символов',
            'keyword.unique' => 'Вы уже добавили это ключевое слово',
        ]);

        // Создание
        Auth::user()->keywords()->create([
            'keyword' => trim($request->keyword),
        ]);

        return back()->with('success', 'Ключевое слово добавлено');
    }

    /**
     * Удаление ключевого слова
     */
    public function destroy(UserKeyword $keyword)
    {
        // Проверка, что ключевое слово принадлежит текущему пользователю
        if ($keyword->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $keyword->delete();

        return back()->with('success', 'Ключевое слово удалено');
    }
}
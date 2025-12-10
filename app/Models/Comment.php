<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_comments';

    protected $fillable = [
        'project_id',
        'user_id',
        'parent_id',
        'body',
    ];

    // Связи

    /**
     * Проект, к которому относится комментарий
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Автор комментария
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Родительский комментарий (для древовидной структуры)
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Дочерние комментарии (ответы)
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Методы

    /**
     * Проверка, может ли пользователь редактировать/удалять комментарий
     */
    public function canManage(User $user): bool
    {
        return $this->user_id === $user->id || $user->hasRole('Admin');
    }
}
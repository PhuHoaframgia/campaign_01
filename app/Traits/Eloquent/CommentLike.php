<?php

namespace App\Traits\Eloquent;

trait CommentLike
{
    public function scopeGetComments($query)
    {
        return $query->with(['comments' => function ($query) {
            $query->with(['subComment' => function ($subQuery) {
                $subQuery->getLikes()
                    ->paginate(config('settings.paginate_comment'), ['*'], 1);
            }])
            ->getLikes()
            ->where('parent_id', config('settings.comment_parent'))
            ->paginate(config('settings.paginate_comment'), ['*'], 1);
        }]);
    }

    public function scopeGetLikes($query)
    {
        return $query->with(['likes' => function ($query) {
            $query->with('user')
                ->paginate(config('settings.paginate_default'), ['*'], 1);
        }]);
    }
}

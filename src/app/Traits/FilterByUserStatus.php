<?php

namespace App\Traits;

use Image;

trait FilterByUserStatus
{
    /**
     * Filter data by user status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tableName
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function scopeFilterByUserStatus($query, $tableName, $idName = 'user_id')
    {
        return $query->join('users', "$tableName.$idName", '=', 'users.id')
            ->where('users.is_active', true)
            ->where(function ($query) use ($tableName) {
                $query->whereRaw("users.user_status_changed_at <= $tableName.created_at")
                    ->orWhereNull('users.user_status_changed_at');
        })->select("$tableName.*");
    }

    public function scopeFilterByUserStatusWithoutSelect($query, $tableName, $idName = 'user_id')
    {
        return $query->join('users', "$tableName.$idName", '=', 'users.id')
            ->where('users.is_active', true)
            ->where(function ($query) use ($tableName) {
                $query->whereRaw("users.user_status_changed_at <= $tableName.created_at")
                    ->orWhereNull('users.user_status_changed_at');
        });
    }
}
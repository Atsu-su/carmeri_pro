<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function isOnSale()
    {
        return $this->on_sale;
    }

    public function isOwnItem()
    {
        return auth()->check() ? $this->seller_id === auth()->user()->id : false;
    }

    public function categoryItems()
    {
        return $this->hasMany(CategoryItem::class);
    }

    public function getOnSaleTextAttribute() {
        return $this->on_sale ? '出品中' : '販売済み';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }
}

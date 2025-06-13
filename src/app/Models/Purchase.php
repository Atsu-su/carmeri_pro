<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const PROCESSING = 'processing';

    public function isPurchased()
    {
        return $this->status === 'purchased';
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'purchased':
                return '購入済み';
            case 'processing':
                return '発送待ち';
            case 'shipped':
                return '発送済み';
            case 'completed':
                return '取引完了';
            default:
                return '不明なステータス';
        }
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const PROCESSING = ['processing' => '支払中'];
    const PAID = ['paid' => '支払済'];
    const SHIPPED = ['shipped' => '発送済み'];
    const COMPLETED = ['completed' => '取引完了'];
    const EXPIRED = ['expired' => '期限切れ'];

    public function isPurchased()
    {
        return $this->status === 'purchased';
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case key(self::PROCESSING):
                return self::PROCESSING[key(self::PROCESSING)];
            case key(self::PAID):
                return self::PAID[key(self::PAID)];
            case key(self::SHIPPED):
                return self::SHIPPED[key(self::SHIPPED)];
            case key(self::COMPLETED):
                return self::COMPLETED[key(self::COMPLETED)];
            default:
                return '不明なステータス';
        }
    }

    public function nextStatus()
    {
        switch ($this->status) {
            case key(self::PROCESSING):
                return self::PAID[key(self::PAID)];
            case key(self::PAID):
                return self::SHIPPED[key(self::SHIPPED)];
            case key(self::SHIPPED):
                return self::COMPLETED[key(self::COMPLETED)];
            default:
                return null; // 取引完了後は次のステータスはない
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

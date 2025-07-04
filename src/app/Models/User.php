<?php

namespace App\Models;

use App\Notifications\CustomCompleteEmail;
use App\Notifications\CustomVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\CustomStatusChangedToCompletedEmail;
use App\Notifications\CustomStatusChangedToShippedEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // メールアドレス確認のためのメッセージ送信
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail($this));
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token, $this));
    }

    public function sendEmailCompleteNotification()
    {
        $this->notify(new CustomCompleteEmail($this));
    }

    public function sendEmailStatusChangedToShippedNotification($purchase)
    {
        $this->notify(new CustomStatusChangedToShippedEmail($purchase, $this));
    }

    public function sendEmailStatusChangedToCompletedNotification($purchase)
    {
        $this->notify(new CustomStatusChangedToCompletedEmail($purchase, $this));
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'seller_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'buyer_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, sender_id);
    }
}

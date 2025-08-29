<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomCheckoutCompletedEmailToBuyer extends VerifyEmail
{

    // 次はここから

    private $purchase;
    private $user;

    public function __construct($purchase, $user)
    {
        $this->purchase = $purchase;
        $this->user = $user;
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('CoachTech メールアドレス確認')
            ->greeting($this->user->name . ' さん')
            ->line('以下のボタンをクリックしてメールアドレスを確認してください。')
            ->line('ボタンを押すと自動的にログインします。')
            ->action('メールアドレスを確認', $url)
            ->line('もし心当たりがない場合は、このメールを破棄してください。')
            ->salutation('ご確認よろしくお願いします。');

    }
}
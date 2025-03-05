<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Lang;

class CustomResetPasswordNotification extends ResetPassword
{
    public $user;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token, $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    /**
     * Get the reset password notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('パスワード変更')
            ->greeting('こんにちは！ '.$this->user->name.' さん')
            ->line('パスワード変更を受け付けました。以下のボタンをクリックして変更してください。')
            ->action('パスワード変更', $url)
            ->line('このリンクは:count分後に無効となります。', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])
            ->line('このメールに心当たりのない場合は、お手数ですが破棄してください。')
            ->salutation('ご確認よろしくお願いします。');
    }
}

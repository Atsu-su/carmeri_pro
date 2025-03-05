<?php

namespace App\Notifications;

use GuzzleHttp\Psr7\Request;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    protected function buildMailMessage($url)
    {
        $greeting = request()->input('activate') ?
            'おかえりなさい！ '.$this->user->name.' さん' :
            $this->user->name . ' さん';

        return (new MailMessage)
            ->subject('メールアドレス確認')
            ->greeting($greeting)
            ->line('以下のボタンをクリックしてメールアドレスを確認してください。')
            ->line('ボタンを押すと自動的にログインします。')
            ->action('メールアドレスを確認', $url)
            ->line('もし心当たりがない場合は、このメールを破棄してください。')
            ->salutation('ご確認よろしくお願いします。');
    }

    protected function verificationUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'activate' => request()->input('activate') ? true : false,
            ]
        );
    }
}
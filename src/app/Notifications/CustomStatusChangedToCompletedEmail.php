<?php
namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomStatusChangedToCompletedEmail extends Notification
{
    use Queueable;

    private $purchase;
    private $seller;

    public function __construct($purchase, $seller)
    {
        $this->purchase = $purchase;
        $this->seller = $seller;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Carmeri 取引完了のお知らせ')
            ->greeting($this->seller->name . ' さん')
            ->line('取引中の商品が購入者に到着し、取引完了となりました。')
            ->line('商品ID：'.$this->purchase->item_id)
            ->line('商品名：'.$this->purchase->item->name)
            ->line('価格：'.$this->purchase->item->price.'円')
            ->line('取引完了日：'.Carbon::parse($this->purchase->updated_at)->format('Y年m月d日 H:i'))
            ->salutation('ご確認よろしくお願いします。');
    }
}
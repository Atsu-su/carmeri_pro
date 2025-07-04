<?php
namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomStatusChangedToShippedEmail extends Notification
{
    use Queueable;

    private $buyer;
    private $purchase;

    public function __construct($purchase, $buyer)
    {
        $this->purchase = $purchase;
        $this->buyer = $buyer;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Carmeri 商品発送完了のお知らせ')
            ->greeting($this->buyer->name . ' さん')
            ->line('ご注文の商品が発送されました。')
            ->line('商品ID：'.$this->purchase->item_id)
            ->line('商品名：'.$this->purchase->item->name)
            ->line('価格：'.$this->purchase->item->price.'円')
            ->line('発送日：'.Carbon::parse($this->purchase->shipped_at)->format('Y年m月d日 H:i'))
            ->salutation('ご確認よろしくお願いします。');
    }
}
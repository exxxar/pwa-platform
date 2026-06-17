<?php

namespace App\Notifications;

// app/Notifications/NewOrderNotification.php
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public string $orderId) {}

    // Обязательно указываем канал webpush
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Новый заказ #' . $this->orderId)
            ->icon('/icon-192.png')
            ->body('Поступил новый заказ. Нажмите, чтобы просмотреть.')
            ->action('Открыть', 'open_url')
            ->data(['url' => "/orders/{$this->orderId}"])
            ->options(['TTL' => 1000]);
    }
}

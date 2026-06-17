<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public $message, public $dialog) {}

    public function via($notifiable)
    {
        return ['webpush'];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Новое сообщение')
            ->icon('/icon.png')
            ->body($this->message->message)
            ->data([
                'dialog_id' => $this->dialog->id
            ]);
    }
}

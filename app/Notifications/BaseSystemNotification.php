<?php

namespace App\Notifications;

use App\Jobs\SendPushJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseSystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        // In-app (database) + mail. Push se dispara manual con ->sendNow()
        return ['database', 'mail'];
    }

    protected function buildMail(string $subject, string $markdown, array $data): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->markdown($markdown, $data);
    }

    /** Dispatch push now (FCM) */
    public function toPush(object $notifiable, string $title, string $body, array $data = []): void
    {
        dispatch(new SendPushJob(
            userId: $notifiable->id,
            title:  $title,
            body:   $body,
            data:   $data
        ));
    }

    /** Optional placeholder (no se usa aún) */
    public function toSms(object $notifiable, string $message): void
    {
        // Intentionally unused now.
    }
}

<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Loan $loan) {}

    public function via($notifiable): array
    {
        return ['mail']; // si usas push/SMS, agrega jobs en NotificationService
    }

    public function toMail($notifiable): MailMessage
    {
        $reason = $this->loan->rejection_reason ?: 'No cumple con la política vigente.';

        return (new MailMessage)
            ->subject('Resultado de solicitud de crédito')
            ->greeting('Hola')
            ->line('Tu solicitud de crédito fue rechazada.')
            ->line("Motivo: {$reason}")
            ->line('Puedes intentarlo nuevamente más adelante.');
    }
}

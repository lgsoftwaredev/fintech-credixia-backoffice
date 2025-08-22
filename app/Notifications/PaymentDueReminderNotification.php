<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentDueReminderNotification extends BaseSystemNotification
{
    private const TYPE = 'payment_due_reminder';
    private const TITLE = 'Tienes un pago próximo';
    private const SUBTITLE = 'Haz tu transferencia SPEI a tiempo';

    public function __construct(public Payment $payment)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->buildMail(
            subject: 'Recordatorio: pago próximo',
            markdown: 'emails.payments.due_reminder',
            data: ['payment' => $this->payment]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => self::TYPE,
            'title'      => self::TITLE,
            'subtitle'   => self::SUBTITLE,
            'payment_id' => $this->payment->id,
            'due_date'   => optional($this->payment->due_date)->format('Y-m-d'),
            'screen'     => 'payment_detail',
        ];
    }

    public function sendNow(object $notifiable): void
    {
        $this->toPush($notifiable, self::TITLE, self::SUBTITLE, [
            'screen'     => 'payment_detail',
            'payment_id' => $this->payment->id,
            'type'       => self::TYPE,
        ]);
    }
}

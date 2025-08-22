<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Messages\MailMessage;

class LoanApprovedNotification extends BaseSystemNotification
{
    private const TYPE = 'loan_approved';
    private const TITLE = 'Tu préstamo fue aprobado';
    private const SUBTITLE = 'Revisa las condiciones y el calendario de pagos';

    public function __construct(public Loan $loan)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->buildMail(
            subject: 'Tu préstamo fue aprobado',
            markdown: 'emails.loans.approved',
            data: ['loan' => $this->loan]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => self::TYPE,
            'title' => self::TITLE,
            'subtitle' => self::SUBTITLE,
            'loan_id' => $this->loan->id,
            'screen'  => 'loan_detail',
        ];
    }

    public function sendNow(object $notifiable): void
    {
        $this->toPush($notifiable, self::TITLE, self::SUBTITLE, [
            'screen'  => 'loan_detail',
            'loan_id' => $this->loan->id,
            'type'    => self::TYPE,
        ]);
    }
}

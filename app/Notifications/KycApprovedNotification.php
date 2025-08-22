<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class KycApprovedNotification extends BaseSystemNotification
{
    private const TYPE = 'kyc_approved';
    private const TITLE = 'KYC aprobado';
    private const SUBTITLE = 'Tu verificación de identidad fue aprobada';

    public function __construct(public User $user)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->buildMail(
            subject: 'Tu verificación KYC fue aprobada',
            markdown: 'emails.kyc.approved',
            data: ['user' => $this->user]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'   => self::TYPE,
            'title'  => self::TITLE,
            'subtitle' => self::SUBTITLE,
            'screen' => 'kyc_status',
        ];
    }

    public function sendNow(object $notifiable): void
    {
        $this->toPush($notifiable, self::TITLE, self::SUBTITLE, [
            'screen' => 'kyc_status',
            'type'   => self::TYPE,
        ]);
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class OtpNotification extends BaseSystemNotification
{
    private const TYPE = 'otp_code';
    private const TITLE = 'Código de verificación';
    private const SUBTITLE = 'Usa este código para continuar con tu registro';

    public function __construct(
        public User $user,
        public string $code,
        public string $purpose = 'register' // register | login | password_reset, etc.
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->buildMail(
            subject: 'Tu código de verificación',
            markdown: 'emails.auth.otp',
            data: [
                'user'    => $this->user,
                'code'    => $this->code,
                'purpose' => $this->purpose,
                'ttl'     => config('authflow.otp_ttl_minutes'),
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => self::TYPE,
            'title'    => self::TITLE,
            'subtitle' => self::SUBTITLE,
            'purpose'  => $this->purpose,
        ];
    }

    public function sendNow(object $notifiable): void
    {
        $this->toPush($notifiable, self::TITLE, self::SUBTITLE, [
            'screen'  => 'otp_verification',
            'type'    => self::TYPE,
            'purpose' => $this->purpose,
        ]);
    }
}

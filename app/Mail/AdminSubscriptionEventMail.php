<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // uncomment if you will queue
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminSubscriptionEventMail extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $message,
        public array $meta = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Billing] ' . $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.subscription_event',
            with: [
                'title'   => $this->title,
                'message' => $this->message,
                'meta'    => $this->meta,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

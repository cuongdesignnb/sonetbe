<?php

namespace App\Mail;

use App\Models\Webinar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WebinarRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Webinar $webinar,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận đăng ký Webinar: ' . $this->webinar->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.webinar-registration',
        );
    }
}

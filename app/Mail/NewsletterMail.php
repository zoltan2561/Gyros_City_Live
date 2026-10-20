<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class NewsletterMail extends Mailable
{
    public $letterSubject;
    public $letterBody;
    public $unsubscribeUrl;

    public function __construct(string $subject, string $body, ?string $unsubscribeUrl = null)
    {
        $this->letterSubject = $subject;
        $this->letterBody = $body;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function build()
    {
        return $this->subject($this->letterSubject)->view('email.newsletter')->text('email.newsletter-text');
    }
}

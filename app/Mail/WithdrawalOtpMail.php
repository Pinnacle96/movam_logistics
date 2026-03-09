<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $amount;

    public function __construct($token, $amount)
    {
        $this->token = $token;
        $this->amount = $amount;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Withdrawal Authorization Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.withdrawal_otp',
        );
    }
}

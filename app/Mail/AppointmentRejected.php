<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'No pudimos validar tu comprobante en Nails Time');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.appointment-rejected',
            with: ['appointment' => $this->appointment, 'reason' => $this->reason],
        );
    }
}

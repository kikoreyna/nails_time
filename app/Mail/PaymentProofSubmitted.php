<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\PaymentProof;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentProofSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment, public PaymentProof $proof) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Comprobante por validar: {$this->appointment->customer_name} · {$this->appointment->appointment_date->format('d/m/Y')}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payment-proof-submitted',
            with: ['appointment' => $this->appointment, 'proof' => $this->proof],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk((string) config('booking.proof.disk'), $this->proof->image_path),
        ];
    }
}

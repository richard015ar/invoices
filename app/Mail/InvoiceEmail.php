<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $subjectLine,
        public string $messageBody,
        private string $pdfBinary
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.invoice'
        );
    }

    public function build(): static
    {
        return $this->attachData(
            $this->pdfBinary,
            $this->invoice->invoice_number . '.pdf',
            ['mime' => 'application/pdf']
        );
    }
}

<?php

namespace App\Services;

use App\Mail\InvoiceEmail;
use App\Models\Invoice;
use App\Models\IssuerProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceDeliveryService
{
    public function __construct(
        private readonly InvoiceViewDataFactory $viewDataFactory
    ) {}

    public function pdfBinary(Invoice $invoice, IssuerProfile $profile): string
    {
        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'template' => config('invoice_templates.'.$invoice->template),
            'displayIssuer' => $this->viewDataFactory->issuer($profile),
            'displayClient' => $this->viewDataFactory->client($invoice),
        ])->output();
    }

    public function send(Invoice $invoice, IssuerProfile $profile, string $recipientEmail, string $subject, string $body): void
    {
        $mail = new InvoiceEmail(
            invoice: $invoice,
            subjectLine: $subject,
            messageBody: $body,
            pdfBinary: $this->pdfBinary($invoice, $profile)
        );

        foreach ($invoice->attachments as $attachment) {
            if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
                continue;
            }

            $mail->attachData(
                Storage::disk($attachment->disk)->get($attachment->path),
                $attachment->original_name,
                ['mime' => $attachment->mime_type ?: 'application/octet-stream']
            );
        }

        $mailer = Mail::to($recipientEmail);
        $copyRecipient = config('mail.invoice_copy_to');

        if ($copyRecipient) {
            $mailer->bcc($copyRecipient);
        }

        $mailer->send($mail);
    }
}

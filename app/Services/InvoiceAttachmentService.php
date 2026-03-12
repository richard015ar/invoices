<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InvoiceAttachmentService
{
    public function storeForInvoice(Invoice $invoice, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (! $attachment instanceof UploadedFile) {
                continue;
            }

            $path = $attachment->store('invoice-attachments/'.$invoice->id, 'local');

            $invoice->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize() ?: 0,
            ]);
        }
    }

    public function removeFromInvoice(Invoice $invoice, array $attachmentIds): void
    {
        if ($attachmentIds === []) {
            return;
        }

        $attachments = $invoice->attachments()
            ->whereIn('id', $attachmentIds)
            ->get();

        foreach ($attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        }
    }
}

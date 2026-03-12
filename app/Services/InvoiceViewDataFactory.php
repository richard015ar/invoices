<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\IssuerProfile;

class InvoiceViewDataFactory
{
    public function issuer(IssuerProfile $profile): array
    {
        return [
            'name' => $profile->name,
            'email' => $profile->email,
            'address' => $profile->address,
            'nie' => $profile->nie,
            'additional_info' => $profile->additional_info,
        ];
    }

    public function client(Invoice $invoice): array
    {
        if ($invoice->client) {
            return [
                'name' => $invoice->client->name,
                'email' => $invoice->client->email,
                'address' => $invoice->client->address,
                'details' => $invoice->client->details,
            ];
        }

        return [
            'name' => $invoice->client_name,
            'email' => $invoice->client_email,
            'address' => $invoice->client_address,
            'details' => $invoice->client_details,
        ];
    }
}

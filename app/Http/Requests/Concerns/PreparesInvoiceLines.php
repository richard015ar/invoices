<?php

namespace App\Http\Requests\Concerns;

trait PreparesInvoiceLines
{
    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->map(function ($line): array {
                $line = is_array($line) ? $line : [];

                return [
                    'catalog_item_id' => $line['catalog_item_id'] ?? null,
                    'description' => trim((string) ($line['description'] ?? '')),
                    'quantity' => $line['quantity'] ?? null,
                    'unit_price' => $line['unit_price'] ?? null,
                    'tax_rate' => $line['tax_rate'] ?? null,
                ];
            })
            ->filter(function (array $line): bool {
                return $line['catalog_item_id'] !== null
                    || $line['description'] !== ''
                    || $line['quantity'] !== null
                    || $line['unit_price'] !== null
                    || $line['tax_rate'] !== null;
            })
            ->values()
            ->all();

        $this->merge(['lines' => $lines]);
    }
}

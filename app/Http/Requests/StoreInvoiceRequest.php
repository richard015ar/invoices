<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')->where('user_id', auth()->id()),
            ],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', Rule::in(Invoice::STATUSES)],
            'currency' => ['required', 'string', 'size:3'],
            'template' => ['required', 'string', 'max:50'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('user_id', auth()->id())],
            'from_name' => ['required', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'from_address' => ['nullable', 'string'],
            'from_nie' => ['nullable', 'string', 'max:255'],
            'from_additional_info' => ['nullable', 'string'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_address' => ['nullable', 'string'],
            'client_details' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.catalog_item_id' => ['nullable', Rule::exists('catalog_items', 'id')->where('user_id', auth()->id())],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

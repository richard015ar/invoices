<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreparesInvoiceLines;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    use PreparesInvoiceLines;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $issueDate = $this->input('issue_date');

        return [
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')->where('user_id', auth()->id()),
            ],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', ...($issueDate ? ['after_or_equal:'.$issueDate] : [])],
            'status' => ['required', Rule::in(Invoice::STATUSES)],
            'currency' => ['required', 'string', 'size:3'],
            'template' => ['required', 'string', Rule::in(array_keys(config('invoice_templates', [])))],
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
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,txt,csv,doc,docx,xls,xlsx'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.catalog_item_id' => ['nullable', Rule::exists('catalog_items', 'id')->where('user_id', auth()->id())],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

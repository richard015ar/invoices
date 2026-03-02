{{ $messageBody }}

Invoice: {{ $invoice->invoice_number }}
Issue date: {{ $invoice->issue_date?->format('Y-m-d') }}
Total: {{ $invoice->currency }} {{ number_format((float) $invoice->grand_total, 2) }}

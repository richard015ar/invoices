<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\CatalogItem;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoices.index', [
            'invoices' => Invoice::query()->latest('issue_date')->latest()->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        $cloneSource = null;
        $clonedLines = null;

        if ($request->filled('clone_from')) {
            $cloneSource = Invoice::query()
                ->with('lines')
                ->find($request->integer('clone_from'));

            if ($cloneSource) {
                $clonedLines = $cloneSource->lines->map(fn ($line) => [
                    'catalog_item_id' => $line->catalog_item_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'tax_rate' => $line->tax_rate,
                ])->all();
            }
        }

        return view('invoices.create', [
            'invoice' => new Invoice([
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'status' => 'draft',
                'currency' => 'USD',
                'template' => 'aurora',
                'accent_color' => '#0f766e',
                'from_name' => $cloneSource?->from_name ?? 'Ricardo Aragon',
                'from_email' => $cloneSource?->from_email ?? '',
                'from_address' => $cloneSource?->from_address ?? '',
                'client_name' => $cloneSource?->client_name ?? '',
                'client_email' => $cloneSource?->client_email ?? '',
                'client_address' => $cloneSource?->client_address ?? '',
                'client_details' => $cloneSource?->client_details ?? '',
                'notes' => $cloneSource?->notes ?? '',
            ]),
            'clonedLines' => $clonedLines,
            'catalogItems' => CatalogItem::query()->where('is_active', true)->orderBy('name')->get(),
            'templates' => config('invoice_templates'),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($request): Invoice {
            $invoice = Invoice::query()->create([
                ...$this->invoiceAttributes($request->validated()),
                'invoice_number' => $request->validated('invoice_number') ?: $this->nextInvoiceNumber(),
            ]);

            $this->syncLines($invoice, $request->validated('lines', []));

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice creada con exito.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['lines.catalogItem']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'template' => config('invoice_templates.' . $invoice->template),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.edit', [
            'invoice' => $invoice->load('lines'),
            'catalogItems' => CatalogItem::query()->where('is_active', true)->orderBy('name')->get(),
            'templates' => config('invoice_templates'),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        DB::transaction(function () use ($request, $invoice): void {
            $invoice->update($this->invoiceAttributes($request->validated()));
            $this->syncLines($invoice, $request->validated('lines', []));
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice actualizada con exito.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice eliminada.');
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Invoice::STATUSES)],
        ]);

        $invoice->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Estado actualizado a ' . strtoupper($validated['status']) . '.');
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $copy = DB::transaction(function () use ($invoice): Invoice {
            $invoice->load('lines');

            $replica = Invoice::query()->create([
                ...$invoice->only([
                    'issue_date',
                    'due_date',
                    'currency',
                    'template',
                    'accent_color',
                    'from_name',
                    'from_email',
                    'from_address',
                    'client_name',
                    'client_email',
                    'client_address',
                    'client_details',
                    'notes',
                ]),
                'invoice_number' => $this->nextInvoiceNumber(),
                'status' => 'draft',
            ]);

            $this->syncLines($replica, $invoice->lines->map(fn ($line) => [
                'catalog_item_id' => $line->catalog_item_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_rate' => $line->tax_rate,
            ])->all());

            return $replica;
        });

        return redirect()->route('invoices.edit', $copy)->with('success', 'Invoice duplicada.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('lines');
        $template = config('invoice_templates.' . $invoice->template);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'template' => $template,
        ]);

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    private function invoiceAttributes(array $validated): array
    {
        return [
            'invoice_number' => $validated['invoice_number'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'],
            'currency' => strtoupper($validated['currency']),
            'template' => $validated['template'],
            'accent_color' => $validated['accent_color'],
            'from_name' => $validated['from_name'],
            'from_email' => $validated['from_email'] ?? null,
            'from_address' => $validated['from_address'] ?? null,
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'client_details' => $validated['client_details'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function syncLines(Invoice $invoice, array $lines): void
    {
        $invoice->lines()->delete();

        collect($lines)
            ->filter(fn (array $line) => trim((string) ($line['description'] ?? '')) !== '')
            ->values()
            ->each(function (array $line, int $position) use ($invoice): void {
                $quantity = (float) $line['quantity'];
                $unitPrice = (float) $line['unit_price'];
                $taxRate = (float) ($line['tax_rate'] ?? 0);

                $invoice->lines()->create([
                    'catalog_item_id' => ($line['catalog_item_id'] ?? null) ?: null,
                    'position' => $position,
                    'description' => $line['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'line_total' => round($quantity * $unitPrice, 2),
                ]);
            });

        $invoice->recalculateTotals();
    }

    private function nextInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $count = Invoice::query()->whereYear('issue_date', now()->year)->count() + 1;

        return sprintf('INV-%s-%04d', $year, $count);
    }
}

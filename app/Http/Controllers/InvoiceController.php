<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use App\Models\InvoiceLine;
use App\Models\IssuerProfile;
use App\Services\InvoiceAttachmentService;
use App\Services\InvoiceDeliveryService;
use App\Services\InvoiceViewDataFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceAttachmentService $attachmentService,
        private readonly InvoiceDeliveryService $deliveryService,
        private readonly InvoiceViewDataFactory $viewDataFactory,
    ) {}

    public function index(): View
    {
        return view('invoices.index', [
            'invoices' => Invoice::query()
                ->where('user_id', auth()->id())
                ->latest('issue_date')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        $issuerProfile = IssuerProfile::forUser(auth()->id());
        $cloneSource = null;
        $clonedLines = null;

        if ($request->filled('clone_from')) {
            $cloneSource = Invoice::query()
                ->where('user_id', auth()->id())
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
                'from_name' => $cloneSource?->from_name ?? $issuerProfile->name,
                'from_email' => $cloneSource?->from_email ?? $issuerProfile->email,
                'from_address' => $cloneSource?->from_address ?? $issuerProfile->address,
                'from_nie' => $cloneSource?->from_nie ?? $issuerProfile->nie,
                'from_additional_info' => $cloneSource?->from_additional_info ?? $issuerProfile->additional_info,
                'client_id' => $cloneSource?->client_id,
                'client_name' => $cloneSource?->client_name ?? '',
                'client_email' => $cloneSource?->client_email ?? '',
                'client_address' => $cloneSource?->client_address ?? '',
                'client_details' => $cloneSource?->client_details ?? '',
                'notes' => $cloneSource?->notes ?? '',
            ]),
            'clonedLines' => $clonedLines,
            'catalogItems' => $this->catalogItemsForForm(),
            'clients' => Client::query()
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'issuerProfile' => $issuerProfile,
            'templates' => config('invoice_templates'),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($request): Invoice {
            $invoice = Invoice::query()->create([
                ...$this->invoiceAttributes($request->validated()),
                'user_id' => auth()->id(),
                'invoice_number' => $request->validated('invoice_number') ?: $this->nextInvoiceNumber(),
            ]);

            $this->syncLines($invoice, $request->validated('lines', []));
            $this->attachmentService->storeForInvoice($invoice, $request->file('attachments', []));

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice creada con exito.');
    }

    public function show(Invoice $invoice): View
    {
        $this->ensureOwner($invoice);

        $invoice->load(['lines.catalogItem', 'client', 'attachments']);
        $issuerProfile = IssuerProfile::forUser(auth()->id());

        return view('invoices.show', [
            'invoice' => $invoice,
            'template' => config('invoice_templates.'.$invoice->template),
            'displayIssuer' => $this->viewDataFactory->issuer($issuerProfile),
            'displayClient' => $this->viewDataFactory->client($invoice),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $this->ensureOwner($invoice);

        return view('invoices.edit', [
            'invoice' => $invoice->load(['lines', 'attachments']),
            'catalogItems' => $this->catalogItemsForForm(),
            'clients' => Client::query()
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'issuerProfile' => IssuerProfile::forUser(auth()->id()),
            'templates' => config('invoice_templates'),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureOwner($invoice);

        DB::transaction(function () use ($request, $invoice): void {
            $invoice->update($this->invoiceAttributes($request->validated()));
            $this->syncLines($invoice, $request->validated('lines', []));
            $this->attachmentService->removeFromInvoice($invoice, $request->validated('remove_attachment_ids', []));
            $this->attachmentService->storeForInvoice($invoice, $request->file('attachments', []));
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice actualizada con exito.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->ensureOwner($invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice eliminada.');
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureOwner($invoice);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Invoice::STATUSES)],
        ]);

        $invoice->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Estado actualizado a '.strtoupper($validated['status']).'.');
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $this->ensureOwner($invoice);

        $copy = DB::transaction(function () use ($invoice): Invoice {
            $invoice->load('lines');

            $replica = Invoice::query()->create([
                ...$invoice->only([
                    'issue_date',
                    'due_date',
                    'currency',
                    'template',
                    'accent_color',
                    'client_id',
                    'from_name',
                    'from_email',
                    'from_address',
                    'from_nie',
                    'from_additional_info',
                    'client_name',
                    'client_email',
                    'client_address',
                    'client_details',
                    'notes',
                ]),
                'user_id' => auth()->id(),
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

    public function downloadAttachment(Invoice $invoice, InvoiceAttachment $attachment): StreamedResponse|Response
    {
        $this->ensureOwner($invoice);
        abort_unless($attachment->invoice_id === $invoice->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->ensureOwner($invoice);
        $invoice->load(['lines', 'client', 'attachments']);

        $issuerProfile = IssuerProfile::forUser(auth()->id());

        return response(
            $this->deliveryService->pdfBinary($invoice, $issuerProfile),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.pdf"',
            ]
        );
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureOwner($invoice);

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $invoice->load(['lines', 'client', 'attachments']);
        $issuerProfile = IssuerProfile::forUser(auth()->id());

        $this->deliveryService->send(
            $invoice,
            $issuerProfile,
            $validated['recipient_email'],
            $validated['subject'],
            (string) ($validated['body'] ?? ''),
        );

        return back()->with('success', 'Invoice enviada a '.$validated['recipient_email'].'.');
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
            'client_id' => $validated['client_id'] ?? null,
            'from_name' => $validated['from_name'],
            'from_email' => $validated['from_email'] ?? null,
            'from_address' => $validated['from_address'] ?? null,
            'from_nie' => $validated['from_nie'] ?? null,
            'from_additional_info' => $validated['from_additional_info'] ?? null,
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
        $count = Invoice::query()
            ->where('user_id', auth()->id())
            ->whereYear('issue_date', now()->year)
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('INV-%s-%04d', $year, $count);
    }

    private function catalogItemsForForm(): Collection
    {
        $items = CatalogItem::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $allowanceDefinitions = collect(config('pb_allowances.items', []));
        $allowanceByName = $allowanceDefinitions->keyBy('name');

        $allowanceItemIds = $items
            ->filter(fn (CatalogItem $item): bool => $allowanceByName->has($item->name))
            ->pluck('id')
            ->values()
            ->all();

        $usageByCatalogItemId = [];

        if ($allowanceItemIds !== []) {
            $usageByCatalogItemId = InvoiceLine::query()
                ->selectRaw('catalog_item_id, SUM(line_total) as used_total')
                ->whereIn('catalog_item_id', $allowanceItemIds)
                ->whereHas('invoice', function ($query): void {
                    $query->where('user_id', auth()->id());
                    $query->whereBetween('issue_date', [
                        now()->startOfYear()->toDateString(),
                        now()->endOfYear()->toDateString(),
                    ]);
                })
                ->groupBy('catalog_item_id')
                ->pluck('used_total', 'catalog_item_id')
                ->all();
        }

        return $items->each(function (CatalogItem $item) use ($allowanceByName, $usageByCatalogItemId): void {
            $definition = $allowanceByName->get($item->name);

            if (! $definition) {
                $item->setAttribute('allowance_is_tracked', false);

                return;
            }

            $annualLimit = (float) ($definition['annual_limit'] ?? 0);
            $used = (float) ($usageByCatalogItemId[$item->id] ?? 0);

            $item->setAttribute('allowance_is_tracked', true);
            $item->setAttribute('allowance_annual_limit', round($annualLimit, 2));
            $item->setAttribute('allowance_used_current_year', round($used, 2));
            $item->setAttribute('allowance_remaining_current_year', round($annualLimit - $used, 2));
            $item->setAttribute('allowance_currency', config('pb_allowances.currency', 'CAD'));
            $item->setAttribute('allowance_year', now()->year);
        });
    }

    private function ensureOwner(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 404);
    }
}

<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PbAllowanceReportService
{
    public function definitions(): Collection
    {
        return collect(config('pb_allowances.items', []));
    }

    public function yearsForUser(int $userId): Collection
    {
        $currentYear = now()->year;

        $invoiceYears = Invoice::query()
            ->where('user_id', $userId)
            ->whereNotNull('issue_date')
            ->pluck('issue_date')
            ->map(fn (string $issueDate): int => (int) substr($issueDate, 0, 4))
            ->unique()
            ->sort()
            ->values();

        return $invoiceYears
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function normalizeYear(int $year): int
    {
        $currentYear = now()->year;

        return $year >= 2000 && $year <= 2100
            ? $year
            : $currentYear;
    }

    public function allowanceOptionsForUser(int $userId): Collection
    {
        $definitions = $this->definitions();
        $catalogItems = $this->catalogItemsForUser($userId)->keyBy('name');

        return $definitions->map(function (array $definition) use ($catalogItems): array {
            $catalogItem = $catalogItems->get($definition['name']);

            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'catalog_item_id' => $catalogItem?->id,
                'catalog_item_exists' => (bool) $catalogItem,
                'annual_limit' => (float) $definition['annual_limit'],
            ];
        })->values();
    }

    public function summaryForUser(int $userId, int $year): Collection
    {
        $definitions = $this->definitions();
        $catalogItems = $this->catalogItemsForUser($userId)->keyBy('name');
        $catalogItemIds = $catalogItems->pluck('id')->values()->all();
        [$startDate, $endDate] = $this->yearDateRange($year);

        $usageByCatalogItemId = InvoiceLine::query()
            ->selectRaw('catalog_item_id, SUM(line_total) as used_total')
            ->whereIn('catalog_item_id', $catalogItemIds)
            ->whereHas('invoice', function ($query) use ($userId, $startDate, $endDate): void {
                $query->where('user_id', $userId)
                    ->whereBetween('issue_date', [$startDate, $endDate]);
            })
            ->groupBy('catalog_item_id')
            ->pluck('used_total', 'catalog_item_id');

        return $definitions->map(function (array $definition) use ($catalogItems, $usageByCatalogItemId, $year): array {
            $catalogItem = $catalogItems->get($definition['name']);
            $used = $catalogItem ? (float) ($usageByCatalogItemId[$catalogItem->id] ?? 0) : 0.0;
            $annualLimit = (float) $definition['annual_limit'];

            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'annual_limit' => $annualLimit,
                'used' => round($used, 2),
                'remaining' => round($annualLimit - $used, 2),
                'catalog_item_id' => $catalogItem?->id,
                'catalog_item_exists' => (bool) $catalogItem,
                'history_url' => $catalogItem
                    ? route('pb-allowances.history', ['year' => $year, 'allowance' => $definition['key']])
                    : null,
            ];
        })->values();
    }

    public function historyForUser(int $userId, int $year, ?string $allowanceKey = null, ?string $status = null): array
    {
        $allowanceOptions = $this->allowanceOptionsForUser($userId);
        $selectedAllowance = $allowanceKey
            ? $allowanceOptions->firstWhere('key', $allowanceKey)
            : null;

        [$startDate, $endDate] = $this->yearDateRange($year);

        $query = InvoiceLine::query()
            ->with(['invoice.client', 'catalogItem'])
            ->whereHas('invoice', function ($invoiceQuery) use ($userId, $startDate, $endDate, $status): void {
                $invoiceQuery->where('user_id', $userId)
                    ->whereBetween('issue_date', [$startDate, $endDate]);

                if ($status && in_array($status, Invoice::STATUSES, true)) {
                    $invoiceQuery->where('status', $status);
                }
            });

        if ($selectedAllowance) {
            if (! $selectedAllowance['catalog_item_id']) {
                return [
                    'entries' => collect(),
                    'selected_allowance' => $selectedAllowance,
                ];
            }

            $query->where('catalog_item_id', $selectedAllowance['catalog_item_id']);
        } else {
            $catalogItemIds = $allowanceOptions
                ->pluck('catalog_item_id')
                ->filter()
                ->values()
                ->all();

            $query->whereIn('catalog_item_id', $catalogItemIds);
        }

        $entries = $query
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->orderByDesc('invoices.issue_date')
            ->orderByDesc('invoice_lines.id')
            ->select('invoice_lines.*')
            ->get()
            ->map(function (InvoiceLine $line) use ($allowanceOptions): array {
                $invoice = $line->invoice;
                $catalogItem = $line->catalogItem;
                $allowance = $allowanceOptions->firstWhere('catalog_item_id', $line->catalog_item_id);

                return [
                    'date' => $invoice->issue_date,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_url' => route('invoices.show', $invoice),
                    'status' => $invoice->status,
                    'allowance_key' => $allowance['key'] ?? null,
                    'allowance_name' => $allowance['name'] ?? ($catalogItem?->name ?? $line->description),
                    'description' => $line->description,
                    'amount' => (float) $line->line_total,
                    'currency' => $invoice->currency,
                    'client_name' => $invoice->client?->name ?? $invoice->client_name,
                ];
            });

        return [
            'entries' => $entries,
            'selected_allowance' => $selectedAllowance,
        ];
    }

    private function catalogItemsForUser(int $userId): EloquentCollection
    {
        $allowanceNames = $this->definitions()->pluck('name')->all();

        return CatalogItem::query()
            ->where('user_id', $userId)
            ->whereIn('name', $allowanceNames)
            ->get(['id', 'name']);
    }

    private function yearDateRange(int $year): array
    {
        return [
            Carbon::create($year)->startOfYear()->toDateString(),
            Carbon::create($year)->endOfYear()->toDateString(),
        ];
    }
}

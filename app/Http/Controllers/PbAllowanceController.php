<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PbAllowanceController extends Controller
{
    public function index(Request $request): View
    {
        $currentYear = now()->year;
        $selectedYear = (int) $request->integer('year', $currentYear);

        if ($selectedYear < 2000 || $selectedYear > 2100) {
            $selectedYear = $currentYear;
        }

        $allowanceDefinitions = collect(config('pb_allowances.items', []));
        $allowanceNames = $allowanceDefinitions->pluck('name')->all();

        $catalogItems = CatalogItem::query()
            ->where('user_id', auth()->id())
            ->whereIn('name', $allowanceNames)
            ->get(['id', 'name'])
            ->keyBy('name');

        $catalogItemIds = $catalogItems->pluck('id')->values()->all();

        $startDate = Carbon::create($selectedYear)->startOfYear()->toDateString();
        $endDate = Carbon::create($selectedYear)->endOfYear()->toDateString();

        $usageByCatalogItemId = InvoiceLine::query()
            ->selectRaw('catalog_item_id, SUM(line_total) as used_total')
            ->whereIn('catalog_item_id', $catalogItemIds)
            ->whereHas('invoice', function ($query) use ($startDate, $endDate): void {
                $query->where('user_id', auth()->id());
                $query->whereBetween('issue_date', [$startDate, $endDate]);
            })
            ->groupBy('catalog_item_id')
            ->pluck('used_total', 'catalog_item_id');

        $allowances = $allowanceDefinitions->map(function (array $definition) use ($catalogItems, $usageByCatalogItemId): array {
            $catalogItem = $catalogItems->get($definition['name']);
            $used = $catalogItem ? (float) ($usageByCatalogItemId[$catalogItem->id] ?? 0) : 0.0;
            $annualLimit = (float) $definition['annual_limit'];

            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'annual_limit' => $annualLimit,
                'used' => round($used, 2),
                'remaining' => round($annualLimit - $used, 2),
                'catalog_item_exists' => (bool) $catalogItem,
            ];
        })->values();

        $invoiceYears = Invoice::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('issue_date')
            ->pluck('issue_date')
            ->map(fn (string $issueDate): int => (int) substr($issueDate, 0, 4))
            ->unique()
            ->sort()
            ->values();

        $years = $invoiceYears
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();

        return view('pb-allowances.index', [
            'allowances' => $allowances,
            'selectedYear' => $selectedYear,
            'years' => $years,
            'currency' => config('pb_allowances.currency', 'CAD'),
        ]);
    }
}

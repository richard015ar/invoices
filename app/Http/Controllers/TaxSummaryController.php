<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\UserSetting;
use App\Services\CurrencyConverter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaxSummaryController extends Controller
{
    public function __construct(
        private CurrencyConverter $converter
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $settings = UserSetting::forUser($user->id);

        $year = $request->input('year', now()->year);
        $availableYears = $this->getAvailableYears($user->id);

        $quarterlyData = $this->calculateQuarterlyData($user->id, $year);
        $yearTotal = $this->calculateYearTotal($quarterlyData);
        $currentQuarterProgress = $this->getCurrentQuarterProgress($user->id);

        return view('tax-summary.index', [
            'settings' => $settings,
            'year' => $year,
            'availableYears' => $availableYears,
            'quarterlyData' => $quarterlyData,
            'yearTotal' => $yearTotal,
            'currentQuarterProgress' => $currentQuarterProgress,
            'exchangeRates' => $this->getExchangeRates(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'monthly_tax_reserve' => 'required|numeric|min:0|max:99999.99',
        ]);

        $settings = UserSetting::forUser(auth()->id());
        $settings->update([
            'monthly_tax_reserve' => $request->monthly_tax_reserve,
        ]);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }

    private function getAvailableYears(int $userId): array
    {
        $firstInvoice = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->orderBy('issue_date')
            ->first();

        $startYear = $firstInvoice ? $firstInvoice->issue_date->year : now()->year;
        $currentYear = now()->year;

        return range($currentYear, $startYear, -1);
    }

    private function calculateQuarterlyData(int $userId, int $year): array
    {
        $quarters = [
            1 => ['start' => 1, 'end' => 3, 'name' => 'Q1 (Ene-Mar)'],
            2 => ['start' => 4, 'end' => 6, 'name' => 'Q2 (Abr-Jun)'],
            3 => ['start' => 7, 'end' => 9, 'name' => 'Q3 (Jul-Sep)'],
            4 => ['start' => 10, 'end' => 12, 'name' => 'Q4 (Oct-Dic)'],
        ];

        $settings = UserSetting::forUser($userId);
        $monthlyReserve = (float) $settings->monthly_tax_reserve;

        $data = [];

        foreach ($quarters as $q => $info) {
            $invoices = Invoice::where('user_id', $userId)
                ->where('status', 'paid')
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', '>=', $info['start'])
                ->whereMonth('issue_date', '<=', $info['end'])
                ->get();

            $totalCad = 0;
            $totalEur = 0;

            foreach ($invoices as $invoice) {
                $amount = (float) $invoice->grand_total;
                $currency = strtoupper($invoice->currency);

                if ($currency === 'CAD') {
                    $totalCad += $amount;
                    $totalEur += $this->converter->convertToEur($amount, 'CAD');
                } elseif ($currency === 'EUR') {
                    $totalEur += $amount;
                    $totalCad += $this->converter->convertToCad($amount, 'EUR');
                } else {
                    // USD or other - convert to both
                    $totalCad += $this->converter->convertToCad($amount, $currency);
                    $totalEur += $this->converter->convertToEur($amount, $currency);
                }
            }

            // Calculate months in this quarter (for current year, might be partial)
            $monthsInQuarter = 3;
            if ($year == now()->year) {
                $currentMonth = now()->month;
                if ($currentMonth < $info['start']) {
                    $monthsInQuarter = 0;
                } elseif ($currentMonth <= $info['end']) {
                    $monthsInQuarter = $currentMonth - $info['start'] + 1;
                }
            }

            $data[$q] = [
                'name' => $info['name'],
                'invoices_count' => $invoices->count(),
                'total_cad' => round($totalCad, 2),
                'total_eur' => round($totalEur, 2),
                'months' => $monthsInQuarter,
                'tax_reserve' => round($monthlyReserve * 3, 2), // Full quarter reserve
                'tax_reserve_to_date' => round($monthlyReserve * $monthsInQuarter, 2),
            ];
        }

        return $data;
    }

    private function calculateYearTotal(array $quarterlyData): array
    {
        $total = [
            'invoices_count' => 0,
            'total_cad' => 0,
            'total_eur' => 0,
            'months' => 0,
            'tax_reserve' => 0,
            'tax_reserve_to_date' => 0,
        ];

        foreach ($quarterlyData as $quarter) {
            $total['invoices_count'] += $quarter['invoices_count'];
            $total['total_cad'] += $quarter['total_cad'];
            $total['total_eur'] += $quarter['total_eur'];
            $total['months'] += $quarter['months'];
            $total['tax_reserve'] += $quarter['tax_reserve'];
            $total['tax_reserve_to_date'] += $quarter['tax_reserve_to_date'];
        }

        return $total;
    }

    private function getCurrentQuarterProgress(int $userId): array
    {
        $now = now();
        $currentQuarter = ceil($now->month / 3);
        $quarterStart = Carbon::create($now->year, ($currentQuarter - 1) * 3 + 1, 1);
        $quarterEnd = $quarterStart->copy()->addMonths(3)->subDay();

        $invoices = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('issue_date', [$quarterStart, $quarterEnd])
            ->get();

        $settings = UserSetting::forUser($userId);
        $monthlyReserve = (float) $settings->monthly_tax_reserve;

        $totalCad = 0;
        $totalEur = 0;

        foreach ($invoices as $invoice) {
            $amount = (float) $invoice->grand_total;
            $currency = strtoupper($invoice->currency);

            if ($currency === 'CAD') {
                $totalCad += $amount;
                $totalEur += $this->converter->convertToEur($amount, 'CAD');
            } elseif ($currency === 'EUR') {
                $totalEur += $amount;
                $totalCad += $this->converter->convertToCad($amount, 'EUR');
            } else {
                $totalCad += $this->converter->convertToCad($amount, $currency);
                $totalEur += $this->converter->convertToEur($amount, $currency);
            }
        }

        $monthsElapsed = $now->month - $quarterStart->month + 1;

        return [
            'quarter' => $currentQuarter,
            'quarter_name' => "Q{$currentQuarter}",
            'start_date' => $quarterStart->format('d M'),
            'end_date' => $quarterEnd->format('d M'),
            'invoices_count' => $invoices->count(),
            'total_cad' => round($totalCad, 2),
            'total_eur' => round($totalEur, 2),
            'months_elapsed' => $monthsElapsed,
            'tax_reserve_to_date' => round($monthlyReserve * $monthsElapsed, 2),
            'days_remaining' => (int) $now->diffInDays($quarterEnd),
        ];
    }

    private function getExchangeRates(): array
    {
        return [
            'CAD_EUR' => $this->converter->getRate('CAD', 'EUR'),
            'EUR_CAD' => $this->converter->getRate('EUR', 'CAD'),
        ];
    }
}

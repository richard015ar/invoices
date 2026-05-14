<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PbAllowanceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PbAllowanceController extends Controller
{
    public function __construct(
        private readonly PbAllowanceReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $userId = auth()->id();
        $selectedYear = $this->reportService->normalizeYear((int) $request->integer('year', now()->year));

        return view('pb-allowances.index', [
            'allowances' => $this->reportService->summaryForUser($userId, $selectedYear),
            'selectedYear' => $selectedYear,
            'years' => $this->reportService->yearsForUser($userId),
            'currency' => config('pb_allowances.currency', 'CAD'),
        ]);
    }

    public function history(Request $request): View
    {
        $userId = auth()->id();
        $selectedYear = $this->reportService->normalizeYear((int) $request->integer('year', now()->year));
        $allowanceOptions = $this->reportService->allowanceOptionsForUser($userId);
        $selectedAllowanceKey = (string) $request->string('allowance')->toString();
        $selectedStatus = (string) $request->string('status')->toString();

        if (! $allowanceOptions->pluck('key')->contains($selectedAllowanceKey)) {
            $selectedAllowanceKey = '';
        }

        if (! in_array($selectedStatus, Invoice::STATUSES, true)) {
            $selectedStatus = '';
        }

        $history = $this->reportService->historyForUser(
            $userId,
            $selectedYear,
            $selectedAllowanceKey !== '' ? $selectedAllowanceKey : null,
            $selectedStatus !== '' ? $selectedStatus : null,
        );

        return view('pb-allowances.history', [
            'entries' => $history['entries'],
            'selectedAllowance' => $history['selected_allowance'],
            'selectedAllowanceKey' => $selectedAllowanceKey,
            'selectedStatus' => $selectedStatus,
            'selectedYear' => $selectedYear,
            'allowanceOptions' => $allowanceOptions,
            'years' => $this->reportService->yearsForUser($userId),
            'statuses' => Invoice::STATUSES,
            'currency' => config('pb_allowances.currency', 'CAD'),
        ]);
    }
}

<?php

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Smalot\PdfParser\Parser;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('invoices:import-legacy-pdfs {path=storage/imports}', function (string $path): void {
    $fullPath = base_path($path);
    $files = glob($fullPath . '/*.pdf') ?: [];
    sort($files);

    if ($files === []) {
        $this->warn('No PDF files found at: ' . $fullPath);
        return;
    }

    $parser = new Parser();

    $extractText = function (string $file) use ($parser): string {
        $text = '';

        try {
            $text = trim($parser->parseFile($file)->getText());
        } catch (Throwable) {
            $text = '';
        }

        if (preg_replace('/\s+/', '', $text) !== '') {
            return preg_replace('/\s+/', ' ', $text) ?? '';
        }

        $tmpBase = '/tmp/invoice_ocr_' . md5($file . microtime(true));
        $png = $tmpBase . '.png';
        @exec('pdftoppm -f 1 -singlefile -png ' . escapeshellarg($file) . ' ' . escapeshellarg($tmpBase));
        $output = [];
        @exec('tesseract ' . escapeshellarg($png) . ' stdout -l eng 2>/dev/null', $output);
        @unlink($png);

        return preg_replace('/\s+/', ' ', trim(implode(' ', $output))) ?? '';
    };

    $parseDate = function (?string $value): ?Carbon {
        if (! $value) {
            return null;
        }

        $normalized = preg_replace('/([A-Za-z])([0-9])/', '$1 $2', $value) ?? $value;
        $normalized = preg_replace('/\s+/', ' ', str_replace(',', ' ', $normalized)) ?? $normalized;

        try {
            return Carbon::parse($normalized);
        } catch (Throwable) {
            return null;
        }
    };

    $parseAmount = function (string $text): float {
        $candidates = [];
        if (preg_match_all('/(Amount Due|Balance Due|Total|Subtotal)\s*(?:C\$|\$|USD)?\s*([0-9][0-9,]*\.[0-9]{2})/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $label = strtolower($match[1]);
                $amount = (float) str_replace(',', '', $match[2]);
                if ($amount <= 0) {
                    continue;
                }
                $priority = match (true) {
                    str_contains($label, 'amount due') => 1,
                    str_contains($label, 'balance due') => 2,
                    str_contains($label, 'total') => 3,
                    default => 4,
                };
                $candidates[] = [$priority, $amount];
            }
        }

        if ($candidates === [] && preg_match_all('/(?:C\$|\$)\s*([0-9][0-9,]*\.[0-9]{2})/', $text, $rawMatches)) {
            $rawAmounts = array_map(
                fn (string $value): float => (float) str_replace(',', '', $value),
                $rawMatches[1] ?? []
            );
            $rawAmounts = array_values(array_filter($rawAmounts, fn (float $value): bool => $value > 0));
            if ($rawAmounts !== []) {
                return (float) max($rawAmounts);
            }
        }

        if ($candidates === []) {
            return 0.0;
        }

        usort($candidates, fn (array $a, array $b) => $a[0] <=> $b[0]);

        return (float) $candidates[0][1];
    };

    $imported = 0;
    $skipped = 0;

    foreach ($files as $file) {
        $basename = basename($file);
        $marker = '[legacy-import:' . $basename . ']';

        $existing = Invoice::query()->where('notes', 'like', '%' . $marker . '%')->first();

        $text = $extractText($file);
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        preg_match('/ISSUED\s+([0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{4})/i', $text, $issuedMatch);
        preg_match('/Date:\s*([A-Za-z]+\s*[0-9]{1,2},?\s*[0-9]{4})/i', $text, $dateMatch);
        preg_match('/DUE\s+([0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{4})/i', $text, $dueMatch);
        preg_match('/Due Date:\s*([A-Za-z]+\s*[0-9]{1,2},?\s*[0-9]{4})/i', $text, $dueDateMatch);
        preg_match('/BILL TO\s+(.+?)\s+(Tax Reg No|INVOICE NUMBER|Date:|ISSUED)/i', $text, $clientMatch);
        preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $text, $emailMatch);

        $issueDate = $parseDate($issuedMatch[1] ?? $dateMatch[1] ?? null);
        $dueDate = $parseDate($dueMatch[1] ?? $dueDateMatch[1] ?? null);
        $clientName = trim($clientMatch[1] ?? 'Book Oven Inc (dba. Pressbooks)');
        $total = $parseAmount($text);

        if (! $issueDate && preg_match('/(Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?|Jan(?:uary)?|Feb(?:ruary)?)\s+([0-9]{4})/i', $basename, $monthYear)) {
            $issueDate = $parseDate('1 ' . $monthYear[1] . ' ' . $monthYear[2]);
        }

        $issueDate = $issueDate ?: now();
        $dueDate = $dueDate ?: (clone $issueDate)->addDays(7);
        $currency = str_contains($text, 'C$') ? 'CAD' : 'USD';
        $invoiceNumber = 'LEG-' . $issueDate->format('Y') . '-' . strtoupper(substr(md5($basename), 0, 6));

        if ($existing) {
            if ((float) $existing->grand_total > 0 || $total <= 0) {
                $skipped++;
                $this->line('Skipped (already imported): ' . $basename);
                continue;
            }

            $existing->update([
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'currency' => $currency,
                'client_name' => $clientName,
                'from_email' => $emailMatch[1] ?? $existing->from_email,
                'subtotal' => round($total, 2),
                'tax_total' => 0,
                'grand_total' => round($total, 2),
            ]);

            $existing->lines()->delete();
            $existing->lines()->create([
                'position' => 0,
                'description' => 'Legacy invoice import - ' . pathinfo($basename, PATHINFO_FILENAME),
                'quantity' => 1,
                'unit_price' => round($total, 2),
                'tax_rate' => 0,
                'line_total' => round($total, 2),
            ]);

            $imported++;
            $this->info("Updated: {$basename} -> {$existing->invoice_number} ({$currency} " . number_format($total, 2) . ')');
            continue;
        }

        $invoice = Invoice::query()->create([
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'status' => 'paid',
            'currency' => $currency,
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo Aragon',
            'from_email' => $emailMatch[1] ?? null,
            'from_address' => null,
            'client_name' => $clientName,
            'client_email' => 'contact@pressbooks.com',
            'client_address' => null,
            'client_details' => null,
            'notes' => $marker . ' Imported automatically from legacy PDF: ' . $basename,
            'subtotal' => round($total, 2),
            'tax_total' => 0,
            'grand_total' => round($total, 2),
        ]);

        $invoice->lines()->create([
            'position' => 0,
            'description' => 'Legacy invoice import - ' . pathinfo($basename, PATHINFO_FILENAME),
            'quantity' => 1,
            'unit_price' => round($total, 2),
            'tax_rate' => 0,
            'line_total' => round($total, 2),
        ]);

        $imported++;
        $this->info("Imported: {$basename} -> {$invoiceNumber} ({$currency} " . number_format($total, 2) . ')');
    }

    $this->newLine();
    $this->info("Done. Imported: {$imported}. Skipped: {$skipped}.");
})->purpose('Import legacy invoice PDFs from a directory');

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'sent', 'paid'];

    protected $fillable = [
        'invoice_number',
        'issue_date',
        'due_date',
        'status',
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
        'subtotal',
        'tax_total',
        'grand_total',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->lines()->sum('line_total');
        $taxTotal = (float) $this->lines()->get()->sum(function (InvoiceLine $line): float {
            return ((float) $line->quantity * (float) $line->unit_price) * ((float) $line->tax_rate / 100);
        });

        $this->forceFill([
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($subtotal + $taxTotal, 2),
        ])->save();
    }
}

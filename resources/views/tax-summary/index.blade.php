@extends('layouts.app')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2>Resumen Fiscal {{ $year }}</h2>
        <form method="GET" action="{{ route('tax-summary.index') }}" class="year-selector">
            <label for="year">Año:</label>
            <select name="year" id="year" onchange="this.form.submit()">
                @foreach ($availableYears as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Configuración de reserva mensual --}}
    <div class="settings-card">
        <form method="POST" action="{{ route('tax-summary.settings') }}" class="settings-form">
            @csrf
            <label for="monthly_tax_reserve">Reserva mensual para impuestos (EUR):</label>
            <input
                type="number"
                name="monthly_tax_reserve"
                id="monthly_tax_reserve"
                value="{{ $settings->monthly_tax_reserve }}"
                step="0.01"
                min="0"
                class="input-field"
            >
            <button type="submit" class="btn-secondary">Guardar</button>
        </form>
        <p class="hint">Tipo de cambio actual: 1 CAD = {{ number_format($exchangeRates['CAD_EUR'], 4) }} EUR | 1 EUR = {{ number_format($exchangeRates['EUR_CAD'], 4) }} CAD</p>
    </div>

    {{-- Progreso del trimestre actual --}}
    <div class="current-quarter-card">
        <h3>Trimestre Actual ({{ $currentQuarterProgress['quarter_name'] }}: {{ $currentQuarterProgress['start_date'] }} - {{ $currentQuarterProgress['end_date'] }})</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Facturas pagadas</span>
                <span class="stat-value">{{ $currentQuarterProgress['invoices_count'] }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total CAD</span>
                <span class="stat-value">${{ number_format($currentQuarterProgress['total_cad'], 2) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total EUR</span>
                <span class="stat-value">€{{ number_format($currentQuarterProgress['total_eur'], 2) }}</span>
            </div>
            <div class="stat-item highlight">
                <span class="stat-label">A reservar ({{ $currentQuarterProgress['months_elapsed'] }} meses)</span>
                <span class="stat-value">€{{ number_format($currentQuarterProgress['tax_reserve_to_date'], 2) }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Días restantes</span>
                <span class="stat-value">{{ $currentQuarterProgress['days_remaining'] }}</span>
            </div>
        </div>
    </div>

    {{-- Tabla trimestral --}}
    <h3>Resumen por Trimestre - {{ $year }}</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Trimestre</th>
                    <th>Facturas</th>
                    <th>Total CAD</th>
                    <th>Total EUR</th>
                    <th>Meses</th>
                    <th>A Reservar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quarterlyData as $q => $data)
                    <tr>
                        <td><strong>{{ $data['name'] }}</strong></td>
                        <td>{{ $data['invoices_count'] }}</td>
                        <td>${{ number_format($data['total_cad'], 2) }}</td>
                        <td>€{{ number_format($data['total_eur'], 2) }}</td>
                        <td>{{ $data['months'] }}</td>
                        <td class="highlight-cell">€{{ number_format($data['tax_reserve_to_date'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>TOTAL {{ $year }}</strong></td>
                    <td><strong>{{ $yearTotal['invoices_count'] }}</strong></td>
                    <td><strong>${{ number_format($yearTotal['total_cad'], 2) }}</strong></td>
                    <td><strong>€{{ number_format($yearTotal['total_eur'], 2) }}</strong></td>
                    <td><strong>{{ $yearTotal['months'] }}</strong></td>
                    <td class="highlight-cell"><strong>€{{ number_format($yearTotal['tax_reserve_to_date'], 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="note">
        <strong>Nota:</strong> "A Reservar" es el total que deberías apartar para impuestos
        (€{{ number_format($settings->monthly_tax_reserve, 2) }}/mes × meses transcurridos).
        El año completo requiere €{{ number_format($settings->monthly_tax_reserve * 12, 2) }}.
    </p>
</section>

<style>
    .year-selector {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .year-selector select {
        padding: 0.5rem;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    .settings-card {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .settings-form {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .settings-form .input-field {
        width: 120px;
        padding: 0.5rem;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .hint {
        margin-top: 0.5rem;
        font-size: 0.85rem;
        color: #666;
    }
    .current-quarter-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }
    .current-quarter-card h3 {
        margin-bottom: 1rem;
        color: white;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    .stat-item {
        background: rgba(255,255,255,0.15);
        padding: 1rem;
        border-radius: 6px;
        text-align: center;
    }
    .stat-item.highlight {
        background: rgba(255,255,255,0.3);
        border: 2px solid rgba(255,255,255,0.5);
    }
    .stat-label {
        display: block;
        font-size: 0.8rem;
        opacity: 0.9;
        margin-bottom: 0.3rem;
    }
    .stat-value {
        display: block;
        font-size: 1.4rem;
        font-weight: bold;
    }
    .highlight-cell {
        background: #fff3cd;
        font-weight: 600;
    }
    .total-row {
        background: #e9ecef;
    }
    .total-row .highlight-cell {
        background: #ffc107;
        color: #000;
    }
    .note {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #e7f3ff;
        border-left: 4px solid #0066cc;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    h3 {
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }
</style>
@endsection

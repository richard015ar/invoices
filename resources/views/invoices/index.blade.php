@extends('layouts.app')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2>My invoices</h2>
        <a class="btn-primary" href="{{ route('invoices.create') }}">Crear invoice</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Numero</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->client_name }}</td>
                        <td>{{ $invoice->issue_date?->format('Y-m-d') }}</td>
                        <td>
                            <form method="POST" action="{{ route('invoices.status.update', $invoice) }}" class="status-chips">
                                @csrf
                                @method('PATCH')
                                @foreach (\App\Models\Invoice::STATUSES as $status)
                                    <button
                                        type="submit"
                                        name="status"
                                        value="{{ $status }}"
                                        class="chip chip-{{ $status }} {{ $invoice->status === $status ? 'is-active' : '' }}"
                                    >
                                        {{ strtoupper($status) }}
                                    </button>
                                @endforeach
                            </form>
                        </td>
                        <td>{{ $invoice->currency }} {{ number_format((float) $invoice->grand_total, 2) }}</td>
                        <td class="actions">
                            <a class="action-icon" href="{{ route('invoices.show', $invoice) }}" title="Ver invoice" aria-label="Ver invoice">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6"/>
                                    <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.6"/>
                                </svg>
                            </a>
                            <a class="action-icon" href="{{ route('invoices.edit', $invoice) }}" title="Editar invoice" aria-label="Editar invoice">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m14.8 4.8 4.4 4.4M4 20h4l10.4-10.4a1.6 1.6 0 0 0 0-2.2l-1.8-1.8a1.6 1.6 0 0 0-2.2 0L4 16v4Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a class="action-icon" href="{{ route('invoices.create', ['clone_from' => $invoice->id]) }}" title="Clonar invoice" aria-label="Clonar invoice">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="8" y="8" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No hay invoices todavia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}
</section>
@endsection

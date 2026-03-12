@extends('layouts.app')

@section('content')
<section class="panel invoice-view" style="--accent: {{ $invoice->accent_color }}; --template-bg: {{ $template['background'] ?? '#f0fdfa' }}; --template-header: {{ $template['header'] ?? '#0f766e' }};">
    <div class="panel-head">
        <h2>{{ $invoice->invoice_number }}</h2>
        <div class="actions">
            <a class="btn-secondary" href="{{ route('invoices.edit', $invoice) }}">Editar</a>
            <a class="btn-secondary" href="{{ route('invoices.pdf', $invoice) }}">PDF</a>
            <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}">
                @csrf
                <button class="btn-secondary" type="submit">Duplicar</button>
            </form>
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Seguro que quieres eliminar esta invoice?')">
                @csrf
                @method('DELETE')
                <button class="btn-danger" type="submit">Eliminar</button>
            </form>
        </div>
    </div>

    <details class="panel nested send-panel">
        <summary>Enviar</summary>
        <form method="POST" action="{{ route('invoices.send', $invoice) }}" class="stack" enctype="multipart/form-data">
            @csrf
            <div class="grid cols-2">
                <label>
                    Destinatario
                    <input
                        type="email"
                        name="recipient_email"
                        required
                        value="{{ old('recipient_email', $invoice->client_email ?: 'billingadmin@pressbooks.com') }}"
                    />
                </label>
                <label>
                    Asunto
                    <input
                        name="subject"
                        required
                        value="{{ old('subject', 'Invoice ' . $invoice->invoice_number) }}"
                    />
                </label>
            </div>
            <label>
                Body (texto simple)
                <textarea name="body" rows="6">{{ old('body', "Hi,\n\nPlease find attached invoice {$invoice->invoice_number}.\n\nThanks,") }}</textarea>
            </label>
            <p class="send-note">
                Se enviara el PDF de la invoice y {{ $invoice->attachments->count() }} documento(s) asociado(s).
            </p>
            <div>
                <button class="btn-primary" type="submit">Enviar email con PDF</button>
            </div>
        </form>
    </details>

    <article class="invoice-card">
        <header>
            <div>
                <h3>Invoice</h3>
                <p>{{ $invoice->invoice_number }}</p>
            </div>
            <div>
                <p><strong>Fecha:</strong> {{ $invoice->issue_date?->format('Y-m-d') }}</p>
                <p><strong>Vence:</strong> {{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</p>
                <p><strong>Estado:</strong> {{ strtoupper($invoice->status) }}</p>
            </div>
        </header>

        <section class="grid cols-2">
            <div>
                <h4>Desde</h4>
                <p>{{ $displayIssuer['name'] }}</p>
                <p>{{ $displayIssuer['email'] }}</p>
                <p>{{ $displayIssuer['address'] }}</p>
                @if ($displayIssuer['nie'])
                    <p><strong>NIE:</strong> {{ $displayIssuer['nie'] }}</p>
                @endif
                @if ($displayIssuer['additional_info'])
                    <p>{{ $displayIssuer['additional_info'] }}</p>
                @endif
            </div>
            <div>
                <h4>Para</h4>
                <p>{{ $displayClient['name'] }}</p>
                <p>{{ $displayClient['email'] }}</p>
                <p>{{ $displayClient['address'] }}</p>
                @if ($displayClient['details'])
                    <p><strong>Detalles:</strong> {{ $displayClient['details'] }}</p>
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Descripcion</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Imp %</th>
                    <th>Linea</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    <tr>
                        <td>{{ $line->description }}</td>
                        <td>{{ number_format((float) $line->quantity, 2) }}</td>
                        <td>{{ $invoice->currency }} {{ number_format((float) $line->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $line->tax_rate, 2) }}</td>
                        <td>{{ $invoice->currency }} {{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-box">
            <p>Subtotal: <strong>{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</strong></p>
            <p>Impuestos: <strong>{{ $invoice->currency }} {{ number_format((float) $invoice->tax_total, 2) }}</strong></p>
            <p>Total: <strong>{{ $invoice->currency }} {{ number_format((float) $invoice->grand_total, 2) }}</strong></p>
        </div>

        @if ($invoice->notes)
            <div class="notes">
                <h4>Notas</h4>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <div class="notes">
            <h4>Documentos asociados</h4>
            @if ($invoice->attachments->isEmpty())
                <p>No hay documentos asociados a esta invoice.</p>
            @else
                <div class="stack">
                    @foreach ($invoice->attachments as $attachment)
                        <a href="{{ route('invoices.attachments.download', [$invoice, $attachment]) }}" class="attachment-link">
                            {{ $attachment->original_name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </article>
</section>
@endsection

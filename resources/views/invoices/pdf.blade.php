<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2f3237;
            font-size: 11px;
            line-height: 1.35;
        }
        .sheet {
            border: 1px solid #c9cdd2;
            padding: 42px 44px 34px;
        }
        .top {
            width: 100%;
            margin-bottom: 24px;
        }
        .top td { vertical-align: top; }
        .brand {
            font-family: DejaVu Serif, serif;
            font-size: 56px;
            color: #3f4349;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }
        .brand-sub {
            font-size: 12px;
            color: #5a6168;
            margin-bottom: 12px;
        }
        .meta {
            margin-top: 8px;
            width: 100%;
        }
        .meta td { padding: 0; width: 50%; }
        .meta-label {
            font-size: 10px;
            letter-spacing: 1px;
            color: #646a72;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 13px;
            color: #2f3237;
        }
        .leaf-wrap {
            text-align: right;
            width: 140px;
        }
        .leaf {
            width: 82px;
            height: 82px;
            margin-left: auto;
            border: 1px solid #8fb1a2;
            border-radius: 68% 30% 68% 30%;
            transform: rotate(-18deg);
            position: relative;
            background: #b4d2c6;
        }
        .leaf:before,
        .leaf:after {
            content: "";
            position: absolute;
            border: 1px solid #6f9787;
            border-radius: 50%;
            opacity: 0.75;
        }
        .leaf:before {
            width: 58px;
            height: 22px;
            top: 30px;
            left: 10px;
            transform: rotate(14deg);
        }
        .leaf:after {
            width: 48px;
            height: 18px;
            top: 18px;
            left: 18px;
            transform: rotate(-12deg);
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 34px;
        }
        .items th,
        .items td {
            border-bottom: 1px solid #9ba0a8;
            border-right: 1px solid #9ba0a8;
            padding: 8px 10px;
            font-size: 11px;
        }
        .items th:last-child,
        .items td:last-child { border-right: 0; }
        .items th {
            background: #dce7e2;
            color: #3f4349;
            font-weight: 600;
        }
        .desc { width: 50%; }
        .qty, .price, .line { width: 16%; text-align: right; }
        .empty-row td { height: 24px; color: transparent; }
        .total-label,
        .total-value {
            background: #dce7e2;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .total-value { text-align: right; }
        .footer {
            margin-top: 78px;
            width: 100%;
        }
        .footer td {
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }
        .footer-title {
            font-family: DejaVu Serif, serif;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 0.4px;
            color: #444a50;
        }
        .footer-right {
            text-align: right;
            padding-right: 0;
            padding-left: 12px;
        }
    </style>
</head>
<body>
    @php
        $minRows = 9;
        $lineCount = $invoice->lines->count();
        $emptyRows = max(0, $minRows - $lineCount);
    @endphp
    <div class="sheet">
        <table class="top" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="brand">{{ $displayIssuer['name'] }}</div>
                    <div class="brand-sub">
                        {!! nl2br(e(trim(
                            ($displayIssuer['email'] ?? '') . "\n" .
                            ($displayIssuer['address'] ?? '') . "\n" .
                            (($displayIssuer['nie'] ?? '') ? 'NIE: ' . $displayIssuer['nie'] : '') . "\n" .
                            ($displayIssuer['additional_info'] ?? '')
                        ))) !!}
                    </div>
                    <table class="meta" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <div class="meta-label">Fecha de Emision</div>
                                <div class="meta-value">{{ $invoice->issue_date?->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="meta-label">Factura Nro.</div>
                                <div class="meta-value">#{{ $invoice->invoice_number }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="leaf-wrap">
                    <div class="leaf"></div>
                </td>
            </tr>
        </table>

        <table class="items" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th class="desc">Descripcion</th>
                    <th class="qty">Cantidad</th>
                    <th class="price">Precio</th>
                    <th class="line">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    <tr>
                        <td class="desc">{{ $line->description }}</td>
                        <td class="qty">{{ number_format((float) $line->quantity, 2) }}</td>
                        <td class="price">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="line">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @endforeach
                @for ($i = 0; $i < $emptyRows; $i++)
                    <tr class="empty-row">
                        <td class="desc">.</td>
                        <td class="qty">.</td>
                        <td class="price">.</td>
                        <td class="line">.</td>
                    </tr>
                @endfor
                <tr>
                    <td class="desc"></td>
                    <td class="qty"></td>
                    <td class="price total-label">Total</td>
                    <td class="line total-value">{{ $invoice->currency }} {{ number_format((float) $invoice->grand_total, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="footer" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="footer-title">{{ $displayClient['name'] }}</div>
                    {!! nl2br(e(trim(($displayClient['email'] ?? '') . "\n" . ($displayClient['address'] ?? '') . "\n" . ($displayClient['details'] ?? '')))) !!}
                </td>
                <td class="footer-right">
                    @if($invoice->notes)
                        <div class="footer-title">Notas</div>
                        {!! nl2br(e($invoice->notes)) !!}
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

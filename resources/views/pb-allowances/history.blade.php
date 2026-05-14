@extends('layouts.app')

@php
    $formatMoney = fn (float $amount, string $entryCurrency = null): string => ($entryCurrency ?: $currency) . ' ' . number_format($amount, 2);
@endphp

@section('content')
<section class="panel stack-lg">
    <div class="panel-head allowance-history-head">
        <div>
            <h2>Allowance history</h2>
            <p class="eyebrow">Detailed expense lines for PB allowances</p>
        </div>

        <a href="{{ route('pb-allowances.index', ['year' => $selectedYear]) }}" class="btn-secondary">Back to summary</a>
    </div>

    <form method="GET" action="{{ route('pb-allowances.history') }}" class="allowance-filters">
        <label for="year">
            Year
            <select id="year" name="year" onchange="this.form.submit()">
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
                @endforeach
            </select>
        </label>

        <label for="allowance">
            Allowance
            <select id="allowance" name="allowance" onchange="this.form.submit()">
                <option value="">All allowances</option>
                @foreach ($allowanceOptions as $option)
                    <option value="{{ $option['key'] }}" @selected($selectedAllowanceKey === $option['key'])>
                        {{ $option['name'] }}
                    </option>
                @endforeach
            </select>
        </label>

        <label for="status">
            Status
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>
                        {{ strtoupper($status) }}
                    </option>
                @endforeach
            </select>
        </label>
    </form>

    @if ($selectedAllowance)
        <div class="allowance-history-summary">
            <strong>{{ $selectedAllowance['name'] }}</strong>
            <span>Annual limit: {{ $formatMoney($selectedAllowance['annual_limit']) }}</span>
        </div>
    @endif

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Allowance</th>
                    <th>Description</th>
                    <th>Invoice</th>
                    <th>Status</th>
                    <th>Client</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ $entry['date']?->format('Y-m-d') }}</td>
                        <td>{{ $entry['allowance_name'] }}</td>
                        <td>{{ $entry['description'] }}</td>
                        <td><a href="{{ $entry['invoice_url'] }}" class="table-link">{{ $entry['invoice_number'] }}</a></td>
                        <td><span class="badge badge-{{ $entry['status'] }}">{{ strtoupper($entry['status']) }}</span></td>
                        <td>{{ $entry['client_name'] }}</td>
                        <td>{{ $formatMoney($entry['amount'], $entry['currency']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No allowance usage found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

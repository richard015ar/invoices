@extends('layouts.app')

@php
    $formatMoney = fn (float $amount): string => $currency . ' ' . number_format($amount, 2);
@endphp

@section('content')
<section class="panel stack-lg">
    <div class="panel-head">
        <div>
            <h2>PB Allowances</h2>
            <p class="eyebrow">Track anual usage by allowance type</p>
        </div>

        <form method="GET" action="{{ route('pb-allowances.index') }}" class="year-filter">
            <label for="year">
                Year
                <select id="year" name="year" onchange="this.form.submit()">
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected($selectedYear === (int) $year)>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Allowance</th>
                    <th>Used</th>
                    <th>Annual limit</th>
                    <th>Remaining</th>
                    <th>Usage</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allowances as $allowance)
                    @php
                        $usagePercent = $allowance['annual_limit'] > 0
                            ? min(100, max(0, ($allowance['used'] / $allowance['annual_limit']) * 100))
                            : 0;
                        $isExceeded = $allowance['remaining'] < 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $allowance['name'] }}</strong>
                            @unless ($allowance['catalog_item_exists'])
                                <p class="allowance-note">Catalog item missing. Create it in reusable items.</p>
                            @endunless
                        </td>
                        <td>{{ $formatMoney($allowance['used']) }}</td>
                        <td>{{ $formatMoney($allowance['annual_limit']) }}</td>
                        <td class="{{ $isExceeded ? 'allowance-negative' : '' }}">
                            {{ $formatMoney($allowance['remaining']) }}
                        </td>
                        <td>
                            <div class="allowance-meter">
                                <div class="allowance-meter-fill" style="width: {{ number_format($usagePercent, 2, '.', '') }}%"></div>
                            </div>
                            <small>{{ number_format($usagePercent, 1) }}%</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

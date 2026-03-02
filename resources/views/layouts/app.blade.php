<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Invoices App') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="app-body">
    <div class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Autonomo Toolkit</p>
                <h1>Invoices</h1>
            </div>
            <nav class="nav-links">
                <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'is-active' : '' }}">Historial</a>
                <a href="{{ route('catalog-items.index') }}" class="{{ request()->routeIs('catalog-items.*') ? 'is-active' : '' }}">Items reutilizables</a>
                <a href="{{ route('pb-allowances.index') }}" class="{{ request()->routeIs('pb-allowances.*') ? 'is-active' : '' }}">PB Allowances</a>
                <a href="{{ route('invoices.create') }}" class="cta">Nueva invoice</a>
            </nav>
        </header>

        @if (session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">
                <strong>Hay errores en el formulario:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main>
            @yield('content')
        </main>
    </div>
</body>
</html>

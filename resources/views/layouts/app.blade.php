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
                @auth
                    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'is-active' : '' }}">My invoices</a>
                    <a href="{{ route('catalog-items.index') }}" class="{{ request()->routeIs('catalog-items.*') ? 'is-active' : '' }}">Items reutilizables</a>
                    <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'is-active' : '' }}">Clientes</a>
                    <a href="{{ route('tax-summary.index') }}" class="{{ request()->routeIs('tax-summary.*') ? 'is-active' : '' }}">Resumen Fiscal</a>
                    <a href="{{ route('pb-allowances.index') }}" class="{{ request()->routeIs('pb-allowances.*') ? 'is-active' : '' }}">PB Allowances</a>
                    <a href="{{ route('issuer-profile.edit') }}" class="{{ request()->routeIs('issuer-profile.*') ? 'is-active' : '' }}">Mi perfil</a>
                    <a href="{{ route('invoices.create') }}" class="cta">Nueva invoice</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'is-active' : '' }}">Iniciar sesion</a>
                    <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'is-active' : '' }}">Crear cuenta</a>
                @endauth
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

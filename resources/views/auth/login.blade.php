@extends('layouts.app')

@section('content')
<section class="panel auth-panel">
    <div class="panel-head">
        <h2>Iniciar sesion</h2>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="stack-lg">
        @csrf
        <label>
            Email
            <input type="email" name="email" required value="{{ old('email') }}" />
        </label>

        <label>
            Password
            <input type="password" name="password" required />
        </label>

        <label class="toggle">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) />
            Recordarme
        </label>

        <button class="btn-primary" type="submit">Entrar</button>
        <a href="{{ route('register') }}">Crear nueva cuenta</a>
    </form>
</section>
@endsection

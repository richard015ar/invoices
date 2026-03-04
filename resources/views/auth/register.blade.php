@extends('layouts.app')

@section('content')
<section class="panel auth-panel">
    <div class="panel-head">
        <h2>Crear cuenta</h2>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="stack-lg">
        @csrf
        <label>
            Nombre
            <input name="name" required value="{{ old('name') }}" />
        </label>

        <label>
            Email
            <input type="email" name="email" required value="{{ old('email') }}" />
        </label>

        <label>
            Password
            <input type="password" name="password" required />
        </label>

        <label>
            Confirmar password
            <input type="password" name="password_confirmation" required />
        </label>

        <button class="btn-primary" type="submit">Crear cuenta</button>
        <a href="{{ route('login') }}">Ya tengo cuenta</a>
    </form>
</section>
@endsection

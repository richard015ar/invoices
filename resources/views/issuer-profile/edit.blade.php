@extends('layouts.app')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2>Mi perfil</h2>
    </div>

    <form method="POST" action="{{ route('issuer-profile.update') }}" class="stack-lg">
        @csrf
        @method('PUT')

        <label>
            Nombre
            <input name="name" required value="{{ old('name', $profile->name) }}" />
        </label>

        <label>
            Email
            <input type="email" name="email" value="{{ old('email', $profile->email) }}" />
        </label>

        <label>
            Direccion
            <textarea name="address" rows="3">{{ old('address', $profile->address) }}</textarea>
        </label>

        <label>
            NIE
            <input name="nie" value="{{ old('nie', $profile->nie) }}" />
        </label>

        <label>
            Informacion adicional
            <textarea name="additional_info" rows="3">{{ old('additional_info', $profile->additional_info) }}</textarea>
        </label>

        <button class="btn-primary" type="submit">Guardar perfil</button>
    </form>
</section>
@endsection

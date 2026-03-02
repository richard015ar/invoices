<section class="panel">
    <div class="panel-head">
        <h2>{{ $title }}</h2>
    </div>

    <form action="{{ $action }}" method="POST" class="stack-lg">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        <label>
            Nombre
            <input name="name" required value="{{ old('name', $client->name) }}" />
        </label>

        <label>
            Email
            <input type="email" name="email" value="{{ old('email', $client->email) }}" />
        </label>

        <label>
            Direccion
            <textarea name="address" rows="3">{{ old('address', $client->address) }}</textarea>
        </label>

        <label>
            Detalles (Tax ID, VAT, etc.)
            <textarea name="details" rows="3">{{ old('details', $client->details) }}</textarea>
        </label>

        <label class="toggle">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active)) />
            Activo
        </label>

        <button class="btn-primary" type="submit">Guardar cliente</button>
    </form>
</section>

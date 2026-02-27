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
            <input name="name" required value="{{ old('name', $item->name) }}" />
        </label>

        <label>
            Descripcion
            <textarea name="description" rows="3">{{ old('description', $item->description) }}</textarea>
        </label>

        <div class="grid cols-3">
            <label>
                Precio default
                <input type="number" step="0.01" min="0" name="default_unit_price" required value="{{ old('default_unit_price', $item->default_unit_price) }}" />
            </label>

            <label>
                Impuesto default %
                <input type="number" step="0.01" min="0" max="100" name="default_tax_rate" value="{{ old('default_tax_rate', $item->default_tax_rate) }}" />
            </label>

            <label class="toggle">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) />
                Activo
            </label>
        </div>

        <button class="btn-primary" type="submit">Guardar item</button>
    </form>
</section>

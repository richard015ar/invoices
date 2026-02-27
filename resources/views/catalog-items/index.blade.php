@extends('layouts.app')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2>Items reutilizables</h2>
        <a class="btn-primary" href="{{ route('catalog-items.create') }}">Nuevo item</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Precio default</th>
                    <th>Impuesto default</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ number_format((float) $item->default_unit_price, 2) }}</td>
                        <td>{{ number_format((float) $item->default_tax_rate, 2) }}%</td>
                        <td>{{ $item->is_active ? 'Si' : 'No' }}</td>
                        <td class="actions">
                            <a href="{{ route('catalog-items.edit', $item) }}">Editar</a>
                            <form method="POST" action="{{ route('catalog-items.destroy', $item) }}" onsubmit="return confirm('Eliminar item?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</section>
@endsection

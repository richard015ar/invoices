@extends('layouts.app')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2>Clientes</h2>
        <a class="btn-primary" href="{{ route('clients.create') }}">Nuevo cliente</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->email ?: '-' }}</td>
                        <td>{{ $client->is_active ? 'Si' : 'No' }}</td>
                        <td class="actions">
                            <a href="{{ route('clients.edit', $client) }}">Editar</a>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Eliminar cliente?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No hay clientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $clients->links() }}
</section>
@endsection

@extends('layouts.app')

@section('content')
    @include('clients.partials.form', [
        'title' => 'Editar cliente',
        'action' => route('clients.update', $client),
        'method' => 'PUT',
    ])
@endsection

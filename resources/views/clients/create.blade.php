@extends('layouts.app')

@section('content')
    @include('clients.partials.form', [
        'title' => 'Nuevo cliente',
        'action' => route('clients.store'),
        'method' => 'POST',
    ])
@endsection

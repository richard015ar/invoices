@extends('layouts.app')

@section('content')
    @include('catalog-items.partials.form', [
        'title' => 'Nuevo item',
        'action' => route('catalog-items.store'),
        'method' => 'POST',
    ])
@endsection

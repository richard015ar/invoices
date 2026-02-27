@extends('layouts.app')

@section('content')
    @include('catalog-items.partials.form', [
        'title' => 'Editar item',
        'action' => route('catalog-items.update', $item),
        'method' => 'PUT',
    ])
@endsection

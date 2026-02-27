@extends('layouts.app')

@section('content')
    @include('invoices.partials.form', [
        'title' => 'Nueva invoice',
        'action' => route('invoices.store'),
        'method' => 'POST',
    ])
@endsection

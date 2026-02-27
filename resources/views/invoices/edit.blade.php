@extends('layouts.app')

@section('content')
    @include('invoices.partials.form', [
        'title' => 'Editar invoice ' . $invoice->invoice_number,
        'action' => route('invoices.update', $invoice),
        'method' => 'PUT',
    ])
@endsection

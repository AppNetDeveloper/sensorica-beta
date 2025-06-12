@extends('layouts.admin')

@section('title', __('Create Original Order'))

@section('title', __('Create Original Order'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.original-orders.index', $customer->id) }}">{{ __('Original Orders') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Create') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">{{ __('New Original Order') }} - {{ $customer->name }}</h3>
                    </div>
                </div>
                @php
                    $originalOrder = new \App\Models\OriginalOrder();
                    $selectedProcesses = old('processes', []);
                @endphp
                @include('customers.original-orders.form', [
                    'originalOrder' => $originalOrder,
                    'selectedProcesses' => $selectedProcesses,
                    'customer' => $customer,
                    'processes' => $processes ?? []
                ])
            </div>
        </div>
    </div>
@stop

@push('js')
    <script>
        $(function () {
            // Inicialización de componentes si es necesario
        });
    </script>
@endpush

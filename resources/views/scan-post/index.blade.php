@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Scan Post')

{{-- Migas de pan --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Scan Post') }}</li>
    </ul>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h1>Vista Scan Post</h1>
            <!-- Vista vacía por el momento -->
        </div>
    </div>
@endsection

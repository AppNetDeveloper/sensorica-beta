@extends('layouts.admin')

@section('title', __('Edit Production Line'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productionlines.index', $productionLine->customer_id ?? '') }}">{{ __('Production Lines') }}</a></li>
        <li class="breadcrumb-item">{{ __('Edit Production Line') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('productionlines.update', $productionLine->id ?? '') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $productionLine->name ?? '' }}" required>
                        </div>

                        <div class="form-group">
                            <label for="token">{{ __('Token') }}</label>
                            <input type="text" class="form-control" id="token" name="token" value="{{ $productionLine->token ?? '' }}" required>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('productionlines.index', $productionLine->customer_id ?? '') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

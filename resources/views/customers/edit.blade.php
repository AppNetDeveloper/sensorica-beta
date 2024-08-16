@extends('layouts.admin')
@section('title', __('Edit Customer'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item">{{ __('Edit Customer') }}</li>
    </ul>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ $customer->name }}" required>
                        </div>
                        <div class="form-group">
                            <label for="token_zerotier">{{ __('Token ZeroTier') }}</label>
                            <input type="text" name="token_zerotier" id="token_zerotier" class="form-control" value="{{ $customer->token_zerotier }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

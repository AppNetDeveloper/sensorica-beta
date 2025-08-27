@extends('layouts.admin')

@section('title', __('Edit Maintenance') . ' - ' . $customer->name)
@section('page-title', __('Edit Maintenance'))

@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">{{ __('Edit Maintenance') }}</h5></div>
  <div class="card-body">
    <form method="POST" action="{{ route('customers.maintenances.update', [$customer->id, $maintenance->id]) }}">
      @csrf
      @method('PUT')

      <div class="mb-3">
        <label class="form-label">{{ __('Production Line') }}</label>
        <select class="form-select" name="production_line_id" required>
          @foreach($lines as $line)
            <option value="{{ $line->id }}" {{ $maintenance->production_line_id == $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">{{ __('Start date & time') }}</label>
          <input type="datetime-local" class="form-control" name="start_datetime" value="{{ optional($maintenance->start_datetime)->format('Y-m-d\TH:i') }}" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">{{ __('End date & time') }}</label>
          <input type="datetime-local" class="form-control" name="end_datetime" value="{{ optional($maintenance->end_datetime)->format('Y-m-d\TH:i') }}">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">{{ __('Annotations') }}</label>
        <textarea class="form-control" name="annotations" rows="4">{{ $maintenance->annotations }}</textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">{{ __('Operator (optional)') }}</label>
          <select class="form-select" name="operator_id">
            <option value="">{{ __('Select an operator') }}</option>
            @foreach($operators as $op)
              <option value="{{ $op->id }}" {{ (string)old('operator_id', $maintenance->operator_id) === (string)$op->id ? 'selected' : '' }}>{{ $op->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">{{ __('User (optional)') }}</label>
          <select class="form-select" name="user_id">
            <option value="">{{ __('Select a user') }}</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}" {{ (string)old('user_id', $maintenance->user_id) === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
        <a class="btn btn-secondary" href="{{ route('customers.maintenances.index', $customer->id) }}">{{ __('Cancel') }}</a>
      </div>
    </form>
  </div>
</div>
@endsection

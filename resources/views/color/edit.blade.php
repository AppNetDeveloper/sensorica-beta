@extends('layouts.admin')

@section('title', __('Editar Color RFID'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('rfid.colors.index') }}">{{ __('RFID Colores') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Editar Color RFID') }}</li>
    </ul>
@endsection

@section('content')
   <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card border-0 shadow">
                 <div class="card-header">
                     <h4 class="card-title mb-0">{{ __('Editar Color RFID') }}</h4>
                 </div>
                 <div class="card-body">
                     <form action="{{ route('rfid.colors.update', $color->id) }}" method="POST">
                         @csrf
                         @method('PUT')
                         <div class="mb-3">
                              <label for="name" class="form-label">{{ __('Nombre') }}</label>
                              <input type="text" name="name" id="name" class="form-control" value="{{ $color->name }}" required>
                         </div>
                         <button type="submit" class="btn btn-primary">{{ __('Actualizar') }}</button>
                     </form>
                 </div>
            </div>
        </div>
   </div>
@endsection

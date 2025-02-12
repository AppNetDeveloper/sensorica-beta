@extends('layouts.admin')

@section('title', __('Añadir Nuevo Color RFID'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('rfid.colors.index', ['production_line_id' => $production_line_id]) }}">{{ __('RFID Colores') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Añadir Nuevo Color RFID') }}</li>
    </ul>
@endsection

@section('content')
   <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card border-0 shadow">
                 <div class="card-header">
                     <h4 class="card-title mb-0">{{ __('Añadir Nuevo Color RFID') }}</h4>
                 </div>
                 <div class="card-body">
                     <form action="{{ route('rfid.colors.store', ['production_line_id' => $production_line_id]) }}" method="POST">
                         @csrf
                         <div class="mb-3">
                              <label for="name" class="form-label">{{ __('Nombre') }}</label>
                              <input type="text" name="name" id="name" class="form-control" required>
                         </div>
                         <button type="submit" class="btn btn-primary">{{ __('Guardar') }}</button>
                     </form>
                 </div>
            </div>
        </div>
   </div>
@endsection

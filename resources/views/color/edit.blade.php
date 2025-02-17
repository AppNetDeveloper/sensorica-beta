@extends('layouts.admin')

@section('title', __('Editar Color RFID'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('rfid.colors.index', ['production_line_id' => $production_line_id]) }}">
                {{ __('RFID Colores') }}
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            {{ __('Editar Color RFID') }}
        </li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow">
            {{-- Cabecera --}}
            <div class="card-header">
                <h4 class="card-title mb-0">
                    {{ __('Editar Color RFID') }}
                </h4>
            </div>

            {{-- Cuerpo del formulario --}}
            <div class="card-body">
                {{-- Mostrar errores de validación --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Formulario para editar --}}
                <form action="{{ route('rfid.colors.update', ['production_line_id' => $production_line_id, 'rfidColor' => $color->id]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Campo Nombre --}}
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Nombre del Color') }}</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name', $color->name) }}" required>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('rfid.colors.index', ['production_line_id' => $production_line_id]) }}" 
                           class="btn btn-secondary">
                            {{ __('Cancelar') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            {{ __('Actualizar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

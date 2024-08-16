@extends('layouts.admin')
@section('title', __('Crear Nueva Línea de Producción'))
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Agregar Nueva Línea de Producción</h4>
                    <form action="{{ route('productionlines.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="name">Nombre de la Línea:</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_id">ID del Cliente:</label>
                            <input type="text" name="customer_id" id="customer_id" class="form-control" value="{{ request()->route('customer_id') }}" readonly>
                            <!-- Puedes cambiar el tipo de input según sea necesario -->
                        </div>
                        <!-- Otros campos que puedas necesitar -->
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

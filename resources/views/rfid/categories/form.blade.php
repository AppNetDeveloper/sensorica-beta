@extends('layouts.admin')
@section('title', $rfidReading->exists ? __('Editar Categoría RFID') : __('Añadir Nueva Categoría RFID'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ $rfidReading->exists ? __('Editar Categoría RFID') : __('Añadir Nueva Categoría RFID') }}</h1>

        <form action="{{ $rfidReading->exists ? route('rfid.categories.update', $rfidReading->id) : route('rfid.categories.store') }}" method="POST">
            @csrf
            @if ($rfidReading->exists)
                @method('PUT')
            @endif

            <div class="form-group">
                <label for="name">{{ __('Nombre') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $rfidReading->name) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="epc">{{ __('EPC') }}</label>
                <input type="text" name="epc" id="epc" class="form-control" value="{{ old('epc', $rfidReading->epc) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="production_line_id">{{ __('Línea de Producción') }}</label>
                <select name="production_line_id" id="production_line_id" class="form-control" required>
                    <option value="">{{ __('Seleccione una línea de producción') }}</option>
                    @foreach($productionLines as $line)
                        <option value="{{ $line->id }}" {{ old('production_line_id', $rfidReading->production_line_id) == $line->id ? 'selected' : '' }}>
                            {{ $line->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mt-3">
                <label for="rfid_color_id">{{ __('Color RFID') }}</label>
                <select name="rfid_color_id" id="rfid_color_id" class="form-control">
                    <option value="">{{ __('Seleccione un color') }}</option>
                    @foreach($rfidColors as $color)
                        <option value="{{ $color->id }}" {{ old('rfid_color_id', $rfidReading->rfid_color_id) == $color->id ? 'selected' : '' }}>
                            {{ $color->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-success mt-4">
                {{ $rfidReading->exists ? __('Actualizar') : __('Guardar') }}
            </button>
        </form>
    </div>
@endsection

@extends('layouts.admin')

@section('title', __('Shift Lists'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Shift Lists') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Mensajes de éxito --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- Tabla con la lista de turnos --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title">{{ __('Shift Lists') }}</h4>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createShiftModal">
                        {{ __('Create Shift') }}
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ __('Production Line Name') }}</th>
                                <th>{{ __('Start Time') }}</th>
                                <th>{{ __('End Time') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shiftLists as $shift)
                                <tr>
                                    <td>{{ $shift->id }}</td>
                                    <td>{{ $shift->productionLine->name }}</td>
                                    <td>{{ $shift->start }}</td>
                                    <td>{{ $shift->end }}</td>
                                    <td>
                                        {{-- Botón para editar --}}
                                        <button class="btn btn-primary btn-sm edit-shift" data-id="{{ $shift->id }}"
                                            data-production-line-id="{{ $shift->production_line_id }}"
                                            data-start="{{ $shift->start }}"
                                            data-end="{{ $shift->end }}" data-bs-toggle="modal"
                                            data-bs-target="#editShiftModal">{{ __('Edit') }}</button>
                                        {{-- Botón para eliminar --}}
                                        <form action="{{ route('shift.destroy', $shift->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('Are you sure?') }}')">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para crear un turno --}}
    <div class="modal fade" id="createShiftModal" tabindex="-1" aria-labelledby="createShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createShiftModalLabel">{{ __('Create Shift') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('shift.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="createProductionLineId" class="form-label">{{ __('Production Line') }}</label>
                            <select class="form-select" name="production_line_id" id="createProductionLineId" required>
                                <option value="" disabled selected>{{ __('Select a Production Line') }}</option>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="createStartTime" class="form-label">{{ __('Start Time') }}</label>
                            <input type="time" class="form-control" name="start" id="createStartTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="createEndTime" class="form-label">{{ __('End Time') }}</label>
                            <input type="time" class="form-control" name="end" id="createEndTime" required>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para editar un turno --}}
    <div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editShiftModalLabel">{{ __('Edit Shift') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editShiftForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editShiftId" name="id">
                        <div class="mb-3">
                            <label for="editProductionLineId" class="form-label">{{ __('Production Line') }}</label>
                            <select class="form-select" id="editProductionLineId" name="production_line_id" required>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editStartTime" class="form-label">{{ __('Start Time') }}</label>
                            <input type="time" class="form-control" id="editStartTime" name="start" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEndTime" class="form-label">{{ __('End Time') }}</label>
                            <input type="time" class="form-control" id="editEndTime" name="end" required>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Script para rellenar el modal de edición --}}
    <script>
        document.querySelectorAll('.edit-shift').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const productionLineId = button.dataset.productionLineId;
                const start = button.dataset.start;
                const end = button.dataset.end;

                const form = document.getElementById('editShiftForm');
                form.action = `/shift-lists/${id}`;
                document.getElementById('editShiftId').value = id;
                document.getElementById('editProductionLineId').value = productionLineId;
                document.getElementById('editStartTime').value = start.slice(0, 5);
                document.getElementById('editEndTime').value = end.slice(0, 5);
            });
        });
    </script>
@endsection

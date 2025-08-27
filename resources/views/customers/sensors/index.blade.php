@extends('layouts.admin')

@section('title', __('Sensors'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $customer->name }} - {{ __('Sensors') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fas fa-microchip me-2"></i>{{ __('Sensors') }} — {{ $customer->name }}
                </h4>
                <div>
                    <a href="{{ route('productionlines.index', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-sitemap"></i> {{ __('Production Lines') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Production Line') }}</label>
                        <select id="lineFilter" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}">{{ $line->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
                    <table class="table table-striped align-middle" id="sensorsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Production Line') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Created at') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sensors as $sensor)
                                <tr data-line-id="{{ $sensor->production_line_id }}">
                                    <td>{{ $sensor->id }}</td>
                                    <td>{{ $sensor->name }}</td>
                                    <td>{{ optional($sensor->productionLine)->name }}</td>
                                    <td>
                                        @switch($sensor->sensor_type)
                                            @case(0) <span class="badge bg-primary">Actividad Maquina</span> @break
                                            @case(1) <span class="badge bg-info text-dark">Materia Prima</span> @break
                                            @case(2) <span class="badge bg-warning text-dark">Raw</span> @break
                                            @case(3) <span class="badge bg-danger">Incident</span> @break
                                            @default <span class="badge bg-secondary">N/A</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $sensor->created_at }}</td>
                                    <td>
                                        <a href="{{ url('/smartsensors/' . $sensor->id . '/live') }}" class="btn btn-sm btn-outline-primary" title="{{ __('Live') }}">
                                            <i class="fas fa-broadcast-tower"></i> {{ __('Live') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ __('No sensors found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('lineFilter');
        const rows = Array.from(document.querySelectorAll('#sensorsTable tbody tr'));
        select.addEventListener('change', function () {
            const val = this.value;
            rows.forEach(tr => {
                const match = !val || tr.getAttribute('data-line-id') === val;
                tr.style.display = match ? '' : 'none';
            });
        });
    });
</script>
@endpush

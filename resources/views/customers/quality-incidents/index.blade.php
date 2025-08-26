@extends('layouts.admin')

@section('title', __('Incidencias QC'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Incidencias QC') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-danger text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('Incidencias QC') - {{ $customer->name }}</h5>
                        <a href="{{ route('customers.order-organizer', $customer->id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-th"></i> @lang('Order Organizer')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <!-- Filtros -->
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha desde')</label>
                            <input type="date" class="form-control form-control-sm" id="filter-date-from">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha hasta')</label>
                            <input type="date" class="form-control form-control-sm" id="filter-date-to">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Línea de producción')</label>
                            <select id="filter-line" class="form-select form-select-sm">
                                <option value="">@lang('Todas')</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name ?? ('#'.$line->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Operador')</label>
                            <select id="filter-operator" class="form-select form-select-sm">
                                <option value="">@lang('Todos')</option>
                                @foreach($operators as $op)
                                    <option value="{{ $op->id }}">{{ $op->name ?? ('#'.$op->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="qc-incidents-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('Original Order')</th>
                                    <th class="text-uppercase">@lang('Original Order QC')</th>
                                    <th class="text-uppercase">@lang('Process')</th>
                                    <th class="text-uppercase">@lang('Info')</th>
                                    <th class="text-uppercase">@lang('Reason')</th>
                                    <th class="text-uppercase">@lang('Created At')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incidents as $index => $issue)
                                    @php
                                        $status = optional($issue->productionOrder)->status;
                                        $rowClass = '';
                                        if ($status === 3) { // incidencias
                                            $rowClass = 'table-danger';
                                        } elseif ($status === 2) { // finalizada
                                            $rowClass = 'table-success';
                                        } elseif ($status === 1) { // en curso
                                            $rowClass = 'table-warning';
                                        } elseif ($status === 0) { // pendiente
                                            $rowClass = 'table-light';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}" data-line-id="{{ $issue->production_line_id ?? optional($issue->productionOrder)->production_line_id }}"
                                        data-operator-id="{{ $issue->operator_id ?? '' }}"
                                        data-created-at="{{ optional($issue->created_at)->format('Y-m-d') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($issue->originalOrder)
                                                <a href="{{ route('customers.original-orders.show', ['customer' => $customer->id, 'originalOrder' => $issue->originalOrder->id]) }}" class="text-decoration-none">
                                                    #{{ $issue->originalOrder->order_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($issue->originalOrderQc)
                                                <a href="{{ route('customers.original-orders.show', ['customer' => $customer->id, 'originalOrder' => $issue->originalOrderQc->id]) }}" class="text-decoration-none">
                                                    #{{ $issue->originalOrderQc->order_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($issue->productionOrder)
                                                #{{ $issue->productionOrder->order_id }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($issue->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $issue->productionLine->name ?? ('L#'.$issue->productionLine->id) }}</span>
                                            @elseif(optional($issue->productionOrder)->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $issue->productionOrder->productionLine->name ?? ('L#'.$issue->productionOrder->productionLine->id) }}</span>
                                            @endif
                                            @if($issue->operator)
                                                <span class="badge bg-secondary" title="@lang('Operador')"><i class="fas fa-user"></i> {{ $issue->operator->name }}</span>
                                            @elseif($issue->operator_id)
                                                <span class="badge bg-secondary" title="@lang('Operador')"><i class="fas fa-user"></i> #{{ $issue->operator_id }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($issue->texto, 80) }}</td>
                                        <td>{{ optional($issue->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function() {
            // Custom filter by date range, line, and operator
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
                const tr = $(settings.aoData[dataIndex].nTr);
                const lineFilter = $('#filter-line').val();
                const operatorFilter = $('#filter-operator').val();
                const dateFrom = $('#filter-date-from').val();
                const dateTo = $('#filter-date-to').val();

                const lineId = (tr.data('line-id') || '').toString();
                const operatorId = (tr.data('operator-id') || '').toString();
                const createdAt = (tr.data('created-at') || '').toString(); // 'YYYY-MM-DD'

                // Line filter
                if (lineFilter && lineId !== lineFilter) return false;
                // Operator filter
                if (operatorFilter && operatorId !== operatorFilter) return false;
                // Date range filter
                if (dateFrom && createdAt < dateFrom) return false;
                if (dateTo && createdAt > dateTo) return false;
                return true;
            });

            const table = $('#qc-incidents-table').DataTable({
                responsive: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[6, 'desc']],
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'print', text: 'Imprimir', className: 'btn btn-sm btn-outline-primary' },
                ]
            });

            // Re-draw on filter changes
            $('#filter-line, #filter-operator, #filter-date-from, #filter-date-to').on('change keyup', function() {
                table.draw();
            });
        });
    </script>
@endpush

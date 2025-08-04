@extends('layouts.admin')

@section('title', __('Sensor History'))

@section('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .card-dashboard {
            transition: all 0.3s;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('smartsensors.index', $sensor->production_line_id) }}">{{ __('Smart Sensors') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('smartsensors.live-view', $sensor->id) }}">{{ __('Live View') }} - {{ $sensor->name }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('History') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mb-4">
    <!-- Estadísticas resumen -->
    <div class="col-md-3">
        <div class="card card-dashboard bg-primary text-white">
            <div class="card-body text-center">
                <h6 class="stat-label">{{ __('Total Production') }}</h6>
                <div class="stat-value">{{ $stats['total_production'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard bg-success text-white">
            <div class="card-body text-center">
                <h6 class="stat-label">{{ __('Average Production') }}</h6>
                <div class="stat-value">{{ $stats['avg_production'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard bg-warning text-white">
            <div class="card-body text-center">
                <h6 class="stat-label">{{ __('Total Downtime') }}</h6>
                <div class="stat-value">{{ $stats['total_downtime'] }}s</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard bg-danger text-white">
            <div class="card-body text-center">
                <h6 class="stat-label">{{ __('Efficiency') }}</h6>
                <div class="stat-value">{{ round($stats['efficiency'], 1) }}%</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Gráficos -->
    <div class="col-md-6">
        <div class="card card-dashboard">
            <div class="card-header">
                <h5 class="card-title">{{ __('Production Trend') }}</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="productionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-dashboard">
            <div class="card-header">
                <h5 class="card-title">{{ __('Downtime Analysis') }}</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="downtimeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card card-dashboard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">{{ __('Sensor History for') }} {{ $sensor->name }}</h4>
                <div>
                    <button id="refreshData" class="btn btn-sm btn-primary">
                        <i class="fas fa-sync-alt"></i> {{ __('Refresh') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                    <table id="historyTable" class="display table table-striped table-bordered" style="width:100%">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">{{ __('Date') }}</th>
                                <th class="border-0">{{ __('Order Code') }}</th>
                                <th class="border-0">{{ __('Order ID') }}</th>
                                <th class="border-0">{{ __('Count Order 1') }}</th>
                                <th class="border-0">{{ __('Count Order 0') }}</th>
                                <th class="border-0">{{ __('Downtime') }}</th>
                                <th class="border-0 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $record)
                            <tr>
                                <td>{{ $record->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $record->unic_code_order }}</td>
                                <td>{{ $record->orderId }}</td>
                                <td>{{ $record->count_order_1 }}</td>
                                <td>{{ $record->count_order_0 }}</td>
                                <td>{{ $record->downtime_count }}s</td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-info view-details" 
                                            data-date="{{ $record->created_at->format('d/m/Y H:i:s') }}"
                                            data-code="{{ $record->unic_code_order }}"
                                            data-order="{{ $record->orderId }}"
                                            data-shift1="{{ $record->count_shift_1 }}"
                                            data-shift0="{{ $record->count_shift_0 }}"
                                            data-order1="{{ $record->count_order_1 }}"
                                            data-order0="{{ $record->count_order_0 }}"
                                            data-downtime="{{ $record->downtime_count }}">
                                        <i class="fas fa-eye"></i> {{ __('Live View') }}
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación manejada por DataTables -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">{{ __('Record Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ __('Date') }}
                                <span id="modal-date" class="badge bg-primary rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ __('Order Code') }}
                                <span id="modal-code" class="badge bg-secondary rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ __('Order ID') }}
                                <span id="modal-order" class="badge bg-info rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ __('Count Order 1') }}
                                <span id="modal-count1" class="badge bg-success rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ __('Count Order 0') }}
                                <span id="modal-count0" class="badge bg-warning rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ __('Downtime') }}
                                <span id="modal-downtime" class="badge bg-danger rounded-pill"></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="recordDetailChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
    $(document).ready(function() {
        console.log('jQuery ready. Initializing components...');
        
        // Verificar si jQuery está disponible
        if (typeof $ === 'undefined') {
            console.error('jQuery no está disponible');
            return;
        }

        try {
            // 1. Initialize DataTables
            const dataTable = $('#historyTable').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                // This callback runs once the table is fully initialized
                initComplete: function(settings, json) {
                    console.log('DataTables initComplete: Table initialized.');
                    // Mejorar estilo de los elementos de DataTables
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                    $('.dt-buttons .btn').addClass('btn-sm');
                    
                    // 2. Initialize main charts after DataTables is ready
                    setTimeout(function() {
                        initializeCharts();
                    }, 500); // Pequeño retraso para asegurar que todo esté listo
                }
            });
            
            console.log('DataTable initialized successfully');
        } catch (error) {
            console.error('Error en la inicialización de DataTable:', error);
        }

        try {
            // 3. Set up event handler for the view details button - usando click directo
            $('.view-details').click(function(e) {
                e.preventDefault();
                console.log('View details button clicked');
                
                const button = $(this);
                const recordData = {
                    date: button.data('date'),
                    code: button.data('code'),
                    order: button.data('order'),
                    shift1: button.data('shift1'),
                    shift0: button.data('shift0'),
                    order1: button.data('order1'),
                    order0: button.data('order0'),
                    downtime: button.data('downtime')
                };
                
                console.log('Record data:', recordData);
                showDetailsModal(recordData);
            });
            console.log('Event handlers set up successfully');
        } catch (error) {
            console.error('Error setting up event handlers:', error);
        }

        // 4. Set up refresh button
        $('#refreshData').click(function() {
            location.reload();
        });

        function showDetailsModal(data) {
            try {
                // Update modal content
                $('#modal-date').text(data.date);
                $('#modal-code').text(data.code);
                $('#modal-order').text(data.order);
                $('#modal-count1').text(data.order1);
                $('#modal-count0').text(data.order0);
                $('#modal-downtime').text(data.downtime + 's');
                
                // Update and display the modal's chart
                updateModalChart(data);
                
                // Show the modal using Bootstrap 5 API
                const modalElement = document.getElementById('detailsModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else {
                    console.error('Modal element #detailsModal not found.');
                }
            } catch (error) {
                console.error('Error showing modal:', error);
            }
        }

        function initializeCharts() {
            try {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not available to initialize main charts.');
                    return;
                }
                console.log('Initializing main charts...');
                
                // Data is reversed in the controller for efficiency
                const dates = @json($allHistory->pluck('created_at')->map(function($date) { return $date->format('d/m/Y H:i'); })->reverse()->values());
                const countOrder1 = @json($allHistory->pluck('count_order_1')->reverse()->values());
                const countOrder0 = @json($allHistory->pluck('count_order_0')->reverse()->values());
                const downtimes = @json($allHistory->pluck('downtime_count')->reverse()->values());

                // Production Chart
                const productionCtx = document.getElementById('productionChart');
                if (productionCtx) {
                    new Chart(productionCtx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [
                                { 
                                    label: 'Count Order 1', 
                                    data: countOrder1, 
                                    borderColor: 'rgba(75, 192, 192, 1)', 
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    tension: 0.1,
                                    fill: true
                                },
                                { 
                                    label: 'Count Order 0', 
                                    data: countOrder0, 
                                    borderColor: 'rgba(153, 102, 255, 1)', 
                                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                                    tension: 0.1,
                                    fill: true
                                }
                            ]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false, 
                            plugins: { 
                                title: { 
                                    display: true, 
                                    text: 'Production Trend' 
                                },
                                legend: {
                                    position: 'top',
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Downtime Chart
                const downtimeCtx = document.getElementById('downtimeChart');
                if (downtimeCtx) {
                    new Chart(downtimeCtx, {
                        type: 'bar',
                        data: { 
                            labels: dates, 
                            datasets: [{ 
                                label: 'Downtime (seconds)', 
                                data: downtimes, 
                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            }] 
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false, 
                            plugins: { 
                                title: { 
                                    display: true, 
                                    text: 'Downtime Analysis' 
                                } 
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
                console.log('Main charts initialized.');
            } catch (error) {
                console.error('Error initializing charts:', error);
            }
        }

        function updateModalChart(data) {
            try {
                const ctx = document.getElementById('recordDetailChart');
                if (!ctx || typeof Chart === 'undefined') {
                    console.error('Modal chart canvas or Chart.js is not available.');
                    return;
                }

                // Destroy previous chart instance if exists
                if (window.recordDetailChart instanceof Chart) {
                    window.recordDetailChart.destroy();
                }

                // Create new chart
                window.recordDetailChart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: ['Count Order 1', 'Count Order 0', 'Downtime (s)', 'Shift 1', 'Shift 0'],
                        datasets: [{
                            label: 'Record Values',
                            data: [data.order1, data.order0, data.downtime, data.shift1, data.shift0],
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { 
                            r: { 
                                beginAtZero: true 
                            } 
                        }
                    }
                });
                console.log('Modal chart created/updated.');
            } catch (error) {
                console.error('Error updating modal chart:', error);
            }
        }
    });
</script>
@endpush

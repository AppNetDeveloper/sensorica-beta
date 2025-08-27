@extends('layouts.admin')

@section('title', __('Sensor Live View'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('smartsensors.index', $sensor->production_line_id) }}">{{ __('Smart Sensors') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Live View') }} - {{ $sensor->name }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">{{ __('Live View') }} - {{ $sensor->name }}</h4>
                <div>
                    <span class="badge bg-primary me-2" id="connection-status">Conectando...</span>
                    <a href="{{ route('smartsensors.history', $sensor->id) }}" class="btn btn-info mr-2">{{ __('View History') }}</a>
                    <a href="javascript:history.back()" class="btn btn-secondary">{{ __('Back') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Panel de estadísticas principales -->
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-tachometer-alt me-2"></i>{{ __('Estadísticas Principales') }}
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Total') }}</h6>
                                            <h3 id="count_total" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Tiempo Inactivo') }}</h6>
                                            <h3 id="downtime_count" class="mb-0 fw-bold">0s</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Estado 0') }}</h6>
                                            <h3 id="count_total_0" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Estado 1') }}</h6>
                                            <h3 id="count_total_1" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Tiempo Óptimo') }}</h6>
                                            <h3 id="optimal_production_time" class="mb-0 fw-bold">0s</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Multiplicador') }}</h6>
                                            <h3 id="reduced_speed_time_multiplier" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel de gráficos -->
                    <div class="col-md-8">
                        <div class="card bg-light mb-3">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-chart-line me-2"></i>{{ __('Gráficos en Tiempo Real') }}
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="chartTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="count-tab" data-bs-toggle="tab" data-bs-target="#count-chart" type="button" role="tab" aria-controls="count-chart" aria-selected="true">Conteo</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="downtime-tab" data-bs-toggle="tab" data-bs-target="#downtime-chart" type="button" role="tab" aria-controls="downtime-chart" aria-selected="false">Tiempo Inactivo</button>
                                    </li>
                                </ul>
                                <div class="tab-content mt-3" id="chartTabsContent">
                                    <div class="tab-pane fade show active" id="count-chart" role="tabpanel" aria-labelledby="count-tab">
                                        <div class="chart-container" style="position: relative; height:300px;">
                                            <canvas id="countChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="downtime-chart" role="tabpanel" aria-labelledby="downtime-tab">
                                        <div class="chart-container" style="position: relative; height:300px;">
                                            <canvas id="downtimeChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de detalles adicionales -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-info-circle me-2"></i>{{ __('Detalles Adicionales') }}
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Turno 0') }}</h6>
                                            <h3 id="count_shift_0" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Turno 1') }}</h6>
                                            <h3 id="count_shift_1" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Pedido 0') }}</h6>
                                            <h3 id="count_order_0" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Pedido 1') }}</h6>
                                            <h3 id="count_order_1" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Semana 0') }}</h6>
                                            <h3 id="count_week_0" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Conteo Semana 1') }}</h6>
                                            <h3 id="count_week_1" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('ID de Orden') }}</h6>
                                            <h3 id="orderId" class="mb-0 fw-bold">-</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card mb-3 p-3 bg-white rounded shadow-sm">
                                            <h6 class="text-muted">{{ __('Cantidad') }}</h6>
                                            <h3 id="quantity" class="mb-0 fw-bold">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .stat-card {
        transition: all 0.3s ease;
        border-left: 4px solid #4e73df;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .card-header {
        font-weight: 600;
    }
    
    .chart-container {
        margin-top: 1rem;
    }
    
    #connection-status {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
    
    .bg-primary {
        background-color: #4e73df !important;
    }
    
    .text-white {
        color: #fff !important;
    }
    
    .updated {
        animation: highlight 1s ease-in-out;
    }
    
    @keyframes highlight {
        0% { background-color: rgba(78, 115, 223, 0.1); }
        100% { background-color: transparent; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Variables globales
    const sensorToken = "{{ $sensor->token }}";
    let countChart, downtimeChart;
    let countData = {
        labels: [],
        datasets: [
            {
                label: 'Conteo Total',
                data: [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Conteo Estado 0',
                data: [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Conteo Estado 1',
                data: [],
                borderColor: '#f6c23e',
                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    };
    
    let downtimeData = {
        labels: [],
        datasets: [{
            label: 'Tiempo Inactivo (s)',
            data: [],
            borderColor: '#e74a3b',
            backgroundColor: 'rgba(231, 74, 59, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    
    // Configuración de gráficos
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 500
        },
        scales: {
            x: {
                ticks: {
                    maxRotation: 0,
                    maxTicksLimit: 10
                }
            },
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: 'top',
            }
        }
    };
    
    // Inicializar gráficos
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar gráfico de conteo
        const countCtx = document.getElementById('countChart').getContext('2d');
        countChart = new Chart(countCtx, {
            type: 'line',
            data: countData,
            options: chartOptions
        });
        
        // Inicializar gráfico de tiempo inactivo
        const downtimeCtx = document.getElementById('downtimeChart').getContext('2d');
        downtimeChart = new Chart(downtimeCtx, {
            type: 'line',
            data: downtimeData,
            options: chartOptions
        });
        
        // Iniciar actualización de datos
        fetchSensorData();
        setInterval(fetchSensorData, 1000);
    });
    
    // Función para actualizar un elemento con animación
    function updateElementWithAnimation(id, value) {
        const element = document.getElementById(id);
        const currentValue = element.textContent;
        
        if (currentValue !== String(value)) {
            element.textContent = value;
            element.classList.add('updated');
            setTimeout(() => {
                element.classList.remove('updated');
            }, 1000);
        }
    }
    
    // Función para obtener datos del sensor
    async function fetchSensorData() {
        try {
            const response = await fetch(`/api/sensors/${sensorToken}`);
            if (!response.ok) {
                throw new Error('Error al obtener datos del sensor');
            }
            
            const data = await response.json();
            updateDashboard(data);
            updateConnectionStatus(true);
        } catch (error) {
            console.error(error);
            updateConnectionStatus(false);
        }
    }
    
    // Actualizar estado de conexión
    function updateConnectionStatus(isConnected) {
        const statusElement = document.getElementById('connection-status');
        if (isConnected) {
            statusElement.textContent = 'Conectado';
            statusElement.classList.remove('bg-danger');
            statusElement.classList.add('bg-success');
        } else {
            statusElement.textContent = 'Desconectado';
            statusElement.classList.remove('bg-success');
            statusElement.classList.add('bg-danger');
        }
    }
    
    // Actualizar dashboard con nuevos datos
    function updateDashboard(data) {
        // Actualizar estadísticas principales
        updateElementWithAnimation('count_total', data.count_total || 0);
        updateElementWithAnimation('downtime_count', (data.downtime_count || 0) + 's');
        updateElementWithAnimation('count_total_0', data.count_total_0 || 0);
        updateElementWithAnimation('count_total_1', data.count_total_1 || 0);
        updateElementWithAnimation('optimal_production_time', (data.optimal_production_time || 0) + 's');
        updateElementWithAnimation('reduced_speed_time_multiplier', data.reduced_speed_time_multiplier || 0);
        
        // Actualizar estadísticas adicionales
        updateElementWithAnimation('count_shift_0', data.count_shift_0 || 0);
        updateElementWithAnimation('count_shift_1', data.count_shift_1 || 0);
        updateElementWithAnimation('count_order_0', data.count_order_0 || 0);
        updateElementWithAnimation('count_order_1', data.count_order_1 || 0);
        updateElementWithAnimation('count_week_0', data.count_week_0 || 0);
        updateElementWithAnimation('count_week_1', data.count_week_1 || 0);
        updateElementWithAnimation('orderId', data.orderId || '-');
        updateElementWithAnimation('quantity', data.quantity || 0);
        
        // Actualizar gráficos
        updateCharts(data);
    }
    
    // Actualizar gráficos con nuevos datos
    function updateCharts(data) {
        const now = new Date();
        const timeLabel = now.getHours().toString().padStart(2, '0') + ':' + 
                         now.getMinutes().toString().padStart(2, '0') + ':' + 
                         now.getSeconds().toString().padStart(2, '0');
        
        // Actualizar gráfico de conteo
        if (countData.labels.length > 20) {
            countData.labels.shift();
            countData.datasets[0].data.shift();
            countData.datasets[1].data.shift();
            countData.datasets[2].data.shift();
        }
        
        countData.labels.push(timeLabel);
        countData.datasets[0].data.push(data.count_total || 0);
        countData.datasets[1].data.push(data.count_total_0 || 0);
        countData.datasets[2].data.push(data.count_total_1 || 0);
        countChart.update();
        
        // Actualizar gráfico de tiempo inactivo
        if (downtimeData.labels.length > 20) {
            downtimeData.labels.shift();
            downtimeData.datasets[0].data.shift();
        }
        
        downtimeData.labels.push(timeLabel);
        downtimeData.datasets[0].data.push(data.downtime_count || 0);
        downtimeChart.update();
    }
</script>
@endpush

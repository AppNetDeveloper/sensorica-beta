@extends('layouts.admin')

@section('title')
    {{ __('Monitor de Servidores') }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <!-- Grid de hosts -->
        <div class="row">
            @foreach($hosts as $host)
                @php
                    $latest = $host->hostMonitors->first();
                    // Se considera OFFLINE si no hay registro o si el último registro es anterior a 3 minutos.
                    $offline = !$latest || $latest->created_at->diffInMinutes(now()) >= 3;
                    $cpu = $latest ? number_format($latest->cpu, 1) : 0;
                    $ram = $latest ? number_format($latest->memory_used_percent, 1) : 0;
                    $disk = $latest ? number_format($latest->disk, 1) : 0;
                @endphp

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <!-- Encabezado: Nombre y estado -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <!-- Convierto el nombre en un botón para cambiar la gráfica -->
                                <div class="bg-light p-3 rounded">
                                    <button class="btn btn-link p-0 m-0 text-start {{ $offline ? 'text-danger' : 'text-body' }} host-btn"
                                            data-host-id="{{ $host->id }}"
                                            style="font-size: 1.1rem; font-weight: 600;">
                                            <i class="ti ti-device-desktop-analytics d-block mb-1 text-error" style="font-size: 24px;">{{ $host->name }}</i>
                                    </button>
                                </div>
                                @if($offline)
                                    <span class="badge bg-danger">{{ __('OFFLINE') }}</span>
                                @endif
                                <!-- Botones de Editar/Borrar -->
                            <div class="mt-auto d-flex justify-content-end gap-2">
                                @canany(['servermonitor update','servermonitorbusynes update'])
                                    <a href="{{ route('hosts.edit', $host->id) }}"
                                       class="btn btn-sm btn-outline-primary" title="{{ __('Editar') }}">
                                       <i class="ti ti-pencil d-block text-error" style="font-size: 24px;"></i>
                                    </a>
                                @endcanany

                                @canany(['servermonitor delete','servermonitorbusynes delete'])
                                    <form action="{{ route('hosts.destroy', $host->id) }}" method="POST" onsubmit="return false;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="{{ __('Borrar') }}">
                                            <i class="ti ti-trash d-block text-primary" style="font-size: 24px;"></i>
                                        </button>
                                    </form>
                                @endcanany
                            </div>
                            </div>

                            <!-- Métricas: CPU, RAM, Disco -->
                            <div class="row text-center">
                                <div class="col-4 mb-3">
                                    <div class="bg-light p-3 rounded">
                                        <i class="ti ti-power d-block mb-1 text-primary" style="font-size: 24px;"></i>
                                        <span class="text-muted d-block">{{ __('CPU') }}</span>
                                        <strong id="cpu-{{ $host->id }}">{{ $cpu }}%</strong>
                                    </div>
                                </div>
                                <div class="col-4 mb-3">
                                    <div class="bg-light p-3 rounded">
                                        <i class="ti ti-server d-block mb-1 text-warning" style="font-size: 24px;"></i>
                                        <span class="text-muted d-block">{{ __('RAM') }}</span>
                                        <strong id="ram-{{ $host->id }}">{{ $ram }}%</strong>
                                    </div>
                                </div>
                                <div class="col-4 mb-3">
                                    <div class="bg-light p-3 rounded">
                                        <i class="ti ti-folder d-block mb-1 text-info" style="font-size: 24px;"></i>
                                        <span class="text-muted d-block">{{ __('Disco') }}</span>
                                        <strong id="disk-{{ $host->id }}">{{ $disk }}%</strong>
                                    </div>
                                </div>
                            </div>

                            
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sección de Monitoreo en Vivo -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Monitoreo En Vivo') }}</h5>
            </div>
            <div class="card-body">
                <div id="liveChart" style="height: 350px;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <!-- Tabler Icons (o la librería que uses para 'ti ti-...') -->
    <link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@latest/tabler-icons.min.css">
@endpush

@section('javascript')
    <!-- SweetAlert2 (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // URL base para concatenar rutas
        const appUrl = "{{ rtrim(config('app.url'), '/') }}";

        // Variables de la gráfica
        let currentHostId = null;
        let chartInstance = null;
        let pollInterval = null;

        let chartCategories = [];
        let seriesCPU = [];
        let seriesRAM = [];
        let seriesDisk = [];

        let fullHistoricalData = [];
        let isExpanded = false;
        const MAX_RECORDS = 60;

        // Crear la gráfica
        function createChart() {
            const options = {
                chart: {
                    type: 'line',
                    height: 350,
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: { speed: 500 }
                    }
                },
                series: [
                    { name: 'CPU (%)', data: seriesCPU, color: '#000000' },
                    { name: 'RAM (%)', data: seriesRAM, color: '#F97316' },
                    { name: 'Disco (%)', data: seriesDisk, color: '#FACC15' }
                ],
                stroke: { curve: 'smooth' },
                xaxis: { categories: chartCategories, title: { text: 'Hora' } },
                yaxis: { max: 100, title: { text: 'Porcentaje' } },
                legend: { position: 'bottom' }
            };
            chartInstance = new ApexCharts(document.querySelector("#liveChart"), options);
            chartInstance.render();
        }

        // Actualizar la gráfica con los datos actuales
        function updateChart() {
            if (!chartInstance) return;
            chartInstance.updateOptions({ xaxis: { categories: chartCategories } });
            chartInstance.updateSeries([
                { name: 'CPU (%)', data: seriesCPU, color: '#000000' },
                { name: 'RAM (%)', data: seriesRAM, color: '#F97316' },
                { name: 'Disco (%)', data: seriesDisk, color: '#FACC15' }
            ]);
        }

        function resetChartData() {
            chartCategories = [];
            seriesCPU = [];
            seriesRAM = [];
            seriesDisk = [];
        }

        // Cargar el historial completo y dibujar
        function updateChartData() {
            const dataToShow = isExpanded ? fullHistoricalData : fullHistoricalData.slice(-MAX_RECORDS);
            resetChartData();
            dataToShow.forEach(item => {
                chartCategories.push(item.timestamp);
                seriesCPU.push(item.cpu);
                seriesRAM.push(item.memory);
                seriesDisk.push(item.disk);
            });
            updateChart();
        }

        // Petición AJAX para historial
        async function fetchHistoricalData(hostId) {
            try {
                const url = `${appUrl}/servermonitor/history/${hostId}`;
                const response = await fetch(url);
                fullHistoricalData = await response.json();
                updateChartData();
            } catch (error) {
                console.error('Error al obtener historial:', error);
            }
        }

        // Petición AJAX para último dato
        async function fetchLatestChartData(hostId) {
            try {
                const url = `${appUrl}/servermonitor/latest/${hostId}`;
                const response = await fetch(url);
                const json = await response.json();

                chartCategories.push(json.timestamp);
                seriesCPU.push(json.cpu);
                seriesRAM.push(json.memory);
                seriesDisk.push(json.disk);

                // Limitar a 120 puntos
                if (chartCategories.length > 120) {
                    chartCategories.shift();
                    seriesCPU.shift();
                    seriesRAM.shift();
                    seriesDisk.shift();
                }
                updateChart();
            } catch (error) {
                console.error('Error al obtener último dato:', error);
            }
        }

        // Iniciar monitoreo para un host
        async function startLiveMonitoring(hostId) {
            currentHostId = hostId;
            resetChartData();

            // Destruir gráfica anterior si existe
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            createChart();

            // Detener intervalos previos
            if (pollInterval) {
                clearInterval(pollInterval);
            }

            isExpanded = false;
            // Cargar historial
            await fetchHistoricalData(currentHostId);
            // Añadir último dato
            fetchLatestChartData(currentHostId);

            // Cada 30s, nuevo dato
            pollInterval = setInterval(() => {
                fetchLatestChartData(currentHostId);
            }, 30000);
        }

        // Actualizar las tarjetas cada 30s
        function fetchHostData(hostId) {
            const url = `${appUrl}/servermonitor/latest/${hostId}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cpu-' + hostId).innerText  = parseFloat(data.cpu).toFixed(1) + '%';
                    document.getElementById('ram-' + hostId).innerText  = parseFloat(data.memory).toFixed(1) + '%';
                    document.getElementById('disk-' + hostId).innerText = parseFloat(data.disk).toFixed(1) + '%';
                })
                .catch(error => console.error('Error al actualizar tarjetas:', error));
        }

        function updateAllHosts() {
            const hostIds = [
                @foreach($hosts as $h)
                    {{ $h->id }},
                @endforeach
            ];
            hostIds.forEach(id => fetchHostData(id));
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Escuchar clicks en el nombre del host
            document.querySelectorAll('.host-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const hostId = parseInt(btn.getAttribute('data-host-id'));
                    if (hostId !== currentHostId) {
                        startLiveMonitoring(hostId);
                    }
                });
            });

            // Seleccionar primer host por defecto
            const firstBtn = document.querySelector('.host-btn');
            if (firstBtn) {
                const firstId = parseInt(firstBtn.getAttribute('data-host-id'));
                startLiveMonitoring(firstId);
            }

            // Actualizar tarjetas cada 30s
            setInterval(updateAllHosts, 30000);
        });

        // Ejemplo: confirmación al borrar
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(){
                const form = this.closest('form');
                Swal.fire({
                    title: '{{ __("¿Estás seguro?") }}',
                    text: '{{ __("Esta acción no se puede deshacer.") }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ __("Sí, borrar!") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection

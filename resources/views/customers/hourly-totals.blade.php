@extends('layouts.admin')

@section('title', __('Carga por hora'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item active">{{ $customer->name }} - {{ __('Carga por hora') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ __('Carga horaria de producción') }}</h5>
                        <p class="mb-0 text-muted">{{ __('Suma del tiempo teórico (minutos) por línea y hora') }}</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">{{ $customer->name }}</span>
                        @if($lastCapture)
                            <div class="small text-muted mt-1">{{ __('Última captura') }}: {{ $lastCapture }}</div>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(empty($series) || collect($series)->every(fn($serie) => empty($serie['data'])))
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>{{ __('Todavía no hay datos registrados para este cliente.') }}
                        </div>
                    @else
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="small text-muted">
                                {{ __('Selecciona un rango para analizar la carga horaria') }}
                            </div>
                            <div class="btn-group btn-group-sm flex-wrap" role="group" id="hourlyRangeSelector">
                                <button type="button" class="btn btn-outline-primary active" data-range="1w">{{ __('1 semana') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="1d">{{ __('1 día') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="1m">{{ __('1 mes') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="6m">{{ __('6 meses') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="1y">{{ __('1 año') }}</button>
                            </div>
                        </div>
                        <div id="hourlyTotalsChart" style="min-height: 460px;"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/svg.js@2.6.6/dist/svg.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartContainer = document.querySelector('#hourlyTotalsChart');
            const rangeSelector = document.querySelector('#hourlyRangeSelector');
            const rawSeries = @json($series);
            const lastCapture = @json($lastCapture);

            if (!chartContainer || !Array.isArray(rawSeries) || rawSeries.length === 0) {
                return;
            }

            const referenceDate = lastCapture ? new Date(lastCapture.replace(' ', 'T')) : new Date();

            const timeRanges = {
                '1d': { label: '{{ __('1 día') }}', duration: 24 * 60 * 60 * 1000 },
                '1w': { label: '{{ __('1 semana') }}', duration: 7 * 24 * 60 * 60 * 1000 },
                '1m': { label: '{{ __('1 mes') }}', duration: 30 * 24 * 60 * 60 * 1000 },
                '6m': { label: '{{ __('6 meses') }}', duration: 182 * 24 * 60 * 60 * 1000 },
                '1y': { label: '{{ __('1 año') }}', duration: 365 * 24 * 60 * 60 * 1000 }
            };

            const baseSeries = rawSeries
                .filter(series => Array.isArray(series.data) && series.data.length > 0)
                .map((series) => {
                    const points = series.data
                        .map(point => {
                            const timestamp = new Date(point.x.replace(' ', 'T')).getTime();
                            const value = Number(point.y ?? 0);
                            if (Number.isNaN(timestamp) || Number.isNaN(value)) {
                                return null;
                            }
                            return { x: timestamp, y: value };
                        })
                        .filter(Boolean)
                        .sort((a, b) => a.x - b.x);

                    return {
                        name: series.name,
                        data: points,
                    };
                });

            if (baseSeries.length === 0) {
                chartContainer.innerHTML = '<div class="alert alert-info">{{ __('Sin datos válidos para mostrar en la gráfica.') }}</div>';
                return;
            }

            const colorPalette = baseSeries.map((_, index) => {
                const hue = Math.floor((index / baseSeries.length) * 360);
                return `hsl(${hue}, 65%, 48%)`;
            });

            const chartOptions = {
                chart: {
                    type: 'line',
                    height: 460,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 0,
                    hover: {
                        size: 6
                    }
                },
                colors: colorPalette,
                series: baseSeries,
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeUTC: false
                    }
                },
                yaxis: {
                    title: {
                        text: '{{ __('Minutos acumulados') }}'
                    },
                    labels: {
                        formatter: (val) => val.toFixed(0)
                    }
                },
                tooltip: {
                    shared: true,
                    x: {
                        format: 'dd MMM yyyy HH:mm'
                    },
                    y: {
                        formatter: (val) => `${val.toLocaleString(undefined, { maximumFractionDigits: 2 })} {{ __('min') }}`
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    fontSize: '13px',
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 12
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 5
                    }
                },
                grid: {
                    borderColor: '#e5e7eb',
                    strokeDashArray: 4
                },
                noData: {
                    text: '{{ __('Sin datos para mostrar') }}'
                }
            };

            const chart = new ApexCharts(chartContainer, chartOptions);
            chart.render();

            const applyRange = (rangeKey) => {
                const range = timeRanges[rangeKey];
                if (!range) {
                    chart.updateSeries(baseSeries);
                    return;
                }

                const cutoff = referenceDate.getTime() - range.duration;

                const filteredSeries = baseSeries.map(series => ({
                    name: series.name,
                    data: series.data.filter(point => point.x >= cutoff)
                }));

                chart.updateSeries(filteredSeries);
            };

            if (rangeSelector) {
                rangeSelector.addEventListener('click', (event) => {
                    const button = event.target.closest('button[data-range]');
                    if (!button) {
                        return;
                    }

                    rangeSelector.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    applyRange(button.dataset.range);
                });

                // Inicializar con la opción activa por defecto
                const defaultButton = rangeSelector.querySelector('button.active');
                if (defaultButton) {
                    applyRange(defaultButton.dataset.range);
                }
            }
        });
    </script>
@endpush

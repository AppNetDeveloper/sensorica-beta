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
                                <button type="button" class="btn btn-outline-primary active" data-range="1d">{{ __('1 día') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="1w">{{ __('1 semana') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="1m">{{ __('1 mes') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="6m">{{ __('6 meses') }}</button>
                                <button type="button" class="btn btn-outline-primary" data-range="1y">{{ __('1 año') }}</button>
                            </div>
                        </div>
                        <div id="hourlyTotalsChart" style="min-height: 460px;"></div>
                        <div class="text-end mt-2 text-muted small" id="hourlyTotalsUpdated">
                            {{ $lastCapture ? __('Última captura') . ': ' . $lastCapture : __('Sin capturas disponibles') }}
                        </div>
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
            const updatedLabel = document.querySelector('#hourlyTotalsUpdated');
            const seedSeries = @json($series);

            if (!chartContainer || !Array.isArray(seedSeries) || seedSeries.length === 0) {
                return;
            }

            const hourlyRanges = {
                '1d': { label: '{{ __('Últimas 24 horas') }}', durationMs: 24 * 60 * 60 * 1000 },
                '1w': { label: '{{ __('Últimos 7 días') }}', durationMs: 7 * 24 * 60 * 60 * 1000 },
                '1m': { label: '{{ __('Últimos 30 días') }}', durationMs: 30 * 24 * 60 * 60 * 1000 },
                '6m': { label: '{{ __('Últimos 6 meses') }}', durationMs: 182 * 24 * 60 * 60 * 1000 },
                '1y': { label: '{{ __('Últimos 12 meses') }}', durationMs: 365 * 24 * 60 * 60 * 1000 },
            };

            let chartInstance = null;
            let refreshTimer = null;

            const buildColorPalette = (length) => {
                if (!length) {
                    return ['#3b82f6'];
                }
                return Array.from({ length }, (_, index) => {
                    const hue = Math.floor((index / length) * 360);
                    return `hsl(${hue}, 65%, 48%)`;
                });
            };

            const renderChart = (series, lastCapture) => {
                if (!chartInstance) {
                    chartInstance = new ApexCharts(chartContainer, {
                        chart: {
                            type: 'area',
                            height: 460,
                            animations: { enabled: true, easing: 'easeinout', speed: 600 },
                            toolbar: { show: true },
                            zoom: { enabled: true },
                        },
                        stroke: { curve: 'smooth', width: 2 },
                        dataLabels: { enabled: false },
                        markers: { size: 0, hover: { size: 6 } },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [0, 100, 100],
                            },
                        },
                        xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
                        yaxis: {
                            labels: { formatter: (val) => val.toFixed(0) },
                            title: { text: '{{ __('Minutos acumulados') }}' },
                        },
                        tooltip: {
                            shared: true,
                            x: { format: 'dd MMM yyyy HH:mm' },
                            y: { formatter: (val) => `${val.toLocaleString(undefined, { maximumFractionDigits: 2 })} {{ __('min') }}` },
                        },
                        legend: { position: 'top', horizontalAlign: 'left' },
                        series,
                        colors: buildColorPalette(series.length),
                        noData: { text: '{{ __('Sin datos para mostrar') }}' },
                    });
                    chartInstance.render();
                } else {
                    chartInstance.updateOptions({ colors: buildColorPalette(series.length) });
                    chartInstance.updateSeries(series);
                }

                if (updatedLabel) {
                    updatedLabel.textContent = lastCapture
                        ? `{{ __('Última captura') }}: ${lastCapture} · {{ __('Líneas activas') }}: ${series.length}`
                        : `{{ __('Sin capturas disponibles') }} · {{ __('Líneas activas') }}: ${series.length}`;
                }
            };

            const normalizeSeedSeries = () => {
                return seedSeries
                    .filter(series => Array.isArray(series.data) && series.data.length > 0)
                    .map(series => ({
                        name: series.name,
                        data: series.data.map(point => ({
                            x: new Date(point.x.replace(' ', 'T')).getTime(),
                            y: Number(point.y ?? 0)
                        })).filter(point => !Number.isNaN(point.x) && !Number.isNaN(point.y))
                            .sort((a, b) => a.x - b.x),
                    }));
            };

            const fetchData = (rangeKey = '1d') => {
                const range = hourlyRanges[rangeKey] || hourlyRanges['1d'];
                const now = new Date();
                const rangeStart = new Date(now.getTime() - range.durationMs);

                const params = new URLSearchParams({
                    range_start: rangeStart.toISOString(),
                });

                return fetch(`{{ route('customers.hourly-totals.data', [$customer->id]) }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.json())
                    .then(data => {
                        const series = Array.isArray(data.series) ? data.series : [];

                        const normalized = series.map(serie => ({
                            name: serie.name,
                            data: (serie.data || []).map(point => ({
                                x: new Date(String(point.x).replace(' ', 'T')).getTime(),
                                y: Number(point.y ?? 0),
                            })).filter(point => !Number.isNaN(point.x) && !Number.isNaN(point.y))
                                .sort((a, b) => a.x - b.x),
                        }));

                        renderChart(normalized, data.lastCapture || null);
                    })
                    .catch(() => {
                        const normalizedSeed = normalizeSeedSeries();
                        renderChart(normalizedSeed, @json($lastCapture));
                    });
            };

            const scheduleRefresh = (rangeKey) => {
                if (refreshTimer) {
                    clearInterval(refreshTimer);
                }
                fetchData(rangeKey);
                refreshTimer = setInterval(() => fetchData(rangeKey), 60 * 60 * 1000);
            };

            if (rangeSelector) {
                rangeSelector.addEventListener('click', (event) => {
                    const button = event.target.closest('button[data-range]');
                    if (!button) {
                        return;
                    }
                    rangeSelector.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    scheduleRefresh(button.dataset.range);
                });

                const defaultButton = rangeSelector.querySelector('button.active');
                const defaultRange = defaultButton ? defaultButton.dataset.range : '1d';
                scheduleRefresh(defaultRange);
            }

            window.addEventListener('beforeunload', () => {
                if (refreshTimer) {
                    clearInterval(refreshTimer);
                }
            });
        });
    </script>
@endpush

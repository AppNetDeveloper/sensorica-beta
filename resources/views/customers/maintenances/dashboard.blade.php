@extends('layouts.admin')

@section('title', __('Dashboard de Mantenimientos') . ' - ' . $customer->name)
@section('page-title', __('Dashboard de Mantenimientos'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.maintenances.index', $customer->id) }}">{{ __('Maintenances') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Dashboard') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">{{ __('Total (30 días)') }}</div>
            <h3 class="mb-0">{{ $totalMaintenances }}</h3>
          </div>
          <i class="ti ti-tools fs-1 text-primary"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">{{ __('Abiertos') }}</div>
            <h3 class="mb-0 {{ $openMaintenances > 0 ? 'text-warning' : 'text-success' }}">{{ $openMaintenances }}</h3>
          </div>
          <i class="ti ti-alert-circle fs-1 {{ $openMaintenances > 0 ? 'text-warning' : 'text-success' }}"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">{{ __('Tiempo Promedio') }}</div>
            <h3 class="mb-0">{{ gmdate('H:i', (int)$avgDowntime) }}</h3>
          </div>
          <i class="ti ti-clock fs-1 text-info"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body text-center">
        <a href="{{ route('customers.maintenances.index', $customer->id) }}" class="btn btn-primary w-100">
          <i class="ti ti-list me-2"></i>{{ __('Ver Listado') }}
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-chart-line me-2"></i>{{ __('Tendencia últimos 30 días') }}</h5>
      </div>
      <div class="card-body">
        <canvas id="trendChart" height="80"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-chart-pie me-2"></i>{{ __('Por Línea') }}</h5>
      </div>
      <div class="card-body">
        <canvas id="lineChart"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-flag-3 me-2"></i>{{ __('Top 10 Causas') }}</h5>
      </div>
      <div class="card-body">
        <canvas id="causesChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-tools me-2"></i>{{ __('Top 10 Piezas Usadas') }}</h5>
      </div>
      <div class="card-body">
        <canvas id="partsChart" height="120"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-clock-hour-4 me-2"></i>{{ __('Tiempo Promedio por Línea') }}</h5>
      </div>
      <div class="card-body">
        <canvas id="avgTimeChart" height="60"></canvas>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Tendencia
  const trendData = @json($trend);
  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: trendData.map(d => d.date),
      datasets: [{
        label: '{{ __("Mantenimientos") }}',
        data: trendData.map(d => d.count),
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // Por línea
  const byLineData = @json($byLine);
  new Chart(document.getElementById('lineChart'), {
    type: 'doughnut',
    data: {
      labels: byLineData.map(d => d.production_line?.name || 'Sin línea'),
      datasets: [{
        data: byLineData.map(d => d.total),
        backgroundColor: [
          'rgba(255, 99, 132, 0.8)',
          'rgba(54, 162, 235, 0.8)',
          'rgba(255, 206, 86, 0.8)',
          'rgba(75, 192, 192, 0.8)',
          'rgba(153, 102, 255, 0.8)',
          'rgba(255, 159, 64, 0.8)'
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true
    }
  });

  // Top causas
  const causesData = @json($topCauses);
  new Chart(document.getElementById('causesChart'), {
    type: 'bar',
    data: {
      labels: causesData.map(d => d.name),
      datasets: [{
        label: '{{ __("Ocurrencias") }}',
        data: causesData.map(d => d.maintenances_count),
        backgroundColor: 'rgba(255, 99, 132, 0.8)'
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // Top piezas
  const partsData = @json($topParts);
  new Chart(document.getElementById('partsChart'), {
    type: 'bar',
    data: {
      labels: partsData.map(d => d.name),
      datasets: [{
        label: '{{ __("Usos") }}',
        data: partsData.map(d => d.maintenances_count),
        backgroundColor: 'rgba(54, 162, 235, 0.8)'
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // Tiempo promedio por línea
  const avgTimeData = @json($avgTimeByLine);
  new Chart(document.getElementById('avgTimeChart'), {
    type: 'bar',
    data: {
      labels: avgTimeData.map(d => d.production_line?.name || 'Sin línea'),
      datasets: [{
        label: '{{ __("Minutos") }}',
        data: avgTimeData.map(d => Math.round(d.avg_time / 60)),
        backgroundColor: 'rgba(153, 102, 255, 0.8)'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
});
</script>
@endpush

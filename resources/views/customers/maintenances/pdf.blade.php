<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Mantenimientos - {{ $customer->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 14px; margin: 10px 0 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .totals { background-color: #e8f4f8; padding: 10px; margin: 15px 0; }
        .totals-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .total-item { text-align: center; }
        .total-label { font-size: 9px; color: #666; }
        .total-value { font-size: 16px; font-weight: bold; margin-top: 3px; }
        .header-info { margin-bottom: 15px; }
        .filters { background: #f9f9f9; padding: 8px; margin-bottom: 10px; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Reporte de Mantenimientos</h1>
    <div class="header-info">
        <strong>Cliente:</strong> {{ $customer->name }}<br>
        <strong>Fecha de generación:</strong> {{ date('d/m/Y H:i') }}
    </div>

    @if(!empty($filters))
    <div class="filters">
        <strong>Filtros aplicados:</strong>
        @if(!empty($filters['production_line_id'])) Línea: {{ $filters['production_line_id'] }} | @endif
        @if(!empty($filters['operator_id'])) Operario: {{ $filters['operator_id'] }} | @endif
        @if(!empty($filters['user_id'])) Usuario: {{ $filters['user_id'] }} | @endif
        @if(!empty($filters['created_from'])) Creado desde: {{ $filters['created_from'] }} | @endif
        @if(!empty($filters['created_to'])) Creado hasta: {{ $filters['created_to'] }} @endif
    </div>
    @endif

    <div class="totals">
        <h2>Resumen de Tiempos</h2>
        <div class="totals-grid">
            <div class="total-item">
                <div class="total-label">Parada Previa</div>
                <div class="total-value">{{ $totals['stopped_before_start'] ?? '00:00:00' }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">Tiempo Avería</div>
                <div class="total-value">{{ $totals['downtime'] ?? '00:00:00' }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">Tiempo Total</div>
                <div class="total-value">{{ $totals['total_time'] ?? '00:00:00' }}</div>
            </div>
        </div>
    </div>

    <h2>Detalle de Mantenimientos ({{ $maintenances->count() }} registros)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 15%;">Línea</th>
                <th style="width: 10%;">Creado</th>
                <th style="width: 10%;">Inicio</th>
                <th style="width: 10%;">Fin</th>
                <th style="width: 8%;">Parada</th>
                <th style="width: 8%;">Avería</th>
                <th style="width: 8%;">Total</th>
                <th style="width: 13%;">Causas</th>
                <th style="width: 13%;">Piezas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($maintenances as $m)
            @php
                $created = \Carbon\Carbon::parse($m->created_at);
                $end = $m->end_datetime ? \Carbon\Carbon::parse($m->end_datetime) : \Carbon\Carbon::now();
                $totalSeconds = max(0, $created->diffInSeconds($end));
                $stoppedSeconds = 0;
                $downtimeSeconds = 0;
                if ($m->start_datetime) {
                    $start = \Carbon\Carbon::parse($m->start_datetime);
                    $stoppedSeconds = max(0, $created->diffInSeconds($start));
                    $downtimeSeconds = max(0, $start->diffInSeconds($end));
                } else {
                    $stoppedSeconds = $totalSeconds;
                }
            @endphp
            <tr>
                <td>{{ $m->id }}</td>
                <td>{{ optional($m->productionLine)->name ?? '-' }}</td>
                <td>{{ $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $m->start_datetime ? \Carbon\Carbon::parse($m->start_datetime)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $m->end_datetime ? \Carbon\Carbon::parse($m->end_datetime)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ gmdate('H:i:s', $stoppedSeconds) }}</td>
                <td>{{ gmdate('H:i:s', $downtimeSeconds) }}</td>
                <td>{{ gmdate('H:i:s', $totalSeconds) }}</td>
                <td>{{ $m->causes ? $m->causes->pluck('name')->join(', ') : '-' }}</td>
                <td>{{ $m->parts ? $m->parts->pluck('name')->join(', ') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 9px; color: #666; text-align: center;">
        Generado por Sensorica - Sistema de Gestión de Mantenimientos
    </div>
</body>
</html>

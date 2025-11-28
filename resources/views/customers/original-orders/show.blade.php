@extends('layouts.admin')

@section('title', __('Order Details') . ' - ' . $originalOrder->order_id)

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.original-orders.index', $customer->id) }}">{{ __('Original Orders') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ $originalOrder->order_id }}</li>
    </ul>
@endsection

@section('content')
<div class="oo-show-container">
    {{-- Header Principal --}}
    <div class="oo-show-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="oo-show-header-icon me-3">
                        <i class="ti ti-package"></i>
                    </div>
                    <div>
                        <h4 class="oo-show-title mb-1">{{ $originalOrder->order_id }}</h4>
                        <p class="oo-show-subtitle mb-0">
                            <i class="ti ti-user me-1"></i> {{ $originalOrder->client_number ?: __('Sin cliente') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    <a href="{{ route('customers.original-orders.index', $customer->id) }}" class="oo-show-btn oo-show-btn-outline">
                        <i class="ti ti-arrow-left"></i>
                        <span>@lang('Back to List')</span>
                    </a>
                    @can('original-order-edit')
                    <a href="{{ route('customers.original-orders.edit', [$customer->id, $originalOrder->id]) }}" class="oo-show-btn oo-show-btn-light">
                        <i class="ti ti-edit"></i>
                        <span>@lang('Edit')</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas de Transferencia --}}
    @if($transferredTo)
    <div class="oo-transfer-alert oo-transfer-out mb-4">
        <div class="oo-transfer-icon">
            <i class="ti ti-transfer"></i>
        </div>
        <div class="oo-transfer-content">
            <h5 class="oo-transfer-title">
                <i class="ti ti-arrow-right me-2"></i>
                @lang('PEDIDO TRANSFERIDO A'): {{ $transferredTo->toCustomer->name }}
            </h5>
            <div class="oo-transfer-meta">
                <span><i class="ti ti-calendar me-1"></i> {{ $transferredTo->transferred_at->format('d/m/Y H:i') }}</span>
                @if($transferredTo->notes)
                <span><i class="ti ti-note me-1"></i> {{ $transferredTo->notes }}</span>
                @endif
            </div>
            @if($transferredTo->originalOrderTarget)
            <a href="{{ route('customers.original-orders.show', [$transferredTo->toCustomer->id, $transferredTo->originalOrderTarget->id]) }}"
               class="oo-show-btn oo-show-btn-warning mt-2">
                <i class="ti ti-external-link"></i>
                <span>@lang('Ver pedido transferido') ({{ $transferredTo->originalOrderTarget->order_id }})</span>
            </a>
            @endif
        </div>
    </div>
    @endif

    @if($transferredFrom)
    <div class="oo-transfer-alert oo-transfer-in mb-4">
        <div class="oo-transfer-icon">
            <i class="ti ti-arrow-down-circle"></i>
        </div>
        <div class="oo-transfer-content">
            <h5 class="oo-transfer-title">
                <i class="ti ti-arrow-left me-2"></i>
                @lang('PEDIDO RECIBIDO DE'): {{ $transferredFrom->fromCustomer->name }}
            </h5>
            <div class="oo-transfer-meta">
                <span><i class="ti ti-calendar me-1"></i> {{ $transferredFrom->transferred_at->format('d/m/Y H:i') }}</span>
                @if($transferredFrom->notes)
                <span><i class="ti ti-note me-1"></i> {{ $transferredFrom->notes }}</span>
                @endif
            </div>
            @if($transferredFrom->originalOrderSource)
            <a href="{{ route('customers.original-orders.show', [$transferredFrom->fromCustomer->id, $transferredFrom->originalOrderSource->id]) }}"
               class="oo-show-btn oo-show-btn-info mt-2">
                <i class="ti ti-external-link"></i>
                <span>@lang('Ver pedido original') ({{ $transferredFrom->originalOrderSource->order_id }})</span>
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Stats Cards --}}
    @php
        $qcIncidentsCount = \App\Models\QualityIssue::where('original_order_id', $originalOrder->id)
            ->orWhere('original_order_id_qc', $originalOrder->id)
            ->count();
        $hasQcConfirmation = method_exists($originalOrder, 'hasQcConfirmations')
            ? $originalOrder->hasQcConfirmations()
            : \App\Models\QcConfirmation::where('original_order_id', $originalOrder->id)->exists();
        $processCount = $originalOrder->processes->count();
        $finishedProcesses = $originalOrder->processes->filter(fn($p) => $p->pivot->finished)->count();
    @endphp

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="oo-stat-card">
                <div class="oo-stat-icon oo-stat-primary">
                    <i class="ti ti-list-check"></i>
                </div>
                <div class="oo-stat-info">
                    <h3>{{ $finishedProcesses }}/{{ $processCount }}</h3>
                    <span>@lang('Procesos')</span>
                </div>
                <div class="oo-stat-progress">
                    <div class="oo-stat-progress-bar" style="width: {{ $processCount > 0 ? ($finishedProcesses / $processCount * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="oo-stat-card">
                <div class="oo-stat-icon {{ $originalOrder->finished_at ? 'oo-stat-success' : 'oo-stat-warning' }}">
                    <i class="ti ti-{{ $originalOrder->finished_at ? 'circle-check' : 'clock' }}"></i>
                </div>
                <div class="oo-stat-info">
                    <h3>{{ $originalOrder->finished_at ? __('Finalizado') : __('Pendiente') }}</h3>
                    <span>@lang('Estado')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="oo-stat-card">
                <div class="oo-stat-icon {{ $originalOrder->in_stock ? 'oo-stat-success' : 'oo-stat-danger' }}">
                    <i class="ti ti-{{ $originalOrder->in_stock ? 'package' : 'package-off' }}"></i>
                </div>
                <div class="oo-stat-info">
                    <h3>{{ $originalOrder->in_stock ? __('Con Stock') : __('Sin Stock') }}</h3>
                    <span>@lang('Stock')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="oo-stat-card {{ $qcIncidentsCount > 0 ? 'oo-stat-card-alert' : '' }}">
                <div class="oo-stat-icon {{ $qcIncidentsCount > 0 ? 'oo-stat-danger' : 'oo-stat-success' }}">
                    <i class="ti ti-{{ $qcIncidentsCount > 0 ? 'alert-triangle' : 'shield-check' }}"></i>
                </div>
                <div class="oo-stat-info">
                    <h3>{{ $qcIncidentsCount }}</h3>
                    <span>@lang('Incidencias QC')</span>
                </div>
                @if($qcIncidentsCount > 0)
                <a href="{{ route('customers.quality-incidents.index', ['customer' => $customer->id]) }}" class="oo-stat-link">
                    <i class="ti ti-external-link"></i>
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Columna Izquierda: Información del Pedido --}}
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="oo-info-card">
                <div class="oo-info-card-header">
                    <i class="ti ti-file-info me-2"></i>
                    @lang('Order Information')
                </div>
                <div class="oo-info-card-body">
                    <div class="oo-info-grid">
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Order ID')</span>
                            <span class="oo-info-value">{{ $originalOrder->order_id }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Client Name')</span>
                            <span class="oo-info-value">{{ $originalOrder->client_number ?: '-' }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Address')</span>
                            <span class="oo-info-value">{{ $originalOrder->address ?: '-' }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Phone')</span>
                            <span class="oo-info-value">{{ $originalOrder->phone ?: '-' }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('CIF / NIF')</span>
                            <span class="oo-info-value">{{ $originalOrder->cif_nif ?: '-' }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Order Reference')</span>
                            <span class="oo-info-value">{{ $originalOrder->ref_order ?: '-' }}</span>
                        </div>
                    </div>

                    <div class="oo-info-divider"></div>

                    <div class="oo-info-grid">
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Processed')</span>
                            <span class="oo-info-value">
                                @if($originalOrder->processed)
                                    <span class="badge bg-success"><i class="ti ti-check me-1"></i>@lang('Yes')</span>
                                @else
                                    <span class="badge bg-warning"><i class="ti ti-clock me-1"></i>@lang('No')</span>
                                @endif
                            </span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('QC Confirmation')</span>
                            <span class="oo-info-value">
                                @if($hasQcConfirmation)
                                    <span class="badge bg-success"><i class="ti ti-check me-1"></i>@lang('Done')</span>
                                @else
                                    <span class="badge bg-warning"><i class="ti ti-clock me-1"></i>@lang('Pending')</span>
                                @endif
                                <a href="{{ route('customers.qc-confirmations.index', ['customer' => $customer->id]) }}" class="btn btn-sm btn-link p-0 ms-2">
                                    <i class="ti ti-external-link"></i>
                                </a>
                            </span>
                        </div>
                    </div>

                    <div class="oo-info-divider"></div>

                    <div class="oo-info-grid">
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Delivery Date')</span>
                            <span class="oo-info-value">
                                @if($originalOrder->delivery_date)
                                    <i class="ti ti-truck text-muted me-1"></i>
                                    {{ $originalOrder->delivery_date->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Fecha Pedido ERP')</span>
                            <span class="oo-info-value">
                                @if($originalOrder->fecha_pedido_erp)
                                    {{ $originalOrder->fecha_pedido_erp->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Created At')</span>
                            <span class="oo-info-value">{{ $originalOrder->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Updated At')</span>
                            <span class="oo-info-value">{{ $originalOrder->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="oo-info-item">
                            <span class="oo-info-label">@lang('Finished At')</span>
                            <span class="oo-info-value">
                                @if($originalOrder->finished_at)
                                    <span class="badge bg-success">{{ $originalOrder->finished_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="badge bg-info">@lang('Pending')</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Tiempos de procesamiento (solo si está finalizado) --}}
                    @if($originalOrder->finished_at)
                    @php
                        $fmt = function($seconds) {
                            if ($seconds === null) return null;
                            $hours = floor($seconds / 3600);
                            $minutes = floor(($seconds % 3600) / 60);
                            $secs = $seconds % 60;
                            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                        };

                        $erpToFinishedSeconds = null;
                        if (!empty($originalOrder->fecha_pedido_erp)) {
                            try {
                                $erpToFinishedSeconds = $originalOrder->fecha_pedido_erp->diffInSeconds($originalOrder->finished_at);
                            } catch (Exception $e) {
                                $erpToFinishedSeconds = null;
                            }
                        }

                        $createdToFinishedSeconds = null;
                        try {
                            $createdToFinishedSeconds = $originalOrder->created_at->diffInSeconds($originalOrder->finished_at);
                        } catch (Exception $e) {
                            $createdToFinishedSeconds = null;
                        }
                    @endphp
                    <div class="oo-info-divider"></div>
                    <div class="oo-time-stats">
                        <div class="oo-time-stat">
                            <i class="ti ti-clock-hour-4"></i>
                            <div>
                                <span class="oo-time-label">@lang('Tiempo desde ERP hasta fin')</span>
                                <span class="oo-time-value">{{ $erpToFinishedSeconds !== null ? $fmt($erpToFinishedSeconds) : '-' }}</span>
                            </div>
                        </div>
                        <div class="oo-time-stat">
                            <i class="ti ti-clock-hour-8"></i>
                            <div>
                                <span class="oo-time-label">@lang('Tiempo desde creación hasta fin')</span>
                                <span class="oo-time-value">{{ $createdToFinishedSeconds !== null ? $fmt($createdToFinishedSeconds) : '-' }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Order Details JSON --}}
            <div class="oo-info-card mt-4">
                <div class="oo-info-card-header">
                    <i class="ti ti-code me-2"></i>
                    @lang('Order Details')
                    <button class="btn btn-sm btn-link ms-auto p-0" type="button" data-bs-toggle="collapse" data-bs-target="#orderDetailsCollapse">
                        <i class="ti ti-chevron-down"></i>
                    </button>
                </div>
                <div class="collapse" id="orderDetailsCollapse">
                    <div class="oo-info-card-body">
                        @php
                            if (is_string($originalOrder->order_details)) {
                                $details = json_decode($originalOrder->order_details, true);
                            } elseif (is_array($originalOrder->order_details)) {
                                $details = $originalOrder->order_details;
                            } else {
                                $details = null;
                            }
                        @endphp
                        @if(is_array($details) && !empty($details))
                            <pre class="oo-json-preview">{{ json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @elseif(is_string($originalOrder->order_details))
                            <pre class="oo-json-preview">{{ $originalOrder->order_details }}</pre>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="ti ti-file-off fs-1"></i>
                                <p class="mt-2 mb-0">@lang('No order details available')</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Procesos --}}
        <div class="col-lg-7 col-md-12">
            <div class="oo-processes-card">
                <div class="oo-processes-header">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-settings me-2"></i>
                        @lang('Associated Processes')
                        <span class="badge bg-light text-dark ms-2">{{ $processCount }}</span>
                    </div>
                </div>

                {{-- Leyenda compacta --}}
                <div class="oo-legend-bar">
                    <span class="oo-legend-item"><span class="badge bg-success">●</span> @lang('Finalizado')</span>
                    <span class="oo-legend-item"><span class="badge bg-primary">●</span> @lang('En fabricación')</span>
                    <span class="oo-legend-item"><span class="badge bg-info">●</span> @lang('Asignado')</span>
                    <span class="oo-legend-item"><span class="badge bg-secondary">●</span> @lang('Sin asignar')</span>
                    <span class="oo-legend-item"><span class="badge bg-danger">●</span> @lang('Incidencia')</span>
                </div>

                <div class="oo-processes-body">
                    @forelse($originalOrder->processes->sortBy('sequence') as $index => $process)
                        @php
                            $pivot = $process->pivot;
                            $articles = $pivot->articles ?? collect();
                            $isFinished = (bool)$pivot->finished;
                            $productionOrder = $pivot->productionOrders->first();
                            $status = $productionOrder ? $productionOrder->status : null;
                            $productionLineId = $productionOrder ? $productionOrder->production_line_id : null;

                            if ($isFinished) {
                                $statusClass = 'success';
                                $statusText = $pivot->finished_at ? $pivot->finished_at->format('d/m/Y H:i') : __('Finalizado');
                                $statusIcon = 'circle-check';
                            } else {
                                if ($status === 0) {
                                    if (is_null($productionLineId)) {
                                        $statusClass = 'secondary';
                                        $statusText = __('Sin asignar');
                                        $statusIcon = 'clock';
                                    } else {
                                        $statusClass = 'info';
                                        $statusText = __('Asignada a máquina');
                                        $statusIcon = 'settings';
                                    }
                                } elseif ($status === 1) {
                                    $statusClass = 'primary';
                                    $statusText = __('En fabricación');
                                    $statusIcon = 'player-play';
                                } elseif ($status > 2) {
                                    $statusClass = 'danger';
                                    $statusText = __('Con incidencia');
                                    $statusIcon = 'alert-triangle';
                                } else {
                                    $statusClass = 'warning';
                                    $statusText = __('Pendiente');
                                    $statusIcon = 'clock';
                                }
                            }

                            $totalUnits = (isset($pivot->box) && isset($pivot->units_box) && is_numeric($pivot->box) && is_numeric($pivot->units_box))
                                ? ($pivot->box * $pivot->units_box)
                                : null;

                            $timeFormatted = $pivot->time
                                ? sprintf("%02d:%02d:%02d", floor($pivot->time / 3600), floor(($pivot->time / 60) % 60), $pivot->time % 60)
                                : null;
                        @endphp

                        <div class="oo-process-item" data-process-id="{{ $pivot->id }}">
                            <div class="oo-process-header" data-bs-toggle="collapse" data-bs-target="#process-{{ $pivot->id }}" aria-expanded="false">
                                <div class="oo-process-status oo-process-status-{{ $statusClass }}">
                                    <i class="ti ti-{{ $statusIcon }}"></i>
                                </div>
                                <div class="oo-process-main">
                                    <div class="oo-process-title">
                                        <span class="oo-process-code">{{ $process->code }}</span>
                                        @if($pivot->grupo_numero)
                                            <span class="oo-process-group">(Grupo {{ $pivot->grupo_numero }})</span>
                                        @endif
                                        <span class="oo-process-name">{{ $process->name }}</span>
                                    </div>
                                    <div class="oo-process-meta">
                                        <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                        @if($timeFormatted)
                                            <span class="oo-process-time" title="@lang('Tiempo previsto de fabricación')"><i class="ti ti-clock"></i> {{ $timeFormatted }}</span>
                                        @endif
                                        {{-- Mostrar indicadores de planificación en la cabecera --}}
                                        @if(($status === 1 || ($status === 0 && !is_null($productionLineId))))
                                            @if($productionOrder && !is_null($productionOrder->accumulated_time))
                                                @php
                                                    $accSec = (int)$productionOrder->accumulated_time;
                                                    $accFormatted = sprintf("%02d:%02d:%02d", floor($accSec / 3600), floor(($accSec % 3600) / 60), $accSec % 60);
                                                @endphp
                                                <span class="oo-mini-time-badge" title="@lang('Tiempo de ocupación máquina')">
                                                    <i class="ti ti-hourglass"></i> {{ $accFormatted }}
                                                </span>
                                            @else
                                                <span class="oo-mini-time-badge oo-mini-time-empty" title="@lang('Tiempo de ocupación máquina')">
                                                    <i class="ti ti-hourglass"></i> --:--:--
                                                </span>
                                            @endif
                                            @if($productionOrder && $productionOrder->estimated_start_datetime)
                                                <span class="oo-mini-time-badge oo-mini-time-start" title="@lang('Fecha estimada de inicio')">
                                                    <i class="ti ti-calendar-event"></i> {{ \Carbon\Carbon::parse($productionOrder->estimated_start_datetime)->format('d/m H:i') }}
                                                </span>
                                            @endif
                                            @if($productionOrder && $productionOrder->estimated_end_datetime)
                                                <span class="oo-mini-time-badge oo-mini-time-end" title="@lang('Fecha estimada de fin')">
                                                    <i class="ti ti-calendar-check"></i> {{ \Carbon\Carbon::parse($productionOrder->estimated_end_datetime)->format('d/m H:i') }}
                                                </span>
                                            @endif
                                        @endif
                                        @if($pivot->in_stock === 1)
                                            <span class="badge bg-success-subtle text-success" title="@lang('Con Stock')"><i class="ti ti-package"></i></span>
                                        @elseif($pivot->in_stock === 0)
                                            <span class="badge bg-danger-subtle text-danger" title="@lang('Sin Stock')"><i class="ti ti-package-off"></i></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="oo-process-toggle">
                                    <i class="ti ti-chevron-down"></i>
                                </div>
                            </div>

                            <div class="collapse" id="process-{{ $pivot->id }}">
                                <div class="oo-process-details">
                                    {{-- Info del proceso --}}
                                    <div class="oo-process-info-grid">
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Sequence')</span>
                                            <span class="value">{{ $process->sequence }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Correction Factor')</span>
                                            <span class="value">{{ number_format($process->factor_correccion, 2) }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Time')</span>
                                            <span class="value">{{ $timeFormatted ?? '-' }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Boxes')</span>
                                            <span class="value">{{ $pivot->box ?? '-' }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Units/Box')</span>
                                            <span class="value">{{ $pivot->units_box ?? '-' }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Total Units')</span>
                                            <span class="value">{{ $totalUnits ?? '-' }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Pallets')</span>
                                            <span class="value">{{ $pivot->number_of_pallets ?? '-' }}</span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Created')</span>
                                            <span class="value">
                                                @if($pivot->created)
                                                    <span class="badge bg-success">@lang('Yes')</span>
                                                @else
                                                    <span class="badge bg-warning">@lang('No')</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="oo-process-info-item">
                                            <span class="label">@lang('Stock Status')</span>
                                            <span class="value">
                                                @if($pivot->in_stock === 0)
                                                    <span class="badge bg-danger">@lang('Sin Stock')</span>
                                                @elseif($pivot->in_stock === 1)
                                                    <span class="badge bg-success">@lang('Con Stock')</span>
                                                @else
                                                    <span class="badge bg-secondary">@lang('No Especificado')</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Tiempos estimados si está asignado/en fabricación --}}
                                    @if(($status === 1 || ($status === 0 && !is_null($productionLineId))))
                                    <div class="oo-process-times">
                                        {{-- Tiempo de ocupación máquina --}}
                                        <div class="oo-time-badge" title="@lang('Tiempo de ocupación máquina')">
                                            <i class="ti ti-hourglass"></i>
                                            @if($productionOrder && !is_null($productionOrder->accumulated_time))
                                                @php
                                                    $seconds = (int)$productionOrder->accumulated_time;
                                                    $hours = floor($seconds / 3600);
                                                    $minutes = floor(($seconds % 3600) / 60);
                                                    $secs = $seconds % 60;
                                                    $formattedAccTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
                                                @endphp
                                                <span>@lang('Tiempo ocupación'): {{ $formattedAccTime }}</span>
                                            @else
                                                <span>@lang('Sin tiempo acumulado')</span>
                                            @endif
                                        </div>
                                        @if($productionOrder && $productionOrder->estimated_start_datetime)
                                            <div class="oo-time-badge oo-time-badge-start" title="@lang('Fecha estimada de inicio de fabricación')">
                                                <i class="ti ti-calendar-event"></i>
                                                <span>@lang('Inicio est.'): {{ \Carbon\Carbon::parse($productionOrder->estimated_start_datetime)->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @endif
                                        @if($productionOrder && $productionOrder->estimated_end_datetime)
                                            <div class="oo-time-badge oo-time-badge-end" title="@lang('Fecha estimada de fin de fabricación')">
                                                <i class="ti ti-calendar-check"></i>
                                                <span>@lang('Fin est.'): {{ \Carbon\Carbon::parse($productionOrder->estimated_end_datetime)->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Archivos del proceso --}}
                                    <div class="oo-process-files">
                                        <div class="oo-process-files-header">
                                            <span><i class="ti ti-files me-1"></i> @lang('Process Files')</span>
                                            @can('original-order-files-upload')
                                            <div class="oo-process-files-upload">
                                                <input type="file" accept="image/*,application/pdf" multiple class="form-control form-control-sm"
                                                   data-file-input
                                                   data-upload-url="{{ route('customers.original-orders.processes.files.store', [$customer->id, $originalOrder->id, $pivot->id]) }}">
                                                <button type="button" class="btn btn-sm btn-primary" data-upload-btn>
                                                    <i class="ti ti-upload"></i>
                                                </button>
                                            </div>
                                            @endcan
                                        </div>
                                        <div class="small text-muted mb-2">@lang('Allowed types'): JPG, PNG, GIF, WEBP, PDF. @lang('Max') 10MB</div>
                                        <div class="row oo-files-grid" data-files-container
                                             data-index-url="{{ route('customers.original-orders.processes.files.index', [$customer->id, $originalOrder->id, $pivot->id]) }}"
                                        ></div>
                                    </div>

                                    {{-- Artículos relacionados --}}
                                    @if($articles->isNotEmpty())
                                    <div class="oo-process-articles">
                                        <h6><i class="ti ti-box me-1"></i> @lang('Related Articles') ({{ $articles->count() }})</h6>
                                        <div class="oo-articles-list">
                                            @foreach($articles as $article)
                                            <div class="oo-article-item">
                                                <div class="oo-article-main">
                                                    <span class="oo-article-code">{{ $article->codigo_articulo }}</span>
                                                    <span class="oo-article-desc">{{ $article->descripcion_articulo }}</span>
                                                </div>
                                                <div class="oo-article-meta">
                                                    @if($article->grupo_articulo)
                                                        <span class="badge bg-light text-dark">{{ $article->grupo_articulo }}</span>
                                                    @endif
                                                    @if($article->in_stock === 0)
                                                        <span class="badge bg-danger">@lang('Sin Stock')</span>
                                                    @elseif($article->in_stock === 1)
                                                        <span class="badge bg-success">@lang('Con Stock')</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="oo-empty-processes">
                            <i class="ti ti-settings-off"></i>
                            <p>@lang('No processes associated with this order.')</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Leyenda de indicadores --}}
    <div class="oo-legend-footer mt-4">
        <div class="oo-legend-footer-header">
            <i class="ti ti-info-circle"></i>
            <span>@lang('Leyenda')</span>
        </div>
        <div class="oo-legend-footer-content">
            <div class="oo-legend-section">
                <span class="oo-legend-section-title">@lang('Estados'):</span>
                <div class="oo-legend-badges">
                    <span class="badge bg-success" title="@lang('Proceso completado')"><i class="ti ti-circle-check me-1"></i>@lang('Finalizado')</span>
                    <span class="badge bg-primary" title="@lang('En producción activa')"><i class="ti ti-player-play me-1"></i>@lang('En fabricación')</span>
                    <span class="badge bg-info" title="@lang('Asignado a máquina')"><i class="ti ti-settings me-1"></i>@lang('Asignado')</span>
                    <span class="badge bg-secondary" title="@lang('Pendiente de asignar')"><i class="ti ti-clock me-1"></i>@lang('Sin asignar')</span>
                    <span class="badge bg-danger" title="@lang('Con problemas')"><i class="ti ti-alert-triangle me-1"></i>@lang('Incidencia')</span>
                </div>
            </div>
            <div class="oo-legend-divider"></div>
            <div class="oo-legend-section">
                <span class="oo-legend-section-title">@lang('Tiempos'):</span>
                <div class="oo-legend-badges">
                    <span class="oo-legend-time-item oo-legend-time-clock" title="@lang('Tiempo previsto de fabricación del proceso')">
                        <i class="ti ti-clock"></i> @lang('Tiempo fabricación')
                    </span>
                    <span class="oo-legend-time-item" title="@lang('Tiempo total que la máquina ha estado trabajando en este proceso')">
                        <i class="ti ti-hourglass"></i> @lang('Ocupación máquina')
                    </span>
                    <span class="oo-legend-time-item oo-legend-time-start" title="@lang('Cuándo se prevé que comience la fabricación')">
                        <i class="ti ti-calendar-event"></i> @lang('Inicio estimado')
                    </span>
                    <span class="oo-legend-time-item oo-legend-time-end" title="@lang('Cuándo se prevé que termine la fabricación')">
                        <i class="ti ti-calendar-check"></i> @lang('Fin estimado')
                    </span>
                </div>
            </div>
            <div class="oo-legend-divider"></div>
            <div class="oo-legend-section">
                <span class="oo-legend-section-title">@lang('Stock'):</span>
                <div class="oo-legend-badges">
                    <span class="badge bg-success-subtle text-success" title="@lang('El proceso tiene stock disponible')">
                        <i class="ti ti-package me-1"></i>@lang('Con Stock')
                    </span>
                    <span class="badge bg-danger-subtle text-danger" title="@lang('El proceso no tiene stock disponible')">
                        <i class="ti ti-package-off me-1"></i>@lang('Sin Stock')
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para eliminar archivos --}}
<div class="modal fade" id="processFileDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Delete File')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>@lang('Are you sure you want to delete this file?')</p>
                <div class="small text-muted" data-delete-filename></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProcessFile">@lang('Delete')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* ========================================
   ORIGINAL ORDER SHOW - ESTILOS MODERNOS
   ======================================== */

/* Container */
.oo-show-container {
    padding: 0;
}

/* Header Principal */
.oo-show-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.oo-show-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}

.oo-show-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.oo-show-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Header Buttons */
.oo-show-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.oo-show-btn:hover {
    transform: translateY(-1px);
}

.oo-show-btn-light {
    background: white;
    color: #667eea;
}
.oo-show-btn-light:hover {
    background: #f8fafc;
    color: #5a67d8;
}

.oo-show-btn-outline {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}
.oo-show-btn-outline:hover {
    background: rgba(255,255,255,0.25);
    color: white;
}

.oo-show-btn-warning {
    background: #f59e0b;
    color: white;
}
.oo-show-btn-warning:hover {
    background: #d97706;
    color: white;
}

.oo-show-btn-info {
    background: #0ea5e9;
    color: white;
}
.oo-show-btn-info:hover {
    background: #0284c7;
    color: white;
}

/* Transfer Alerts */
.oo-transfer-alert {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid;
}

.oo-transfer-out {
    background: #fef3c7;
    border-color: #f59e0b;
}

.oo-transfer-in {
    background: #dbeafe;
    border-color: #3b82f6;
}

.oo-transfer-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.oo-transfer-out .oo-transfer-icon {
    background: rgba(245, 158, 11, 0.2);
    color: #b45309;
}

.oo-transfer-in .oo-transfer-icon {
    background: rgba(59, 130, 246, 0.2);
    color: #1d4ed8;
}

.oo-transfer-title {
    font-weight: 600;
    margin: 0 0 8px 0;
    font-size: 1rem;
}

.oo-transfer-out .oo-transfer-title { color: #92400e; }
.oo-transfer-in .oo-transfer-title { color: #1e40af; }

.oo-transfer-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.875rem;
}

.oo-transfer-out .oo-transfer-meta { color: #a16207; }
.oo-transfer-in .oo-transfer-meta { color: #1e40af; }

/* Stats Cards */
.oo-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    position: relative;
    overflow: hidden;
    height: 100%;
}

.oo-stat-card-alert {
    border: 2px solid #fecaca;
}

.oo-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.oo-stat-primary { background: rgba(102, 126, 234, 0.15); color: #667eea; }
.oo-stat-success { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.oo-stat-warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.oo-stat-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.oo-stat-cyan { background: rgba(14, 165, 233, 0.15); color: #0ea5e9; }

.oo-stat-info h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.oo-stat-info span {
    color: #64748b;
    font-size: 0.8rem;
}

.oo-stat-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #e2e8f0;
}

.oo-stat-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 0 2px 2px 0;
    transition: width 0.3s ease;
}

.oo-stat-link {
    position: absolute;
    top: 12px;
    right: 12px;
    color: #94a3b8;
    font-size: 1rem;
}

.oo-stat-link:hover {
    color: #667eea;
}

/* Info Card */
.oo-info-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
}

.oo-info-card-header {
    padding: 16px 20px;
    background: #f8fafc;
    font-weight: 600;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
}

.oo-info-card-body {
    padding: 20px;
}

.oo-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.oo-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.oo-info-label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.oo-info-value {
    font-size: 0.9rem;
    color: #1e293b;
    font-weight: 500;
}

.oo-info-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 16px 0;
}

.oo-time-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.oo-time-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f1f5f9;
    border-radius: 8px;
}

.oo-time-stat i {
    font-size: 1.25rem;
    color: #667eea;
}

.oo-time-label {
    font-size: 0.75rem;
    color: #64748b;
    display: block;
}

.oo-time-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.oo-json-preview {
    background: #1e293b;
    color: #e2e8f0;
    padding: 16px;
    border-radius: 8px;
    font-size: 0.8rem;
    max-height: 300px;
    overflow-y: auto;
    margin: 0;
}

/* Processes Card */
.oo-processes-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
}

.oo-processes-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

.oo-legend-bar {
    padding: 10px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.75rem;
}

.oo-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #64748b;
}

.oo-legend-item .badge {
    font-size: 0.5rem;
    padding: 2px 4px;
}

.oo-processes-body {
    padding: 12px;
}

/* Process Item */
.oo-process-item {
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.oo-process-item:last-child {
    margin-bottom: 0;
}

.oo-process-item:hover {
    border-color: #cbd5e1;
}

.oo-process-header {
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.oo-process-header:hover {
    background: #f1f5f9;
}

.oo-process-header[aria-expanded="true"] .oo-process-toggle i {
    transform: rotate(180deg);
}

.oo-process-status {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.oo-process-status-success { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.oo-process-status-primary { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.oo-process-status-info { background: rgba(14, 165, 233, 0.15); color: #0ea5e9; }
.oo-process-status-warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.oo-process-status-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.oo-process-status-secondary { background: rgba(100, 116, 139, 0.15); color: #64748b; }

.oo-process-main {
    flex: 1;
    min-width: 0;
}

.oo-process-title {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 6px;
}

.oo-process-code {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.95rem;
}

.oo-process-group {
    font-size: 0.8rem;
    color: #64748b;
}

.oo-process-name {
    font-size: 0.85rem;
    color: #64748b;
}

.oo-process-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.oo-process-meta .badge {
    font-size: 0.7rem;
    font-weight: 500;
}

.oo-process-time {
    font-size: 0.75rem;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Mini time badges en cabecera */
.oo-mini-time-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 500;
    background: #e0e7ff;
    color: #4338ca;
}

.oo-mini-time-badge i {
    font-size: 0.75rem;
}

.oo-mini-time-empty {
    background: #f1f5f9;
    color: #94a3b8;
}

.oo-mini-time-start {
    background: #dbeafe;
    color: #1d4ed8;
}

.oo-mini-time-end {
    background: #dcfce7;
    color: #16a34a;
}

.oo-process-toggle {
    color: #94a3b8;
    transition: transform 0.2s ease;
}

.oo-process-toggle i {
    transition: transform 0.2s ease;
}

/* Process Details */
.oo-process-details {
    padding: 0 16px 16px;
    border-top: 1px solid #e2e8f0;
    background: white;
}

.oo-process-info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding-top: 16px;
}

.oo-process-info-item {
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
}

.oo-process-info-item .label {
    display: block;
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.oo-process-info-item .value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
}

.oo-process-times {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.oo-time-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
}

.oo-time-badge i {
    font-size: 1rem;
}

.oo-time-badge-start {
    background: #dbeafe;
    color: #1d4ed8;
}

.oo-time-badge-end {
    background: #dcfce7;
    color: #16a34a;
}

/* Process Files */
.oo-process-files {
    margin-top: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
}

.oo-process-files-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    color: #334155;
}

.oo-process-files-upload {
    display: flex;
    gap: 8px;
    align-items: center;
}

.oo-process-files-upload input[type="file"] {
    max-width: 200px;
    font-size: 0.75rem;
}

.oo-files-grid {
    min-height: 60px;
}

/* Process Articles */
.oo-process-articles {
    margin-top: 16px;
    padding: 16px;
    background: #fffbeb;
    border-radius: 8px;
    border: 1px solid #fde68a;
}

.oo-process-articles h6 {
    margin: 0 0 12px 0;
    font-size: 0.85rem;
    color: #92400e;
}

.oo-articles-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.oo-article-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #fde68a;
}

.oo-article-main {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.oo-article-code {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1e293b;
}

.oo-article-desc {
    font-size: 0.75rem;
    color: #64748b;
}

.oo-article-meta {
    display: flex;
    gap: 6px;
}

/* Empty State */
.oo-empty-processes {
    text-align: center;
    padding: 48px 24px;
    color: #94a3b8;
}

.oo-empty-processes i {
    font-size: 3rem;
    margin-bottom: 12px;
}

.oo-empty-processes p {
    margin: 0;
}

/* File items styling (reused from original) */
.oo-files-grid .col-md-3 {
    padding: 4px;
}

.oo-files-grid .border {
    border-radius: 8px !important;
    transition: all 0.2s ease;
}

.oo-files-grid .border:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Legend Footer */
.oo-legend-footer {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.oo-legend-footer-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
}

.oo-legend-footer-content {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.oo-legend-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.oo-legend-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.oo-legend-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.oo-legend-badges .badge {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 5px 10px;
    cursor: help;
}

.oo-legend-divider {
    width: 1px;
    height: 32px;
    background: #e2e8f0;
}

.oo-legend-time-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 500;
    background: #e0e7ff;
    color: #4338ca;
    cursor: help;
}

.oo-legend-time-item i {
    font-size: 0.85rem;
}

.oo-legend-time-start {
    background: #dbeafe;
    color: #1d4ed8;
}

.oo-legend-time-end {
    background: #dcfce7;
    color: #16a34a;
}

.oo-legend-time-clock {
    background: #f1f5f9;
    color: #64748b;
}

/* Dark Mode */
[data-theme="dark"] .oo-stat-card,
[data-theme="dark"] .oo-info-card,
[data-theme="dark"] .oo-processes-card {
    background: #1e293b;
}

[data-theme="dark"] .oo-stat-info h3,
[data-theme="dark"] .oo-info-value,
[data-theme="dark"] .oo-process-code {
    color: #f1f5f9;
}

[data-theme="dark"] .oo-info-card-header,
[data-theme="dark"] .oo-legend-bar {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .oo-process-item {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .oo-process-header:hover {
    background: #1e293b;
}

[data-theme="dark"] .oo-process-details {
    background: #1e293b;
    border-color: #334155;
}

[data-theme="dark"] .oo-process-info-item,
[data-theme="dark"] .oo-process-files {
    background: #0f172a;
}

[data-theme="dark"] .oo-json-preview {
    background: #0f172a;
}

[data-theme="dark"] .oo-transfer-out {
    background: #422006;
    border-color: #f59e0b;
}

[data-theme="dark"] .oo-transfer-in {
    background: #172554;
    border-color: #3b82f6;
}

[data-theme="dark"] .oo-transfer-out .oo-transfer-title,
[data-theme="dark"] .oo-transfer-out .oo-transfer-meta { color: #fcd34d; }

[data-theme="dark"] .oo-transfer-in .oo-transfer-title,
[data-theme="dark"] .oo-transfer-in .oo-transfer-meta { color: #93c5fd; }

[data-theme="dark"] .oo-legend-footer {
    background: #1e293b;
    border-color: #334155;
}

[data-theme="dark"] .oo-legend-section-title {
    color: #94a3b8;
}

[data-theme="dark"] .oo-legend-divider {
    background: #334155;
}

/* Responsive */
@media (max-width: 991.98px) {
    .oo-show-btn span {
        display: none;
    }
    .oo-show-btn {
        padding: 10px 14px;
    }
    .oo-process-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .oo-show-header {
        padding: 16px;
    }
    .oo-show-header-icon {
        width: 48px;
        height: 48px;
        font-size: 1.4rem;
    }
    .oo-show-title {
        font-size: 1.1rem;
    }
    .oo-info-grid {
        grid-template-columns: 1fr;
    }
    .oo-process-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .oo-process-files-upload {
        flex-direction: column;
        width: 100%;
    }
    .oo-process-files-upload input[type="file"] {
        max-width: 100%;
    }
    .oo-stat-card {
        padding: 16px;
    }
    .oo-stat-icon {
        width: 42px;
        height: 42px;
        font-size: 1.2rem;
    }
    .oo-stat-info h3 {
        font-size: 1.1rem;
    }
    .oo-transfer-alert {
        flex-direction: column;
        align-items: flex-start;
    }
    .oo-mini-time-badge {
        font-size: 0.6rem;
        padding: 2px 6px;
    }
    .oo-mini-time-badge i {
        font-size: 0.65rem;
    }
    .oo-article-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    .oo-legend-bar {
        gap: 8px;
        font-size: 0.65rem;
    }
}

@media (max-width: 575.98px) {
    .oo-process-title {
        flex-direction: column;
        align-items: flex-start;
    }
    .oo-process-info-grid {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .oo-process-info-item {
        padding: 8px;
    }
    .oo-time-badge {
        font-size: 0.7rem;
        padding: 6px 10px;
    }
    .oo-legend-footer-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    .oo-legend-divider {
        width: 100%;
        height: 1px;
    }
    .oo-legend-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const CAN_DELETE = {{ auth()->check() && auth()->user()->can('original-order-files-delete') ? 'true' : 'false' }};
    let deleteCtx = { url: null, container: null, filename: '' };
    const MAX_FILES = 8;

    function getBootstrapModal(el) {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            return {
                show: () => window.bootstrap.Modal.getOrCreateInstance(el).show(),
                hide: () => {
                    const inst = window.bootstrap.Modal.getInstance(el) || window.bootstrap.Modal.getOrCreateInstance(el);
                    inst.hide();
                }
            };
        }
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            return { show: () => window.jQuery(el).modal('show'), hide: () => window.jQuery(el).modal('hide') };
        }
        return {
            show: () => {
                el.classList.add('show');
                el.style.display = 'block';
                document.body.classList.add('modal-open');
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-fallback-backdrop', '1');
                document.body.appendChild(backdrop);
            },
            hide: () => {
                el.classList.remove('show');
                el.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop[data-fallback-backdrop="1"]').forEach(b => b.remove());
            }
        };
    }

    function formatBytes(bytes) {
        if (!bytes && bytes !== 0) return '';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = bytes === 0 ? 0 : Math.floor(Math.log(bytes) / Math.log(1024));
        const val = (bytes / Math.pow(1024, i)).toFixed( i === 0 ? 0 : 1 );
        return `${val} ${sizes[i]}`;
    }

    function renderFiles(container, files) {
        container.innerHTML = '';
        if (!files || files.length === 0) {
            container.innerHTML = '<div class="col-12 text-muted small">@lang('No files uploaded yet.')</div>';
            container.dataset.count = 0;
            const wrapper = container.closest('.oo-process-files');
            if (wrapper) {
                const input = wrapper.querySelector('[data-file-input]');
                const btn = wrapper.querySelector('[data-upload-btn]');
                if (input) input.disabled = false;
                if (btn) btn.disabled = false;
            }
            return;
        }

        const maxFiles = MAX_FILES;
        container.dataset.count = files.length;
        const wrapperForLimit = container.closest('.oo-process-files');
        if (wrapperForLimit) {
            const input = wrapperForLimit.querySelector('[data-file-input]');
            const btn = wrapperForLimit.querySelector('[data-upload-btn]');
            const over = files.length >= maxFiles;
            if (input) input.disabled = over;
            if (btn) btn.disabled = over;
        }

        files.forEach(f => {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-4 col-6 mb-2';
            const isImage = f.mime_type && f.mime_type.startsWith('image/');
            const isPdf = f.extension && f.extension.toLowerCase() === 'pdf';
            let preview = '';
            if (isImage) {
                preview = `<a href="${f.public_url}" target="_blank"><img src="${f.public_url}" class="img-fluid rounded" alt="${f.original_name}" style="max-height: 80px; object-fit: cover;"></a>`;
            } else if (isPdf) {
                preview = `<a href="${f.public_url}" target="_blank" class="btn btn-outline-secondary btn-sm w-100"><i class="ti ti-file-type-pdf"></i> PDF</a>`;
            } else {
                preview = `<a href="${f.public_url}" target="_blank" class="btn btn-outline-secondary btn-sm w-100"><i class="ti ti-file"></i> ${f.extension || ''}</a>`;
            }
            const deleteBtnHtml = CAN_DELETE
                ? `<button type="button" class="btn btn-link btn-sm text-danger p-0" data-delete data-id="${f.id}" data-name="${f.original_name}"><i class="ti ti-trash"></i></button>`
                : '';
            col.innerHTML = `
                <div class="border rounded p-2 h-100 d-flex flex-column bg-white">
                    <div class="flex-grow-1 mb-2 text-center" style="min-height:60px">${preview}</div>
                    <div class="small text-truncate" title="${f.original_name}">${f.original_name}</div>
                    <div class="text-muted small">${formatBytes(f.size)}</div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <a href="${f.public_url}" target="_blank" class="btn btn-link btn-sm p-0"><i class="ti ti-external-link"></i></a>
                        <button type="button" class="btn btn-link btn-sm p-0" data-copy data-url="${f.public_url}"><i class="ti ti-copy"></i></button>
                        ${deleteBtnHtml}
                    </div>
                </div>`;
            container.appendChild(col);
        });

        container.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const url = btn.getAttribute('data-url');
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(url);
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = url; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                    }
                    btn.innerHTML = '<i class="ti ti-check text-success"></i>';
                    setTimeout(() => btn.innerHTML = '<i class="ti ti-copy"></i>', 1500);
                } catch (e) { alert('@lang('Could not copy link')'); }
            });
        });

        container.querySelectorAll('[data-delete]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const fileId = btn.getAttribute('data-id');
                const base = container.getAttribute('data-index-url').replace(/\/$/, '');
                const url = `${base}/${fileId}`;
                deleteCtx = { url, container, filename: btn.getAttribute('data-name') || '' };
                const modalEl = document.getElementById('processFileDeleteModal');
                modalEl.querySelector('[data-delete-filename]').textContent = deleteCtx.filename;
                const modal = getBootstrapModal(modalEl);
                modal.show();
            });
        });
    }

    async function loadFiles(container) {
        const url = container.getAttribute('data-index-url');
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
            const data = await res.json();
            renderFiles(container, data.data || []);
        } catch (e) {
            container.innerHTML = '<div class="col-12 text-danger small">@lang('Error loading files')</div>';
        }
    }

    document.querySelectorAll('[data-files-container]').forEach(container => {
        loadFiles(container);
        const wrapper = container.closest('.oo-process-files');
        const input = wrapper.querySelector('[data-file-input]');
        const btn = wrapper.querySelector('[data-upload-btn]');
        if (btn && input) {
            btn.addEventListener('click', async () => {
                const count = parseInt(container.dataset.count || '0', 10);
                if (count >= MAX_FILES) {
                    alert('@lang('Maximum of 8 files per process reached')');
                    return;
                }
                if (!input.files || input.files.length === 0) {
                    alert('@lang('Select a file first')');
                    return;
                }
                const allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
                const files = Array.from(input.files).filter(f => allowed.includes(f.type) || /\.(jpg|jpeg|png|gif|webp|pdf)$/i.test(f.name));
                if (files.length === 0) { alert('@lang('Only images and PDF files are allowed')'); return; }
                const remaining = Math.max(0, MAX_FILES - count);
                const toUpload = files.slice(0, remaining);
                if (files.length > remaining) {
                    alert('@lang('Maximum of 8 files per process reached')');
                }
                const url = input.getAttribute('data-upload-url');
                try {
                    btn.disabled = true; input.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    for (const file of toUpload) {
                        const form = new FormData();
                        form.append('file', file);
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: form
                        });
                        if (!res.ok) {
                            const t = await res.text();
                            throw new Error(t || 'Upload failed');
                        }
                    }
                    input.value = '';
                    await loadFiles(container);
                } catch (e) {
                    alert('@lang('Upload failed')');
                } finally {
                    btn.disabled = false; input.disabled = false; btn.innerHTML = '<i class="ti ti-upload"></i>';
                }
            });
        }
    });

    const confirmBtn = document.getElementById('confirmDeleteProcessFile');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            if (!deleteCtx.url || !deleteCtx.container) return;
            try {
                const res = await fetch(deleteCtx.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Delete failed');
                loadFiles(deleteCtx.container);
            } catch (e) {
                alert('@lang('Error deleting file')');
            } finally {
                const modalEl = document.getElementById('processFileDeleteModal');
                const modal = getBootstrapModal(modalEl);
                modal.hide();
                deleteCtx = { url: null, container: null, filename: '' };
            }
        });
    }

    (function(){
        const modalEl = document.getElementById('processFileDeleteModal');
        if (!modalEl) return;
        const modal = getBootstrapModal(modalEl);
        const attach = (sel) => {
            modalEl.querySelectorAll(sel).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    modal.hide();
                });
            });
        };
        attach('[data-dismiss="modal"]');
        attach('[data-bs-dismiss="modal"]');
        attach('.btn-close');
        modalEl.addEventListener('click', (e) => {
            if (e.target === modalEl) {
                modal.hide();
            }
        });
    })();
});
</script>
@endpush

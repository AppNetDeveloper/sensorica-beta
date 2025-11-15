@extends('layouts.admin')

@section('title', __('Maintenances') . ' - ' . $customer->name)
@section('page-title', __('Maintenances'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Maintenances') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Maintenances') }}</h5>
    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
      <div class="btn-group btn-group-sm me-2" role="group" aria-label="Dashboard">
        <a href="{{ route('customers.maintenances.dashboard', $customer->id) }}" class="btn btn-outline-info" data-bs-toggle="tooltip" title="{{ __('Dashboard de Métricas') }}">
          <i class="ti ti-chart-line me-1"></i><span class="d-none d-sm-inline">{{ __('Dashboard') }}</span>
        </a>
      </div>
      <div class="btn-group btn-group-sm me-2" role="group" aria-label="Catálogos">
        <a href="{{ route('customers.maintenance-causes.index', $customer->id) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="{{ __('Operaciones de mantenimiento') }}">
          <i class="ti ti-flag-3 me-1"></i><span class="d-none d-sm-inline">{{ __('Operaciones') }}</span>
        </a>
        <a href="{{ route('customers.maintenance-parts.index', $customer->id) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="{{ __('Repuestos de mantenimiento') }}">
          <i class="ti ti-tools me-1"></i><span class="d-none d-sm-inline">{{ __('Repuestos') }}</span>
        </a>
      </div>
      <div class="btn-group btn-group-sm me-2" role="group" aria-label="Exportar">
        <button type="button" class="btn btn-outline-success" id="btn-export-excel" data-bs-toggle="tooltip" title="{{ __('Exportar a Excel') }}">
          <i class="ti ti-file-spreadsheet me-1"></i><span class="d-none d-sm-inline">Excel</span>
        </button>
        <button type="button" class="btn btn-outline-danger" id="btn-export-pdf" data-bs-toggle="tooltip" title="{{ __('Exportar a PDF') }}">
          <i class="ti ti-file-type-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
        </button>
      </div>
      <div class="btn-group btn-group-sm me-2" role="group" aria-label="Crear">
        @can('maintenance-create')
        <a href="{{ route('customers.maintenances.create', $customer->id) }}" class="btn btn-primary" data-bs-toggle="tooltip" title="{{ __('Crear mantenimiento') }}">
          <i class="ti ti-plus me-1"></i><span class="d-none d-sm-inline">{{ __('Create') }}</span>
        </a>
        @endcan
      </div>
      @php($aiUrl = config('services.ai.url'))
      @php($aiToken = config('services.ai.token'))
      @if(!empty($aiUrl) && !empty($aiToken))
        <div class="btn-group btn-group-sm" role="group" aria-label="IA">
          <!-- AI Dropdown Button -->
          <button type="button" class="btn btn-dark dropdown-toggle position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Análisis con IA') }}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; font-weight: 600;">
            <i class="bi bi-stars me-1"></i>
            <span class="d-none d-sm-inline">{{ __('Análisis IA') }}</span>
            <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65em;">PRO</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 350px; max-height: 600px; overflow-y: auto;">
            <li><h6 class="dropdown-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: -0.5rem -0.5rem 0.5rem -0.5rem; padding: 0.75rem 1rem;">
              <i class="fas fa-brain me-2"></i>{{ __("Análisis Inteligente de Mantenimientos") }}
              <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7em;">PRO</span>
            </h6></li>

            <!-- SECCIÓN 1: Análisis de Tiempos -->
            <li><h6 class="dropdown-header text-primary"><i class="fas fa-clock me-1"></i> {{ __("Tiempos de Mantenimiento") }}</h6></li>
            <li><a class="dropdown-item" href="#" data-analysis="downtime-analysis">
              <i class="fas fa-stopwatch text-danger me-2"></i>{{ __("Análisis de Tiempo de Paro") }}
            </a></li>
            <li><a class="dropdown-item" href="#" data-analysis="response-time">
              <i class="fas fa-hourglass-start text-warning me-2"></i>{{ __("Tiempo de Respuesta") }}
            </a></li>
            <li><a class="dropdown-item" href="#" data-analysis="repair-duration">
              <i class="fas fa-tools text-info me-2"></i>{{ __("Duración de Reparaciones") }}
            </a></li>

            <li><hr class="dropdown-divider"></li>

            <!-- SECCIÓN 2: Análisis por Causa -->
            <li><h6 class="dropdown-header text-warning"><i class="fas fa-exclamation-circle me-1"></i> {{ __("Causas y Frecuencia") }}</h6></li>
            <li><a class="dropdown-item" href="#" data-analysis="top-causes">
              <i class="fas fa-chart-bar text-warning me-2"></i>{{ __("Causas Principales") }}
            </a></li>
            <li><a class="dropdown-item" href="#" data-analysis="recurring-failures">
              <i class="fas fa-redo text-danger me-2"></i>{{ __("Fallas Recurrentes") }}
            </a></li>
            <li><a class="dropdown-item" href="#" data-analysis="critical-failures">
              <i class="fas fa-bomb text-danger me-2"></i>{{ __("Fallas Críticas") }}
            </a></li>

            <li><hr class="dropdown-divider"></li>

            <!-- SECCIÓN 3: Análisis por Línea/Equipo -->
            <li><h6 class="dropdown-header text-success"><i class="fas fa-industry me-1"></i> {{ __("Por Línea/Equipo") }}</h6></li>
            <li><a class="dropdown-item" href="#" data-analysis="by-production-line">
              <i class="fas fa-cogs text-success me-2"></i>{{ __("Por Línea de Producción") }}
            </a></li>
            <li><a class="dropdown-item" href="#" data-analysis="equipment-reliability">
              <i class="fas fa-check-circle text-success me-2"></i>{{ __("Fiabilidad de Equipos") }}
            </a></li>

            <li><hr class="dropdown-divider"></li>

            <!-- SECCIÓN 4: Análisis de Recursos -->
            <li><h6 class="dropdown-header text-info"><i class="fas fa-users me-1"></i> {{ __("Recursos y Repuestos") }}</h6></li>
            <li><a class="dropdown-item" href="#" data-analysis="parts-consumption">
              <i class="fas fa-boxes text-info me-2"></i>{{ __("Consumo de Repuestos") }}
            </a></li>
            <li><a class="dropdown-item" href="#" data-analysis="mechanic-performance">
              <i class="fas fa-user-cog text-primary me-2"></i>{{ __("Rendimiento de Mecánicos") }}
            </a></li>

            <li><hr class="dropdown-divider"></li>

            <!-- SECCIÓN 5: Análisis Completo -->
            <li><h6 class="dropdown-header text-dark"><i class="fas fa-layer-group me-1"></i> {{ __("Análisis Completo") }}</h6></li>
            <li><a class="dropdown-item" href="#" data-analysis="full">
              <i class="fas fa-brain text-dark me-2"></i>{{ __("Análisis Integral") }}
            </a></li>
          </ul>
        </div>
      @endif
    </div>
  </div>
  <div class="card-body">
    <form method="GET" action="{{ route('customers.maintenances.index', $customer->id) }}" class="row g-2 align-items-end mb-3">
      <div class="col-md-4">
        <label class="form-label">{{ __('Production Line') }}</label>
        <select name="production_line_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($lines as $line)
            <option value="{{ $line->id }}" {{ (string)($lineId ?? '') === (string)$line->id ? 'selected' : '' }}>{{ $line->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Trabajador') }}</label>
        <select name="operator_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($operators as $op)
            <option value="{{ $op->id }}" {{ (string)($operatorId ?? '') === (string)$op->id ? 'selected' : '' }}>{{ $op->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Mecanico') }}</label>
        <select name="user_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ (string)($userId ?? '') === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Created from') }}</label>
        <input type="date" name="created_from" value="{{ $createdFrom ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Created to') }}</label>
        <input type="date" name="created_to" value="{{ $createdTo ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Start from') }}</label>
        <input type="date" name="start_from" value="{{ $startFrom ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Start to') }}</label>
        <input type="date" name="start_to" value="{{ $startTo ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('End from') }}</label>
        <input type="date" name="end_from" value="{{ $endFrom ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('End to') }}</label>
        <input type="date" name="end_to" value="{{ $endTo ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-2 d-flex">
        <button type="submit" class="btn btn-outline-primary w-100">{{ __('Filter') }}</button>
      </div>
    </form>

    <!-- Leyenda de Estados -->
    <div class="alert alert-light border mb-3" role="alert">
      <div class="d-flex align-items-center mb-2">
        <i class="ti ti-info-circle me-2"></i>
        <strong>{{ __('Leyenda de Estados') }}:</strong>
      </div>
      <div class="d-flex flex-wrap gap-3">
        <div class="d-flex align-items-center">
          <span class="badge bg-secondary me-2"><i class="ti ti-clock me-1"></i>{{ __('Pendiente') }}</span>
          <small class="text-muted">{{ __('Sin iniciar') }}</small>
        </div>
        <div class="d-flex align-items-center">
          <span class="badge bg-warning me-2"><i class="ti ti-tool me-1"></i>{{ __('En Curso') }}</span>
          <small class="text-muted">{{ __('< 24 horas') }}</small>
        </div>
        <div class="d-flex align-items-center">
          <span class="badge bg-danger me-2"><i class="ti ti-alert-triangle me-1"></i>{{ __('En Curso') }}</span>
          <small class="text-muted">{{ __('> 24 horas (crítico)') }}</small>
        </div>
        <div class="d-flex align-items-center">
          <span class="badge bg-success me-2"><i class="ti ti-check me-1"></i>{{ __('Finalizado') }}</span>
          <small class="text-muted">{{ __('Completado') }}</small>
        </div>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-3" id="maint-summary">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">{{ __('Stopped before Start') }}</div>
                <div class="fs-4 fw-bold" id="sum_stopped">00:00:00</div>
              </div>
              <i class="ti ti-player-pause fs-2 text-warning"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">{{ __('Downtime') }}</div>
                <div class="fs-4 fw-bold" id="sum_downtime">00:00:00</div>
              </div>
              <i class="ti ti-clock fs-2 text-danger"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">{{ __('Total Time') }}</div>
                <div class="fs-4 fw-bold" id="sum_total">00:00:00</div>
              </div>
              <i class="ti ti-sum fs-2 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="maintenancesTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Production Line') }}</th>
            <th>{{ __('Created') }}</th>
            <th>{{ __('Start') }}</th>
            <th>{{ __('End') }}</th>
            <th>{{ __('Stopped before Start') }}</th>
            <th>{{ __('Downtime') }}</th>
            <th>{{ __('Total Time') }}</th>
            <th>{{ __('Causes') }}</th>
            <th>{{ __('Parts') }}</th>
            <th>{{ __('Trabajador') }}</th>
            <th>{{ __('Mecanico') }}</th>
            <th>{{ __('Annotations') }}</th>
            <th>{{ __('Operator Annotation') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    
    <!-- Details Modal -->
    <div class="modal fade" id="maintenanceDetailsModal" tabindex="-1" aria-labelledby="maintenanceDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="maintenanceDetailsModalLabel">{{ __('Detalles de mantenimiento') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold">{{ __('Annotations') }}</label>
                <div id="md-annotations" class="form-control" style="min-height: 60px"></div>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">{{ __('Operator Annotation') }}</label>
                <div id="md-operator-annotations" class="form-control" style="min-height: 60px"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('Production Line') }}</label>
                <div id="md-production-line" class="form-control"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('Trabajador') }}</label>
                <div id="md-operator-name" class="form-control"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('Mecanico') }}</label>
                <div id="md-user-name" class="form-control"></div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">{{ __('Causes') }}</label>
                <div id="md-causes" class="form-control" style="min-height: 60px"></div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">{{ __('Parts') }}</label>
                <div id="md-parts" class="form-control" style="min-height: 60px"></div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Edición de Prompt -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable" style="max-width: 80%; width: 80%;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="aiPromptModalTitle"><i class="fas fa-robot me-2"></i>{{ __('Análisis IA') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <h6 class="mb-2 text-primary"><i class="fas fa-database me-1"></i>{{ __('Datos que enviamos a la IA') }}</h6>
              <ul class="mb-0 ps-3">
                <li><strong>{{ __('Filtros') }}:</strong> {{ __('rango de fechas y filtros activos en la tabla') }}</li>
                <li><strong>{{ __('Datos detallados') }}:</strong> {{ __('hasta 200 registros de mantenimiento en formato CSV') }}</li>
                <li><strong>{{ __('Campos') }}:</strong> {{ __('ID, Línea, Fechas, Tiempos, Causas, Repuestos, Personal, Anotaciones') }}</li>
              </ul>
            </div>
            <label class="form-label fw-bold">{{ __('Prompt a enviar (puedes editarlo):') }}</label>
            <textarea class="form-control font-monospace" id="aiPrompt" rows="15" style="font-size: 0.85rem;" placeholder="{{ __('Selecciona un tipo de análisis del dropdown...') }}"></textarea>
            <div class="alert alert-warning mt-2 mb-0">
              <small>
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>{{ __('Importante:') }}</strong> {{ __('El prompt incluye los datos en formato CSV. NO elimines la sección de datos o la IA no podrá realizar el análisis correctamente.') }}
              </small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">{{ __('Restaurar prompt original') }}</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
            <button type="button" class="btn btn-primary" id="btn-ai-send">
              <i class="bi bi-stars me-1"></i>{{ __('Enviar a IA') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de procesamiento -->
    <div class="modal fade" id="aiProcessingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="aiProcessingModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">{{ __('Procesando...') }}</span>
            </div>
            <h5 class="mb-2">{{ __('Analizando datos con IA...') }}</h5>
            <p class="text-muted mb-0" id="aiProcessingStatus">{{ __('Preparando análisis') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de resultados -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-labelledby="aiResultModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="modal-title text-white" id="aiResultModalLabel">
              <i class="bi bi-stars me-2"></i>{{ __('Análisis IA') }}: <span id="aiAnalysisTitle"></span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div class="p-3 border-bottom bg-light">
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" id="btnCopyResult" title="{{ __('Copiar al portapapeles') }}">
                  <i class="fas fa-copy"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnZoomIn" title="{{ __('Aumentar tamaño') }}">
                  <i class="fas fa-search-plus"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnZoomOut" title="{{ __('Reducir tamaño') }}">
                  <i class="fas fa-search-minus"></i>
                </button>
              </div>
            </div>
            <div id="aiResultContent" class="p-4" style="font-size: 100%; line-height: 1.6;">
              <!-- El resultado del análisis se mostrará aquí -->
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cerrar') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>
<style>
  /* Bigger summary cards */
  #maint-summary .card {
    min-height: 190px;
    border-radius: 14px;
  }
  #maint-summary .card .card-body { padding: 1.5rem 1.75rem; }
  /* Label text */
  #maint-summary .text-muted.small { font-size: 1.35rem !important; font-weight: 700; }
  /* Main number */
  #maint-summary .fs-4.fw-bold { font-size: 3.8rem !important; line-height: 1.05; }
  /* Icon */
  #maint-summary i { font-size: 3.8rem !important; }

  @media (min-width: 1200px) {
    #maint-summary .card { min-height: 210px; }
    #maint-summary .fs-4.fw-bold { font-size: 4.2rem !important; }
    #maint-summary i { font-size: 4.2rem !important; }
  }

  @media (max-width: 575.98px) {
    #maint-summary .card { min-height: 170px; }
    #maint-summary .fs-4.fw-bold { font-size: 3rem !important; }
    #maint-summary .text-muted.small { font-size: 1.25rem !important; }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
  $(function(){
    // Init Bootstrap tooltips for header buttons
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
    const AI_URL = @json(config('services.ai.url'));
    const AI_TOKEN = @json(config('services.ai.token'));
    const table = $('#maintenancesTable').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      order: [[3, 'desc']], // created_at desc
      columnDefs: [
        { targets: -1, responsivePriority: 1 }, // Actions column highest priority
        { targets: 0, responsivePriority: 100 }, // ID lowest priority
        { targets: 1, responsivePriority: 2 }, // Status badge
        { targets: [3,4,5], responsivePriority: 3 }, // Created/Start/End
        { targets: [6,7,8], responsivePriority: 4 } // Stopped/Downtime/Total
      ],
      ajax: {
        url: "{{ route('customers.maintenances.index', $customer->id) }}",
        data: function(d) {
          const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
          d.created_from = form.created_from.value;
          d.created_to = form.created_to.value;
          d.production_line_id = form.production_line_id.value;
          d.operator_id = form.operator_id.value;
          d.user_id = form.user_id.value;
          d.start_from = form.start_from.value;
          d.start_to = form.start_to.value;
          d.end_from = form.end_from.value;
          d.end_to = form.end_to.value;
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
        { data: 'production_line', name: 'production_line', orderable: false, searchable: true },
        { data: 'created_at', name: 'created_at' },
        { data: 'start_datetime', name: 'start_datetime' },
        { data: 'end_datetime', name: 'end_datetime' },
        { data: 'stopped_formatted', name: 'stopped_formatted', orderable: false, searchable: false },
        { data: 'downtime_formatted', name: 'downtime_formatted', orderable: false, searchable: false },
        { data: 'total_time_formatted', name: 'total_time_formatted', orderable: false, searchable: false },
        { data: 'causes_list', name: 'causes_list', orderable: false, searchable: true },
        { data: 'parts_list', name: 'parts_list', orderable: false, searchable: true },
        { data: 'operator_name', name: 'operator_name', orderable: false, searchable: true },
        { data: 'user_name', name: 'user_name', orderable: false, searchable: true },
        { data: 'annotations_short', name: 'annotations', orderable: false, searchable: true },
        { data: 'operator_annotations_short', name: 'operator_annotations', orderable: false, searchable: true },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ]
    });

    // Collect current table page data and active filters
    function collectCurrentMaintData() {
      const rows = table.rows({ page: 'current' }).nodes();
      const dataOut = [];
      table.rows({ page: 'current' }).every(function(rowIdx){
        const rowData = this.data();
        const rowNode = rows[rowIdx];
        const detailsBtn = rowNode ? rowNode.querySelector('.btn-maint-details') : null;
        const details = {
          annotations: detailsBtn ? (detailsBtn.getAttribute('data-annotations') || '') : '',
          operator_annotations: detailsBtn ? (detailsBtn.getAttribute('data-operator-annotations') || '') : '',
          causes: detailsBtn ? (detailsBtn.getAttribute('data-causes') || '') : '',
          parts: detailsBtn ? (detailsBtn.getAttribute('data-parts') || '') : ''
        };
        dataOut.push({
          id: rowData.id,
          production_line: rowData.production_line,
          production_line_name: rowData.production_line,
          created_at: rowData.created_at,
          start_datetime: rowData.start_datetime,
          end_datetime: rowData.end_datetime,
          stopped: rowData.stopped_formatted,
          downtime: rowData.downtime_formatted,
          total_time: rowData.total_time_formatted,
          causes_list: rowData.causes_list,
          parts_list: rowData.parts_list,
          operator_name: rowData.operator_name,
          operator_role: 'Maquinista',
          user_name: rowData.user_name,
          user_role: 'Mecánico',
          annotations_short: rowData.annotations_short,
          operator_annotations_short: rowData.operator_annotations_short,
          details: details
        });
      });
      const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
      const filters = {
        created_from: form?.created_from?.value || '',
        created_to: form?.created_to?.value || '',
        production_line_id: form?.production_line_id?.value || '',
        operator_id: form?.operator_id?.value || '',
        user_id: form?.user_id?.value || '',
        start_from: form?.start_from?.value || '',
        start_to: form?.start_to?.value || '',
        end_from: form?.end_from?.value || '',
        end_to: form?.end_to?.value || ''
      };
      return { rows: dataOut, filters };
    }

    // Load totals
    function loadTotals() {
      const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
      const params = new URLSearchParams({
        totals: 1,
        created_from: form.created_from.value || '',
        created_to: form.created_to.value || '',
        production_line_id: form.production_line_id.value || '',
        operator_id: form.operator_id.value || '',
        user_id: form.user_id.value || '',
        start_from: form.start_from.value || '',
        start_to: form.start_to.value || '',
        end_from: form.end_from.value || '',
        end_to: form.end_to.value || ''
      });
      fetch("{{ route('customers.maintenances.index', $customer->id) }}?" + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.json())
        .then(data => {
          document.getElementById('sum_stopped').textContent = data.stopped_before_start || '00:00:00';
          document.getElementById('sum_downtime').textContent = data.downtime || '00:00:00';
          document.getElementById('sum_total').textContent = data.total_time || '00:00:00';
        })
        .catch(() => {
          document.getElementById('sum_stopped').textContent = '—';
          document.getElementById('sum_downtime').textContent = '—';
          document.getElementById('sum_total').textContent = '—';
        });
    }

    loadTotals();

    // Filtros: recargar DataTable sin navegar
    const filterForm = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
    filterForm.addEventListener('submit', function(e){
      e.preventDefault();
      table.ajax.reload();
      loadTotals();
    });

    // Fill modal on Details button click (event delegation for DataTables)
    $('#maintenancesTable').on('click', '.btn-maint-details', function(){
      const btn = this;
      const annotations = btn.getAttribute('data-annotations') || '';
      const opAnn = btn.getAttribute('data-operator-annotations') || '';
      const causes = btn.getAttribute('data-causes') || '';
      const parts = btn.getAttribute('data-parts') || '';
      let rowData = table.row($(btn).closest('tr')).data();
      if (!rowData) {
        rowData = table.row($(btn).closest('tr').prev()).data();
      }
      const lineName = rowData?.production_line || btn.getAttribute('data-production-line') || '';
      const operatorName = rowData?.operator_name || btn.getAttribute('data-operator-name') || '';
      const userName = rowData?.user_name || btn.getAttribute('data-user-name') || '';
      document.getElementById('md-annotations').textContent = annotations || '-';
      document.getElementById('md-operator-annotations').textContent = opAnn || '-';
      document.getElementById('md-production-line').textContent = lineName || '-';
      document.getElementById('md-operator-name').textContent = operatorName || '-';
      document.getElementById('md-user-name').textContent = userName || '-';
      document.getElementById('md-causes').textContent = causes || '-';
      document.getElementById('md-parts').textContent = parts || '-';
    });

    // ========================================
    // SISTEMA DE ANÁLISIS IA CON PROMPTS CONFIGURADOS
    // ========================================

    // Configuración de prompts por tipo de análisis
    const analysisPrompts = {
      'downtime-analysis': {
        title: 'Análisis de Tiempo de Paro',
        prompt: `Eres un experto en análisis de tiempos de paro (downtime) en manufactura. Analiza los tiempos de paro de las máquinas para identificar patrones y oportunidades de mejora.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Top 5 paros más largos**: Identifica los 5 mantenimientos con mayor tiempo de paro total. Incluye ID, Línea, Tiempo total y causas principales.

2. **Distribución estadística del tiempo de paro**:
   - Media y mediana del tiempo total de paro
   - Tiempo que el 90% de mantenimientos no supera (casos lentos típicos)
   - Tiempo que el 95% de mantenimientos no supera (casos extremos)
   - Identifica mantenimientos con tiempos excepcionales que requieren atención especial

3. **Análisis por línea de producción**:
   - Líneas con mayor tiempo acumulado de paro
   - Frecuencia de paros por línea
   - Líneas con mayor impacto en producción

4. **Recomendaciones**: 3 acciones concretas para reducir el tiempo de paro en al menos 20%.

FORMATO DE SALIDA:
Usa secciones numeradas con métricas cuantificadas. Presenta tiempos en formato legible (horas y minutos).`
      },
      'response-time': {
        title: 'Tiempo de Respuesta',
        prompt: `Eres un analista de eficiencia de mantenimiento. Analiza el tiempo de respuesta desde que se reporta un problema hasta que comienza la reparación.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Estadísticas de tiempo de respuesta** (Fecha_Creacion a Fecha_Inicio):
   - Tiempo promedio de respuesta
   - Mediana del tiempo de respuesta
   - Casos con respuesta más rápida y más lenta

2. **Análisis por turno/horario**:
   - ¿Hay diferencias en tiempo de respuesta según hora del día?
   - Identifica períodos con respuesta más lenta

3. **Por mecánico**:
   - Mecánicos con mejor tiempo de respuesta promedio
   - Variabilidad en tiempos de respuesta

4. **Recomendaciones**: Mejoras para reducir el tiempo de respuesta promedio.

FORMATO DE SALIDA:
Presenta tiempos en formato legible. Incluye gráficas conceptuales si es relevante.`
      },
      'repair-duration': {
        title: 'Duración de Reparaciones',
        prompt: `Eres un ingeniero de mantenimiento. Analiza la duración real de las reparaciones (desde inicio hasta fin de la intervención).

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Distribución de tiempos de reparación**:
   - Media y mediana del tiempo de reparación
   - Reparaciones más rápidas vs más lentas
   - Tiempo que el 90% y 95% de reparaciones no supera

2. **Por tipo de causa**:
   - Causas que requieren más tiempo de reparación
   - Causas con reparaciones más rápidas
   - Variabilidad por tipo de falla

3. **Eficiencia de mecánicos**:
   - Tiempos promedio por mecánico
   - Consistencia en tiempos de reparación

4. **Recomendaciones**: Acciones para reducir tiempos de reparación en un 15%.

FORMATO DE SALIDA:
Usa formato legible para tiempos. Cuantifica impactos y prioriza recomendaciones.`
      },
      'top-causes': {
        title: 'Causas Principales',
        prompt: `Eres un analista de confiabilidad. Identifica las causas principales de fallas y su impacto en la operación.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Top 10 causas por frecuencia**:
   - Número de ocurrencias de cada causa
   - Porcentaje del total de mantenimientos
   - Tendencia (aumentando/disminuyendo)

2. **Top 10 causas por impacto (tiempo acumulado)**:
   - Tiempo total de paro causado
   - Tiempo promedio por ocurrencia
   - Comparación frecuencia vs impacto

3. **Análisis Pareto**:
   - ¿Qué causas representan el 80% del tiempo de paro?
   - Priorización de causas a atacar

4. **Por línea de producción**:
   - Causas específicas por línea
   - Líneas con mayor diversidad de causas

5. **Recomendaciones**: 5 causas prioritarias a eliminar con plan de acción.

FORMATO DE SALIDA:
Usa tablas claras. Cuantifica frecuencias y tiempos. Prioriza por impacto.`
      },
      'recurring-failures': {
        title: 'Fallas Recurrentes',
        prompt: `Eres un experto en confiabilidad de equipos. Identifica fallas que se repiten con frecuencia y patrones de recurrencia.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Fallas más recurrentes**:
   - Combinación Línea + Causa que más se repite
   - Frecuencia de repetición
   - Tiempo promedio entre fallas

2. **Patrones temporales**:
   - ¿Las fallas se repiten en intervalos predecibles?
   - Fallas que ocurren en días/horas específicas
   - Correlación con turnos u operadores

3. **Análisis de causas raíz**:
   - ¿Las reparaciones son efectivas o el problema regresa?
   - Repuestos que se cambian repetidamente
   - Líneas con más fallas recurrentes

4. **Recomendaciones**: Acciones preventivas para las 5 fallas más recurrentes.

FORMATO DE SALIDA:
Identifica patrones claros. Usa ejemplos específicos con IDs. Cuantifica recurrencia.`
      },
      'critical-failures': {
        title: 'Fallas Críticas',
        prompt: `Eres un gerente de mantenimiento. Identifica las fallas más críticas que causan mayor impacto en la operación.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Top 10 fallas críticas por tiempo de paro**:
   - ID, Línea, Fecha, Tiempo total
   - Causas y repuestos utilizados
   - ¿Qué las hizo tan críticas?

2. **Análisis de severidad**:
   - Fallas con tiempo > tiempo que el 95% de casos no supera
   - Impacto acumulado de fallas críticas
   - Porcentaje del tiempo total de paro

3. **Prevención de fallas críticas**:
   - Patrones comunes en fallas críticas
   - ¿Son predecibles?
   - Señales de advertencia

4. **Plan de contingencia**:
   - Repuestos críticos a tener en stock
   - Procedimientos de respuesta rápida

5. **Recomendaciones**: 3 acciones para prevenir fallas críticas futuras.

FORMATO DE SALIDA:
Enfócate en impacto y urgencia. Usa datos específicos. Prioriza acciones.`
      },
      'by-production-line': {
        title: 'Por Línea de Producción',
        prompt: `Eres un analista de producción. Compara el desempeño de mantenimiento entre diferentes líneas de producción.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Comparativa de líneas**:
   - Ranking de líneas por:
     * Número de mantenimientos
     * Tiempo total de paro acumulado
     * Tiempo promedio por mantenimiento
     * Frecuencia de fallas

2. **Análisis de causas por línea**:
   - Causas principales en cada línea
   - Líneas con mayor diversidad de problemas
   - Causas únicas vs compartidas

3. **Confiabilidad por línea**:
   - Líneas más/menos confiables
   - Tiempo entre fallas (MTBF aproximado)
   - Tendencias de mejora o deterioro

4. **Consumo de recursos por línea**:
   - Repuestos más utilizados por línea
   - Tiempo de mecánicos dedicado

5. **Recomendaciones**: Líneas prioritarias para inversión en mejoras. Mejores prácticas de líneas confiables.

FORMATO DE SALIDA:
Usa tablas comparativas. Identifica líneas problemáticas y ejemplares.`
      },
      'equipment-reliability': {
        title: 'Fiabilidad de Equipos',
        prompt: `Eres un ingeniero de confiabilidad (Reliability Engineer). Evalúa la fiabilidad de los equipos y líneas de producción.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Métricas de confiabilidad**:
   - Tasa de fallas por línea
   - Disponibilidad estimada (uptime)
   - Tiempo medio entre fallas (MTBF)
   - Tiempo medio de reparación (MTTR)

2. **Análisis de tendencias**:
   - ¿La confiabilidad mejora o empeora en el tiempo?
   - Líneas con tendencia positiva/negativa
   - Efectividad de mantenimientos preventivos vs correctivos

3. **Modos de falla dominantes**:
   - Fallas más comunes por equipo
   - Componentes que fallan repetidamente
   - Edad/desgaste evidente

4. **Benchmarking**:
   - Líneas con mejor confiabilidad
   - Factores de éxito
   - Comparación con estándares de la industria (si aplica)

5. **Plan de mejora de confiabilidad**:
   - Intervenciones prioritarias
   - ROI estimado de mejoras
   - Metas de MTBF y disponibilidad

FORMATO DE SALIDA:
Usa métricas técnicas claras. Cuantifica confiabilidad. Establece metas.`
      },
      'parts-consumption': {
        title: 'Consumo de Repuestos',
        prompt: `Eres un gerente de almacén de repuestos. Analiza el consumo de repuestos para optimizar inventarios y costos.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Top 20 repuestos más utilizados**:
   - Frecuencia de uso
   - En qué líneas se usan más
   - Para qué causas se utilizan

2. **Análisis de criticidad de repuestos**:
   - Repuestos críticos (uso frecuente + alto impacto)
   - Repuestos de bajo uso
   - Repuestos a tener en stock permanente

3. **Patrones de consumo**:
   - Tendencias en consumo (aumentando/disminuyendo)
   - Repuestos que se usan juntos frecuentemente
   - Consumo por línea de producción

4. **Costos y optimización**:
   - Repuestos con mayor rotación
   - Oportunidades de estandarización
   - Repuestos a considerar para mantenimiento preventivo

5. **Recomendaciones**: Plan de inventario óptimo. Niveles mínimos/máximos sugeridos.

FORMATO DE SALIDA:
Usa tablas de frecuencia. Identifica repuestos críticos. Cuantifica uso.`
      },
      'mechanic-performance': {
        title: 'Rendimiento de Mecánicos',
        prompt: `Eres un supervisor de mantenimiento. Analiza el rendimiento y eficiencia del equipo de mecánicos.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Productividad por mecánico**:
   - Número de mantenimientos atendidos
   - Tiempo promedio de reparación
   - Distribución de carga de trabajo

2. **Eficiencia en reparaciones**:
   - Mecánicos con reparaciones más rápidas
   - Mecánicos con mayor variabilidad en tiempos
   - Tiempo de respuesta promedio

3. **Especialización**:
   - Tipos de fallas que atiende cada mecánico
   - Líneas asignadas predominantemente
   - Repuestos que utiliza más

4. **Calidad del trabajo**:
   - ¿Hay fallas recurrentes por mecánico?
   - Efectividad de reparaciones (no regresa el problema)
   - Mecánicos con menos re-trabajos

5. **Desarrollo y capacitación**:
   - Áreas de mejora por mecánico
   - Necesidades de capacitación
   - Redistribución óptima de cargas

FORMATO DE SALIDA:
Sé objetivo y constructivo. Usa datos cuantitativos. Reconoce fortalezas y áreas de mejora.`
      },
      'full': {
        title: 'Análisis Integral',
        prompt: `Eres un director de mantenimiento. Realiza un análisis ejecutivo integral de todo el sistema de mantenimiento.

FORMATO DE DATOS CSV:
ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador

ANÁLISIS REQUERIDO:
1. **Resumen Ejecutivo**:
   - Estado general del sistema de mantenimiento
   - Principal hallazgo o problema crítico
   - Oportunidad de mejora más importante

2. **Métricas clave**:
   - Tiempo total de paro en el período
   - Número total de mantenimientos
   - Tiempo promedio de paro, respuesta y reparación
   - Tiempo que el 90% y 95% de casos no supera
   - Disponibilidad estimada del sistema

3. **Principales problemas identificados**:
   - Top 5 causas de fallas
   - Top 3 líneas más problemáticas
   - Fallas críticas y recurrentes
   - Cuellos de botella en respuesta

4. **Análisis de recursos**:
   - Rendimiento del equipo de mecánicos
   - Repuestos críticos
   - Distribución de carga de trabajo

5. **Plan de acción estratégico**:
   - 3 Quick wins (implementación inmediata, impacto medio)
   - 3 Iniciativas estratégicas (mediano plazo, alto impacto)
   - Metas cuantificadas de mejora
   - ROI estimado

FORMATO DE SALIDA:
Lenguaje ejecutivo. Enfócate en impacto de negocio. Cuantifica todo. Prioriza acciones.`
      }
    };

    // Variable global para almacenar el prompt actual
    let currentPromptData = null;

    // Click en opciones del dropdown de análisis
    $('.dropdown-item[data-analysis]').on('click', function(e) {
      e.preventDefault();
      const analysisType = $(this).data('analysis');
      const config = analysisPrompts[analysisType];

      if (!config) {
        console.error('[AI] Tipo de análisis no configurado:', analysisType);
        return;
      }

      console.log('[AI] Tipo seleccionado:', analysisType, config.title);

      // Recolectar datos según el tipo de análisis
      const data = collectMaintenanceDataCSV(analysisType);

      // Verificar si hay datos
      if (!data.csv || data.csv.trim() === '' || data.csv.split('\n').length <= 1) {
        alert('No hay datos disponibles para analizar. Por favor, asegúrate de que haya registros en la tabla.');
        return;
      }

      console.log('[AI] Datos recolectados:', {
        tipo: analysisType,
        filas: data.csv.split('\n').length - 1,
        caracteresCSV: data.csv.length
      });

      // Combinar prompt con datos CSV
      const combinedPrompt = `${config.prompt}\n\n=== DATOS CSV ===\n${data.csv}`;

      // Guardar datos del prompt actual (para poder restaurar)
      currentPromptData = {
        type: analysisType,
        title: config.title,
        prompt: config.prompt,
        csv: data.csv,
        combinedPrompt: combinedPrompt
      };

      // Mostrar modal de edición con el prompt completo
      $('#aiPromptModalTitle').text(config.title);
      $('#aiPrompt').val(combinedPrompt);
      const editModal = new bootstrap.Modal(document.getElementById('aiPromptModal'));
      editModal.show();
    });

    // Botón "Restaurar prompt original"
    $('#btn-ai-reset').on('click', function() {
      if (currentPromptData && currentPromptData.combinedPrompt) {
        $('#aiPrompt').val(currentPromptData.combinedPrompt);
      }
    });

    // Botón "Enviar a IA"
    $('#btn-ai-send').on('click', function() {
      const editedPrompt = $('#aiPrompt').val();

      if (!editedPrompt || editedPrompt.trim() === '') {
        alert('{{ __("El prompt no puede estar vacío") }}');
        return;
      }

      // Cerrar modal de edición
      const editModal = bootstrap.Modal.getInstance(document.getElementById('aiPromptModal'));
      if (editModal) editModal.hide();

      // Ejecutar análisis con el prompt editado
      if (currentPromptData && currentPromptData.title) {
        runAIAnalysis(editedPrompt, currentPromptData.title);
      }
    });

    // Función para recolectar datos en formato CSV según el tipo de análisis
    function collectMaintenanceDataCSV(analysisType) {
      const tableInstance = $('#maintenancesTable').DataTable();
      if (!tableInstance) {
        console.error('[AI] DataTable no inicializada');
        return { csv: '' };
      }

      // Header CSV
      let csv = 'ID,Linea_Produccion,Fecha_Creacion,Fecha_Inicio,Fecha_Fin,Tiempo_Parado,Tiempo_Reparacion,Tiempo_Total,Causas,Repuestos,Operador,Mecanico,Anotaciones_Mecanico,Anotaciones_Operador\n';

      let count = 0;
      const maxRows = 200; // Limitar a 200 registros para no exceder límites de la IA

      console.log('[AI] Recolectando datos de mantenimientos...');
      const rowsData = tableInstance.rows({search: 'applied'}).data();
      console.log('[AI] Total rows disponibles:', rowsData.length);

      if (rowsData.length === 0) {
        console.warn('[AI] No hay datos en la tabla.');
        return { csv: '' };
      }

      tableInstance.rows({search: 'applied'}).every(function(rowIdx) {
        if (count >= maxRows) return false;

        const row = this.data();
        const node = this.node();

        // Extraer datos del botón de detalles
        const detailsBtn = node ? node.querySelector('.btn-maint-details') : null;
        const annotations = detailsBtn ? (detailsBtn.getAttribute('data-annotations') || '') : '';
        const operatorAnnotations = detailsBtn ? (detailsBtn.getAttribute('data-operator-annotations') || '') : '';
        const causes = detailsBtn ? (detailsBtn.getAttribute('data-causes') || '') : '';
        const parts = detailsBtn ? (detailsBtn.getAttribute('data-parts') || '') : '';

        // Limpiar valores para CSV (eliminar comas y saltos de línea)
        const cleanValue = (val) => {
          if (!val) return '';
          return String(val).replace(/,/g, ';').replace(/\n/g, ' ').replace(/\r/g, '').trim();
        };

        const id = cleanValue(row.id || '');
        const linea = cleanValue(row.production_line || '');
        const fechaCreacion = cleanValue(row.created_at || '');
        const fechaInicio = cleanValue(row.start_datetime || '');
        const fechaFin = cleanValue(row.end_datetime || '');
        const tiempoParado = cleanValue(row.stopped_formatted || '');
        const tiempoReparacion = cleanValue(row.downtime_formatted || '');
        const tiempoTotal = cleanValue(row.total_time_formatted || '');
        const causasStr = cleanValue(causes || row.causes_list || '');
        const repuestosStr = cleanValue(parts || row.parts_list || '');
        const operador = cleanValue(row.operator_name || '');
        const mecanico = cleanValue(row.user_name || '');
        const anotacionesMecanico = cleanValue(annotations || row.annotations_short || '');
        const anotacionesOperador = cleanValue(operatorAnnotations || row.operator_annotations_short || '');

        csv += `${id},${linea},${fechaCreacion},${fechaInicio},${fechaFin},${tiempoParado},${tiempoReparacion},${tiempoTotal},${causasStr},${repuestosStr},${operador},${mecanico},${anotacionesMecanico},${anotacionesOperador}\n`;
        count++;
      });

      console.log(`[AI] CSV generado con ${count} filas`);
      console.log('[AI] Primeras 200 caracteres del CSV:', csv.substring(0, 200));

      return { csv, count };
    }

    // Función para ejecutar el análisis de IA
    async function runAIAnalysis(combinedPrompt, analysisTitle) {
      if (!AI_URL || !AI_TOKEN) {
        alert('{{ __("Configuración de IA no disponible") }}');
        return;
      }

      try {
        // Mostrar modal de procesamiento
        const processingModal = new bootstrap.Modal(document.getElementById('aiProcessingModal'));
        processingModal.show();
        $('#aiProcessingStatus').text('{{ __("Enviando datos...") }}');

        console.log('[AI] Enviando análisis:', {
          titulo: analysisTitle,
          caracteresTotal: combinedPrompt.length
        });

        // Enviar a la IA
        const fd = new FormData();
        fd.append('prompt', combinedPrompt);
        fd.append('agent', 'data_analysis');

        const startResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${AI_TOKEN}` },
          body: fd
        });

        if (!startResp.ok) throw new Error('Error al iniciar tarea de IA');

        const startData = await startResp.json();
        const taskId = (startData && startData.task && (startData.task.id || startData.task.uuid)) || startData.id || startData.task_id || startData.uuid;

        if (!taskId) throw new Error('No se recibió ID de tarea');

        console.log('[AI] Tarea iniciada:', taskId);
        $('#aiProcessingStatus').text('{{ __("Procesando análisis...") }}');

        // Polling para obtener resultados
        let done = false;
        let lastResponse;

        while (!done) {
          await new Promise(r => setTimeout(r, 5000)); // Esperar 5 segundos

          const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
            headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
          });

          if (pollResp.status === 404) {
            throw new Error('Tarea no encontrada');
          }

          if (!pollResp.ok) throw new Error('Error al consultar tarea');

          lastResponse = await pollResp.json();
          const task = lastResponse && lastResponse.task ? lastResponse.task : null;

          if (!task) continue;

          // Verificar si está completa
          if (task.response != null) {
            done = true;
          } else if (task.error && !/processing/i.test(task.error)) {
            throw new Error(task.error);
          }
        }

        // Cerrar modal de procesamiento
        processingModal.hide();

        // Obtener respuesta
        let content = (lastResponse && lastResponse.task && lastResponse.task.response != null) ? lastResponse.task.response : lastResponse;
        const responseText = typeof content === 'string' ? content : JSON.stringify(content, null, 2);

        // Mostrar resultados
        showAIResults(responseText, analysisTitle);

      } catch (err) {
        console.error('[AI] Error:', err);
        const procModal = bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal'));
        if (procModal) procModal.hide();
        alert('{{ __("Error al procesar solicitud de IA") }}: ' + err.message);
      }
    }

    // Función para mostrar resultados de IA
    function showAIResults(rawText, analysisTitle) {
      // Convertir markdown a HTML básico
      let html = rawText;

      // Títulos
      html = html.replace(/^### (.+)$/gm, '<h4 class="mt-4 mb-3 text-primary">$1</h4>');
      html = html.replace(/^## (.+)$/gm, '<h3 class="mt-4 mb-3 text-dark fw-bold">$1</h3>');
      html = html.replace(/^# (.+)$/gm, '<h2 class="mt-4 mb-3 text-dark fw-bold">$1</h2>');

      // Negrita e itálica
      html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
      html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

      // Listas
      html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
      html = html.replace(/(<li>.*<\/li>)/s, '<ul class="mb-3">$1</ul>');

      // Saltos de línea
      html = html.replace(/\n\n/g, '</p><p class="mb-3">');
      html = '<p class="mb-3">' + html + '</p>';

      // Establecer contenido
      $('#aiResultContent').html(html);
      $('#aiAnalysisTitle').text(analysisTitle);

      // Funcionalidad de botones
      let currentFontSize = 100;

      $('#btnCopyResult').off('click').on('click', function() {
        navigator.clipboard.writeText(rawText).then(() => {
          $(this).html('<i class="fas fa-check"></i>');
          setTimeout(() => {
            $(this).html('<i class="fas fa-copy"></i>');
          }, 2000);
        });
      });

      $('#btnZoomIn').off('click').on('click', function() {
        currentFontSize += 10;
        $('#aiResultContent').css('font-size', currentFontSize + '%');
      });

      $('#btnZoomOut').off('click').on('click', function() {
        currentFontSize -= 10;
        if (currentFontSize < 50) currentFontSize = 50;
        $('#aiResultContent').css('font-size', currentFontSize + '%');
      });

      // Mostrar modal
      const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
      resultModal.show();
    }

    // Export buttons
    $('#btn-export-excel').on('click', function(){
      const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
      const params = new URLSearchParams({
        production_line_id: form.production_line_id.value || '',
        operator_id: form.operator_id.value || '',
        user_id: form.user_id.value || '',
        created_from: form.created_from.value || '',
        created_to: form.created_to.value || '',
        start_from: form.start_from.value || '',
        start_to: form.start_to.value || '',
        end_from: form.end_from.value || '',
        end_to: form.end_to.value || ''
      });
      window.location.href = "{{ route('customers.maintenances.export.excel', $customer->id) }}?" + params.toString();
    });

    $('#btn-export-pdf').on('click', function(){
      const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
      const params = new URLSearchParams({
        production_line_id: form.production_line_id.value || '',
        operator_id: form.operator_id.value || '',
        user_id: form.user_id.value || '',
        created_from: form.created_from.value || '',
        created_to: form.created_to.value || '',
        start_from: form.start_from.value || '',
        start_to: form.start_to.value || '',
        end_from: form.end_from.value || '',
        end_to: form.end_to.value || ''
      });
      window.location.href = "{{ route('customers.maintenances.export.pdf', $customer->id) }}?" + params.toString();
    });
  });
  </script>
@endpush

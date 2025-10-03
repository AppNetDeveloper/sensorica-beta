@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Activos') . ' - ' . $customer->name)
@section('page-title', __('Activos'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.assets.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Activos') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <h5 class="mb-0">{{ __('Listado de activos') }}</h5>
      <small class="text-muted">{{ __('Visualiza y filtra los activos del cliente, incluyendo estado y ubicación.') }}</small>
    </div>
    <a href="{{ route('customers.assets.create', $customer) }}" class="btn btn-sm btn-primary">
      <i class="ti ti-plus"></i> {{ __('Nuevo activo') }}
    </a>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card kpi-card shadow-sm h-100 text-white border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="kpi-label text-white-50 mb-1">{{ __('Activos totales') }}</p>
                <h3 class="kpi-value mb-0">{{ $stats['total'] }}</h3>
              </div>
              <i class="ti ti-packages kpi-icon"></i>
            </div>
            <p class="kpi-subtitle mb-0 opacity-75">{{ __('Incluye todos los registros') }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card kpi-card shadow-sm h-100 border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="kpi-label text-muted mb-1">{{ __('Activos activos') }}</p>
                <h3 class="kpi-value text-success mb-0">{{ $stats['active'] }}</h3>
              </div>
              <span class="badge badge-soft text-success">{{ $stats['active_percent'] }}%</span>
            </div>
            <div class="progress mt-2" style="height:6px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stats['active_percent'] }}%;" aria-valuenow="{{ $stats['active_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-2"><i class="ti ti-check me-1 text-success"></i>{{ $stats['active_percent'] }}% {{ __('del total') }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card kpi-card shadow-sm h-100 border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="kpi-label text-muted mb-1">{{ __('En mantenimiento') }}</p>
                <h3 class="kpi-value text-warning mb-0">{{ $stats['maintenance'] }}</h3>
              </div>
              <span class="badge badge-soft text-warning">{{ $stats['maintenance_percent'] }}%</span>
            </div>
            <div class="progress mt-2" style="height:6px;">
              <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $stats['maintenance_percent'] }}%;" aria-valuenow="{{ $stats['maintenance_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-2"><i class="ti ti-tools me-1 text-warning"></i>{{ $stats['maintenance_percent'] }}% {{ __('del total') }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card kpi-card shadow-sm h-100 border-0">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="kpi-label text-muted mb-1">{{ __('Sin ubicación asignada') }}</p>
                <h3 class="kpi-value mb-0">{{ $stats['without_location'] }}</h3>
              </div>
              <i class="ti ti-map-pin kpi-icon text-primary"></i>
            </div>
            <div class="progress mt-2" style="height:6px;">
              <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $stats['without_location_percent'] }}%;" aria-valuenow="{{ $stats['without_location_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-2">{{ $stats['without_location_percent'] }}% {{ __('del total') }}</small>
            <small class="text-muted d-block"><i class="ti ti-rfid me-1 text-info"></i>{{ __('Activos con RFID') }}: {{ $stats['with_rfid'] }} ({{ $stats['rfid_percent'] }}%)</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4 border shadow-sm">
      <div class="card-body">
        <div class="row g-3 align-items-center">
          <div class="col-lg-5">
            <label for="quick-scan-input" class="form-label mb-1">{{ __('Escanear activo') }}</label>
            <input type="text" id="quick-scan-input" class="form-control" placeholder="{{ __('Escanea o ingresa el código de la etiqueta') }}" autocomplete="off">
          </div>
          <div class="col-lg-3 col-md-6 d-grid">
            <button type="button" id="quick-scan-submit" class="btn btn-primary">
              <i class="ti ti-search"></i> {{ __('Buscar activo') }}
            </button>
          </div>
          <div class="col-lg-4 col-md-6 d-grid d-lg-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary w-100 w-lg-auto" data-bs-toggle="modal" data-bs-target="#scanModal">
              <i class="ti ti-camera-scan me-1"></i>{{ __('Usar cámara del móvil') }}
            </button>
            <small class="text-muted">{{ __('Funciona con códigos QR y de barras.') }}</small>
          </div>
        </div>
      </div>
    </div>

    <form id="asset-filters-form" method="GET" action="{{ route('customers.assets.index', $customer) }}" class="card mb-4 border">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label for="filter-search" class="form-label">{{ __('Buscar por código o descripción') }}</label>
            <input type="text" name="search" id="filter-search" class="form-control" value="{{ $filters['search'] }}" placeholder="{{ __('Etiqueta, artículo, descripción...') }}">
          </div>
          <div class="col-md-2">
            <label for="filter-category" class="form-label">{{ __('Categoría') }}</label>
            <select name="category" id="filter-category" class="form-select">
              <option value="">{{ __('Todas') }}</option>
              @foreach($categories as $id => $name)
                <option value="{{ $id }}" {{ (string)$filters['category'] === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label for="filter-location" class="form-label">{{ __('Ubicación') }}</label>
            <select name="location" id="filter-location" class="form-select">
              <option value="">{{ __('Todas') }}</option>
              @foreach($locations as $id => $name)
                <option value="{{ $id }}" {{ (string)$filters['location'] === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label for="filter-supplier" class="form-label">{{ __('Proveedor') }}</label>
            <select name="supplier" id="filter-supplier" class="form-select">
              <option value="">{{ __('Todos') }}</option>
              @foreach($suppliers as $id => $name)
                <option value="{{ $id }}" {{ (string)$filters['supplier'] === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label for="filter-status" class="form-label">{{ __('Estado') }}</label>
            <select name="status" id="filter-status" class="form-select">
              <option value="">{{ __('Todos') }}</option>
              @foreach($statuses as $status)
                <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ __($status) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary">{{ __('Aplicar') }}</button>
          </div>
          <div class="col-md-1 d-grid">
            <button type="button" id="reset-asset-filters" class="btn btn-outline-secondary">{{ __('Limpiar') }}</button>
          </div>
        </div>
      </div>
    </form>

    @if($assets->isEmpty())
      <div class="alert alert-info mb-0">{{ __('No hay activos registrados con los filtros actuales.') }}</div>
    @else
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="assetsTable">
          <thead>
            <tr>
              <th>{{ __('Código etiqueta') }}</th>
              <th>{{ __('Código artículo') }}</th>
              <th>{{ __('Descripción') }}</th>
              <th>{{ __('Categoría') }}</th>
              <th>{{ __('Centro de coste') }}</th>
              <th>{{ __('Ubicación') }}</th>
              <th>{{ __('Estado') }}</th>
              <th>{{ __('RFID EPC') }}</th>
              <th>{{ __('RFID TID') }}</th>
              <th>{{ __('Proveedor') }}</th>
              <th class="text-end">{{ __('Acciones') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($assets as $asset)
              <tr>
                <td><code>{{ $asset->label_code }}</code></td>
                <td><code>{{ $asset->article_code }}</code></td>
                <td>{{ Str::limit($asset->description, 60) }}</td>
                <td>{{ optional($asset->category)->name ?? '—' }}</td>
                <td>{{ optional($asset->costCenter)->name ?? '—' }}</td>
                <td>{{ optional($asset->location)->name ?? '—' }}</td>
                <td>
                  <span class="badge bg-{{ $asset->status === 'active' ? 'success' : ($asset->status === 'maintenance' ? 'warning' : ($asset->status === 'retired' ? 'secondary' : 'info')) }} text-uppercase">
                    {{ __($asset->status) }}
                  </span>
                </td>
                <td>{{ $asset->rfid_epc ? Str::limit($asset->rfid_epc, 20) : '—' }}</td>
                <td>{{ $asset->rfid_tid ? Str::limit($asset->rfid_tid, 20) : '—' }}</td>
                <td>{{ optional($asset->supplier)->name ?? '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.assets.show', [$customer, $asset]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Ver') }}">
                    <i class="ti ti-eye"></i>
                  </a>
                  <a href="{{ route('customers.assets.edit', [$customer, $asset]) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Editar') }}">
                    <i class="ti ti-edit"></i>
                  </a>
                  <a href="{{ route('customers.assets.print-label', [$customer, $asset]) }}" class="btn btn-sm btn-outline-success" title="{{ __('Imprimir etiqueta') }}" target="_blank">
                    <i class="ti ti-printer"></i>
                  </a>
                  <form action="{{ route('customers.assets.destroy', [$customer, $asset]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Eliminar este activo?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="ti ti-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

<div class="modal fade" id="scanModal" tabindex="-1" aria-labelledby="scanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scanModalLabel"><i class="ti ti-camera-scan me-2"></i>{{ __('Escanear con la cámara') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cerrar') }}"></button>
      </div>
      <div class="modal-body">
        <div id="scan-status" class="alert alert-warning d-none" role="alert"></div>
        <div id="qr-reader" class="border rounded position-relative overflow-hidden" style="min-height: 260px;"></div>
        <p class="text-muted small mt-3 mb-0">{{ __('Permite el acceso a la cámara para detectar etiquetas rápidamente.') }}</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cerrar') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css"/>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
  $(function(){
    $('#assetsTable').DataTable({
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"btn-toolbar"B><"flex-grow-1"f>>rtip',
      buttons: [
        {
          extend: 'excelHtml5',
          title: '{{ __('Activos') }} - {{ $customer->name }}'
        },
        {
          extend: 'pdfHtml5',
          title: '{{ __('Activos') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        },
        {
          extend: 'print',
          title: '{{ __('Activos') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        }
      ],
      language: {
        url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}'
      },
      columnDefs: [
        { targets: -1, orderable: false, searchable: false }
      ]
    });

    const filterForm = document.getElementById('asset-filters-form');
    const filterSearch = document.getElementById('filter-search');
    const quickScanInput = document.getElementById('quick-scan-input');
    const quickScanSubmit = document.getElementById('quick-scan-submit');
    const resetButton = document.getElementById('reset-asset-filters');

    function submitQuickScan(value) {
      if (!filterForm || !filterSearch || !value) {
        return;
      }
      filterSearch.value = value.trim();
      filterForm.submit();
    }

    if (resetButton) {
      resetButton.addEventListener('click', function () {
        if (!filterForm) {
          return;
        }
        filterForm.querySelectorAll('input[type="text"]').forEach(el => el.value = '');
        filterForm.querySelectorAll('select').forEach(el => el.value = '');
        filterForm.submit();
      });
    }

    if (quickScanSubmit) {
      quickScanSubmit.addEventListener('click', function () {
        submitQuickScan(quickScanInput ? quickScanInput.value : '');
      });
    }

    if (quickScanInput) {
      quickScanInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          submitQuickScan(quickScanInput.value);
        }
      });
    }

    const scanModalEl = document.getElementById('scanModal');
    const scanStatusEl = document.getElementById('scan-status');
    const bootstrapModal = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal : null;
    const scanReadyLabel = @json(__('Apunta la cámara al código para identificar el activo.'));
    const scanDetectedTemplate = @json(__('Código detectado: :code'));
    const scanErrorLabel = @json(__('No se pudo iniciar la cámara. Revisa permisos.'));
    const scanUnavailableLabel = @json(__('Escáner no disponible en este navegador.'));
    let html5QrInstance = null;

    async function stopScanner() {
      if (!html5QrInstance) {
        return;
      }
      try {
        await html5QrInstance.stop();
      } catch (error) {
        console.warn('QR stop error', error);
      }
      html5QrInstance.clear();
      html5QrInstance = null;
    }

    async function startScanner() {
      if (!scanStatusEl) {
        return;
      }
      if (typeof Html5Qrcode === 'undefined') {
        scanStatusEl.textContent = scanUnavailableLabel;
        scanStatusEl.classList.remove('d-none', 'alert-warning', 'alert-success');
        scanStatusEl.classList.add('alert-danger');
        return;
      }

      scanStatusEl.textContent = scanReadyLabel;
      scanStatusEl.classList.remove('d-none', 'alert-danger', 'alert-success');
      scanStatusEl.classList.add('alert-warning');

      html5QrInstance = new Html5Qrcode('qr-reader');

      try {
        await html5QrInstance.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: 250 },
          async (decodedText) => {
            if (!scanStatusEl) {
              return;
            }
            scanStatusEl.textContent = scanDetectedTemplate.replace(':code', decodedText);
            scanStatusEl.classList.remove('alert-warning', 'alert-danger');
            scanStatusEl.classList.add('alert-success');
            await stopScanner();
            const modalInstance = bootstrapModal ? bootstrapModal.getInstance(scanModalEl) : null;
            if (modalInstance) {
              modalInstance.hide();
            } else if (scanModalEl) {
              scanModalEl.classList.remove('show');
            }
            submitQuickScan(decodedText);
          }
        );
      } catch (error) {
        console.error('QR start error', error);
        scanStatusEl.textContent = scanErrorLabel;
        scanStatusEl.classList.remove('d-none', 'alert-warning', 'alert-success');
        scanStatusEl.classList.add('alert-danger');
      }
    }

    if (scanModalEl) {
      scanModalEl.addEventListener('shown.bs.modal', () => {
        if (quickScanInput) {
          quickScanInput.blur();
        }
        startScanner();
      });

      scanModalEl.addEventListener('hidden.bs.modal', () => {
        stopScanner();
      });
    }
  });
</script>
@endpush

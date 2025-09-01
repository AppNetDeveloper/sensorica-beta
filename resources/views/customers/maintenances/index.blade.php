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
      <div class="btn-group btn-group-sm me-2" role="group" aria-label="Catálogos">
        <a href="{{ route('customers.maintenance-causes.index', $customer->id) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="{{ __('Operaciones de mantenimiento') }}">
          <i class="ti ti-flag-3 me-1"></i><span class="d-none d-sm-inline">{{ __('Operaciones') }}</span>
        </a>
        <a href="{{ route('customers.maintenance-parts.index', $customer->id) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="{{ __('Repuestos de mantenimiento') }}">
          <i class="ti ti-tools me-1"></i><span class="d-none d-sm-inline">{{ __('Repuestos') }}</span>
        </a>
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
          <!-- AI Prompt Button (config-gated) -->
          <button type="button" class="btn btn-dark" id="btn-ai-prompt" data-bs-toggle="modal" data-bs-target="#aiPromptModal" title="{{ __('Análisis con IA') }}">
            <i class="bi bi-stars me-1 text-white"></i><span class="d-none d-sm-inline">{{ __('Análisis IA') }}</span>
          </button>
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
        <label class="form-label">{{ __('Operator') }}</label>
        <select name="operator_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($operators as $op)
            <option value="{{ $op->id }}" {{ (string)($operatorId ?? '') === (string)$op->id ? 'selected' : '' }}>{{ $op->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('User') }}</label>
        <select name="user_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ (string)($userId ?? '') === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
          @endforeach
        </select>
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
            <th>{{ __('Production Line') }}</th>
            <th>{{ __('Created') }}</th>
            <th>{{ __('Start') }}</th>
            <th>{{ __('End') }}</th>
            <th>{{ __('Stopped before Start') }}</th>
            <th>{{ __('Downtime') }}</th>
            <th>{{ __('Total Time') }}</th>
            <th>{{ __('Causes') }}</th>
            <th>{{ __('Parts') }}</th>
            <th>{{ __('Operator') }}</th>
            <th>{{ __('User') }}</th>
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

    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-labelledby="aiPromptModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="aiPromptModalLabel">{{ __('Análisis con IA') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <label class="form-label fw-bold" for="aiPromptTextarea">{{ __('Prompt') }}</label>
            <textarea id="aiPromptTextarea" class="form-control" rows="6"></textarea>
            <div class="text-muted small mt-2">{{ __('El análisis incluirá los datos visibles en la tabla y los detalles (anotaciones, causas y piezas) de cada fila.') }}</div>
          </div>
          <div class="modal-footer">
            <div id="aiLoading" class="d-flex align-items-center me-auto d-none">
              <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
              <span>Pensando...</span>
            </div>
            <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">{{ __('Limpiar prompt por defecto') }}</button>
            <button type="button" class="btn btn-primary" id="btn-ai-send">{{ __('Enviar') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- AI Result Modal -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-labelledby="aiResultModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="aiResultModalLabel">{{ __('Vista previa de datos para IA') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2"><strong>{{ __('Prompt') }}:</strong></div>
            <pre id="aiResultPrompt" class="bg-light p-2 rounded" style="white-space:pre-wrap"></pre>
            <div class="mb-2 mt-3"><strong>{{ __('Datos') }}:</strong></div>
            <pre id="aiResultData" class="bg-light p-2 rounded" style="white-space:pre-wrap"></pre>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
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
    const defaultPrompt = "Necesito un análisis de los datos de mantenimiento: motivos, causas, piezas usadas, tiempos y cualquier patrón relevante.";
    const AI_URL = @json(config('services.ai.url'));
    const AI_TOKEN = @json(config('services.ai.token'));
    const $aiPromptTextarea = $('#aiPromptTextarea');
    // Set default on open
    $('#aiPromptModal').on('shown.bs.modal', function(){
      if (!$aiPromptTextarea.val()) {
        $aiPromptTextarea.val(defaultPrompt);
      }
      $aiPromptTextarea.trigger('focus');
    });
    // Reset to default
    $('#btn-ai-reset').on('click', function(){
      $aiPromptTextarea.val(defaultPrompt);
    });
    const table = $('#maintenancesTable').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      order: [[2, 'desc']], // created_at desc
      columnDefs: [
        { targets: -1, responsivePriority: 1 }, // Actions column highest priority
        { targets: 0, responsivePriority: 100 }, // ID lowest priority
        { targets: [2,3,4], responsivePriority: 2 }, // Created/Start/End
        { targets: [5,6,7], responsivePriority: 3 } // Stopped/Downtime/Total
      ],
      ajax: {
        url: "{{ route('customers.maintenances.index', $customer->id) }}",
        data: function(d) {
          const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
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
      ],
      language: {
        url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}'
      }
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
          created_at: rowData.created_at,
          start_datetime: rowData.start_datetime,
          end_datetime: rowData.end_datetime,
          stopped: rowData.stopped_formatted,
          downtime: rowData.downtime_formatted,
          total_time: rowData.total_time_formatted,
          causes_list: rowData.causes_list,
          parts_list: rowData.parts_list,
          operator_name: rowData.operator_name,
          user_name: rowData.user_name,
          annotations_short: rowData.annotations_short,
          operator_annotations_short: rowData.operator_annotations_short,
          details: details
        });
      });
      const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
      const filters = {
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
      document.getElementById('md-annotations').textContent = annotations || '-';
      document.getElementById('md-operator-annotations').textContent = opAnn || '-';
      document.getElementById('md-causes').textContent = causes || '-';
      document.getElementById('md-parts').textContent = parts || '-';
    });

    function showLoading(show) {
      const $loading = $('#aiLoading');
      const $send = $('#btn-ai-send');
      if (show) {
        $loading.removeClass('d-none');
        $send.prop('disabled', true);
      } else {
        $loading.addClass('d-none');
        $send.prop('disabled', false);
      }
    }

    async function startAiTask(prompt) {
      if (!AI_URL || !AI_TOKEN) {
        alert('{{ __('An error occurred') }}');
        return;
      }
      showLoading(true);
      try {
        const payload = collectCurrentMaintData();
        console.log('[AI] Collected rows:', payload.rows.length, 'filters:', payload.filters);
        const fd = new FormData();
        // Join prompt + data into the same prompt field as requested
        let combinedPrompt;
        try {
          combinedPrompt = `${prompt}\n\n=== Datos para analizar (JSON) ===\n${JSON.stringify(payload, null, 2)}`;
        } catch (e) {
          combinedPrompt = `${prompt}\n\n=== Datos para analizar (JSON) ===\n[Error serializando datos]`;
        }
        console.log('[AI] Combined prompt length:', combinedPrompt.length);
        console.log('[AI] Combined prompt preview:', combinedPrompt.substring(0, 500));
        fd.append('prompt', combinedPrompt);
        console.log('[AI] Starting task POST ...');
        console.log('[AI] Using URL:', AI_URL);
        console.log('[AI] Token present:', !!AI_TOKEN);
        const startResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${AI_TOKEN}` },
          body: fd
        });
        if (!startResp.ok) throw new Error('start failed');
        const startData = await startResp.json();
        console.log('[AI] Start response:', startData);
        const taskId = (startData && startData.task && (startData.task.id || startData.task.uuid)) || startData.id || startData.task_id || startData.uuid;
        if (!taskId) throw new Error('no id');

        // Polling
        let done = false;
        let last;
        while (!done) {
          await new Promise(r => setTimeout(r, 5000));
          console.log(`[AI] Polling task ${taskId} ...`);
          const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
            headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
          });
          if (pollResp.status === 404) {
            try {
              const notFound = await pollResp.json();
              console.warn('[AI] Task not found (404):', notFound);
              alert((notFound && notFound.error) ? notFound.error : 'Task not found');
            } catch (e) {
              console.warn('[AI] Task not found (404) with no JSON');
              alert('Task not found');
            }
            return; // stop
          }
          if (!pollResp.ok) throw new Error('poll failed');
          last = await pollResp.json();
          console.log('[AI] Poll data:', last);
          const task = last && last.task ? last.task : null;
          if (!task) continue;
          // Pending/In progress
          if (task.response == null) {
            // Some implementations set a transient string in error while processing (e.g., "processing by <uuid>")
            if (task.error && /processing/i.test(task.error)) {
              console.log('[AI] Task pending (processing):', task.error);
              continue;
            }
            if (task.error == null) {
              console.log('[AI] Task pending (no response yet)');
              continue;
            }
          }
          // Failed (real error and not a processing hint)
          if (task.error && !/processing/i.test(task.error)) {
            console.error('[AI] Task failed:', task.error);
            alert(task.error);
            return;
          }
          // Completed
          if (task.response != null) {
            done = true;
          }
        }

        // Show results
        $('#aiResultPrompt').text(prompt);
        let content;
        try {
          content = (last && last.task && last.task.response != null) ? last.task.response : last;
        } catch (e) {
          content = last;
        }
        try {
          $('#aiResultData').text(typeof content === 'string' ? content : JSON.stringify(content, null, 2));
        } catch (e) {
          $('#aiResultData').text(String(content));
        }
        const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
        resultModal.show();
      } catch (err) {
        console.error('[AI] Unexpected error:', err);
        alert('{{ __('An error occurred') }}');
      } finally {
        showLoading(false);
      }
    }

    // Send to AI: start task and poll
    $('#btn-ai-send').on('click', function(){
      const prompt = $aiPromptTextarea.val() || defaultPrompt;
      startAiTask(prompt);
    });
  });
  </script>
@endpush

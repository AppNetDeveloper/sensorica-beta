@extends('layouts.admin')

@section('title', __('Confirmaciones QC'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Confirmaciones QC') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">@lang('Confirmaciones QC') - {{ $customer->name }}</h5>
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
                            @php($aiUrl = config('services.ai.url'))
                            @php($aiToken = config('services.ai.token'))
                            @if(!empty($aiUrl) && !empty($aiToken))
                            <div class="btn-group btn-group-sm me-2" role="group" aria-label="IA">
                                <button type="button" class="btn btn-dark dropdown-toggle position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="@lang('Análisis con IA')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; font-weight: 600;">
                                    <i class="bi bi-stars me-1"></i>
                                    <span class="d-none d-sm-inline">@lang('Análisis IA')</span>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65em;">PRO</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 350px; max-height: 600px; overflow-y: auto;">
                                    <li><h6 class="dropdown-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: -0.5rem -0.5rem 0.5rem -0.5rem; padding: 0.75rem 1rem;">
                                        <i class="fas fa-brain me-2"></i>{{ __("Análisis Inteligente de Confirmaciones QC") }}
                                        <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7em;">PRO</span>
                                    </h6></li>

                                    <!-- SECCIÓN 1: Análisis General -->
                                    <li><h6 class="dropdown-header text-primary"><i class="fas fa-chart-bar me-1"></i> {{ __("Análisis General") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="general">
                                        <i class="fas fa-clipboard-check text-success me-2"></i>{{ __("Visión General de Confirmaciones") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="quality-trends">
                                        <i class="fas fa-chart-line text-primary me-2"></i>{{ __("Tendencias de Calidad") }}
                                    </a></li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- SECCIÓN 2: Por Línea/Operador -->
                                    <li><h6 class="dropdown-header text-info"><i class="fas fa-users me-1"></i> {{ __("Por Línea y Operador") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="by-line">
                                        <i class="fas fa-industry text-info me-2"></i>{{ __("Rendimiento por Línea") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="by-operator">
                                        <i class="fas fa-user-check text-primary me-2"></i>{{ __("Rendimiento por Operador") }}
                                    </a></li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- SECCIÓN 3: Tiempos -->
                                    <li><h6 class="dropdown-header text-warning"><i class="fas fa-clock me-1"></i> {{ __("Análisis de Tiempos") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="confirmation-times">
                                        <i class="fas fa-stopwatch text-warning me-2"></i>{{ __("Tiempos de Confirmación") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="time-patterns">
                                        <i class="fas fa-calendar-alt text-info me-2"></i>{{ __("Patrones Temporales") }}
                                    </a></li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- SECCIÓN 4: Análisis Completo -->
                                    <li><h6 class="dropdown-header text-dark"><i class="fas fa-layer-group me-1"></i> {{ __("Análisis Completo") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="full">
                                        <i class="fas fa-brain text-dark me-2"></i>{{ __("Análisis Integral") }}
                                    </a></li>
                                </ul>
                            </div>
                            @endif
                            <div class="btn-group btn-group-sm" role="group" aria-label="Kanban">
                                <a href="{{ route('customers.order-organizer', $customer->id) }}" class="btn btn-light">
                                    <i class="fas fa-th me-1"></i><span class="d-none d-sm-inline">@lang('Order Organizer')</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Filtros -->
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha desde')</label>
                            <input type="date" name="date_from" id="filter-date-from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha hasta')</label>
                            <input type="date" name="date_to" id="filter-date-to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Línea de producción')</label>
                            <select name="line_id" id="filter-line" class="form-select form-select-sm">
                                <option value="">@lang('Todas')</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}" @selected(($filters['line_id'] ?? '') == $line->id)>{{ $line->name ?? ('#'.$line->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Operador')</label>
                            <select name="operator_id" id="filter-operator" class="form-select form-select-sm">
                                <option value="">@lang('Todos')</option>
                                @foreach($operators as $op)
                                    <option value="{{ $op->id }}" @selected(($filters['operator_id'] ?? '') == $op->id)>{{ $op->name ?? ('#'.$op->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-filter me-1"></i>@lang('Aplicar filtros')
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="qc-confirmations-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('Original Order')</th>
                                    <th class="text-uppercase">@lang('Production Order')</th>
                                    <th class="text-uppercase">@lang('Info')</th>
                                    <th class="text-uppercase">@lang('Notes')</th>
                                    <th class="text-uppercase">@lang('Confirmed At')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($confirmations as $index => $qc)
                                    <tr data-line-id="{{ $qc->production_line_id ?? optional($qc->productionOrder)->production_line_id }}"
                                        data-operator-id="{{ $qc->operator_id ?? '' }}"
                                        data-created-at="{{ optional($qc->confirmed_at)->format('Y-m-d') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($qc->originalOrder)
                                                <a href="{{ route('customers.original-orders.show', ['customer' => $customer->id, 'originalOrder' => $qc->originalOrder->id]) }}" class="text-decoration-none">
                                                    #{{ $qc->originalOrder->order_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($qc->productionOrder)
                                                #{{ $qc->productionOrder->order_id }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($qc->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $qc->productionLine->name ?? ('L#'.$qc->productionLine->id) }}</span>
                                            @elseif(optional($qc->productionOrder)->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $qc->productionOrder->productionLine->name ?? ('L#'.$qc->productionOrder->productionLine->id) }}</span>
                                            @endif
                                            @if($qc->operator)
                                                <span class="badge bg-secondary" title="@lang('Operador')"><i class="fas fa-user"></i> {{ $qc->operator->name }}</span>
                                            @elseif($qc->operator_id)
                                                <span class="badge bg-secondary" title="@lang('Operador')"><i class="fas fa-user"></i> #{{ $qc->operator_id }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($qc->notes, 80) }}</td>
                                        <td>{{ optional($qc->confirmed_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <h6 class="text-uppercase text-muted fw-semibold mb-3">
                        <i class="fas fa-info-circle me-1"></i>@lang('Leyenda de colores Estado de pedido')
                    </h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <span class="legend-swatch bg-danger me-2"></span>
                            <span class="fw-semibold">@lang('Incidencia (status 3)')</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="legend-swatch bg-success me-2"></span>
                            <span class="fw-semibold">@lang('Finalizadas (status 2)')</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="legend-swatch bg-warning me-2"></span>
                            <span class="fw-semibold">@lang('En curso (status 1)')</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="legend-swatch bg-light border me-2"></span>
                            <span class="fw-semibold">@lang('Pendientes (status 0)')</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enable Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
            const table = $('#qc-confirmations-table').DataTable({
                responsive: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[5, 'desc']],
                dom: 'Bfrt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 info-pagination-container"ip>>',
                buttons: [
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'print', text: 'Imprimir', className: 'btn btn-sm btn-outline-primary' },
                ]
            });

            // === AI integration for QC Confirmations ===
            const AI_URL = @json(config('services.ai.url'));
            const AI_TOKEN = @json(config('services.ai.token'));

            function collectCurrentRows() {
                const table = $('#qc-confirmations-table').DataTable();
                const rows = table.rows({ search: 'applied' }).nodes();

                // Header CSV
                let csv = 'Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID\n';
                let count = 0;
                const maxRows = 200;

                table.rows({ search: 'applied' }).every(function(rowIdx){
                    if (count >= maxRows) return false;

                    const tr = $(rows[rowIdx]);
                    const cells = $(this.node()).find('td');

                    // Limpiar valores para CSV (eliminar comas y saltos de línea)
                    const cleanValue = (val) => {
                        if (!val) return '';
                        return String(val).replace(/,/g, ';').replace(/\n/g, ' ').replace(/\r/g, '').trim();
                    };

                    const index = cleanValue($(cells[0]).text());
                    const original_order = cleanValue($(cells[1]).text());
                    const production_order = cleanValue($(cells[2]).text());
                    const info = cleanValue($(cells[3]).text());
                    const notes = cleanValue($(cells[4]).text());
                    const confirmed_at = cleanValue($(cells[5]).text());
                    const line_id = cleanValue(tr.data('line-id') || '');
                    const operator_id = cleanValue(tr.data('operator-id') || '');

                    csv += `${index},${original_order},${production_order},${info},${notes},${confirmed_at},${line_id},${operator_id}\n`;
                    count++;
                });

                const filters = {
                    line: $('#filter-line').val() || '',
                    operator: $('#filter-operator').val() || '',
                    date_from: $('#filter-date-from').val() || '',
                    date_to: $('#filter-date-to').val() || ''
                };

                console.log(`[AI] CSV generado con ${count} filas`);
                return { csv, filters, rowCount: count };
            }

            function showLoading(show) {
                $('#btn-ai-send').prop('disabled', !!show).toggleClass('disabled', !!show);
                $('#aiLoadingIndicator').toggleClass('d-none', !show);
            }

            async function startAiTask(prompt) {
                if (!AI_URL || !AI_TOKEN) { alert('AI config missing'); return; }
                showLoading(true);
                try {
                    console.log('[AI][QC Confirmations] Enviando prompt');
                    console.log('[AI] Prompt length:', prompt.length);
                    console.log('[AI] Prompt preview:', prompt.substring(0, 500));
                    const fd = new FormData();
                    fd.append('prompt', prompt);
                    fd.append('agent', 'data_analysis');

                    console.log('[AI] Starting task POST ...');
                    console.log('[AI] Using URL:', AI_URL);
                    console.log('[AI] Token present:', !!AI_TOKEN);
                    const startResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                        method: 'POST', headers: { 'Authorization': `Bearer ${AI_TOKEN}` }, body: fd
                    });
                    if (!startResp.ok) throw new Error('start failed');
                    const startData = await startResp.json();
                    console.log('[AI] Start response:', startData);
                    const taskId = (startData && startData.task && (startData.task.id || startData.task.uuid)) || startData.id || startData.task_id || startData.uuid;
                    if (!taskId) throw new Error('no id');

                    let done = false; let last;
                    while (!done) {
                        await new Promise(r => setTimeout(r, 5000));
                        console.log(`[AI] Polling task ${taskId} ...`);
                        const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                            headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                        });
                        if (pollResp.status === 404) {
                            try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {}
                            return;
                        }
                        if (!pollResp.ok) throw new Error('poll failed');
                        last = await pollResp.json();
                        console.log('[AI] Poll data:', last);
                        const task = last && last.task ? last.task : null;
                        if (!task) continue;
                        if (task.response == null) {
                            if (task.error && /processing/i.test(task.error)) { console.log('[AI] Task pending (processing):', task.error); continue; }
                            if (task.error == null) { console.log('[AI] Task pending (no response yet)'); continue; }
                        }
                        if (task.error && !/processing/i.test(task.error)) { console.error('[AI] Task failed:', task.error); alert(task.error); return; }
                        if (task.response != null) { done = true; }
                    }

                    $('#aiResultPrompt').text(prompt);
                    const content = (last && last.task && last.task.response != null) ? last.task.response : last;
                    try { $('#aiResultData').text(typeof content === 'string' ? content : JSON.stringify(content, null, 2)); } catch { $('#aiResultData').text(String(content)); }
                    const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
                    resultModal.show();
                } catch (err) {
                    console.error('[AI] Unexpected error:', err);
                    alert('{{ __('An error occurred') }}');
                } finally {
                    showLoading(false);
                }
            }

            // Configuración de prompts por tipo de análisis
            const analysisPrompts = {
                'general': {
                    title: 'Visión General de Confirmaciones',
                    prompt: `Eres un supervisor de control de calidad. Analiza las confirmaciones QC para obtener una visión general del estado de calidad.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Resumen ejecutivo**: Total de confirmaciones, estado general de calidad
2. **Líneas más activas**: Top 5 líneas por número de confirmaciones
3. **Operadores más activos**: Top 5 operadores por número de confirmaciones
4. **Distribución temporal**: Patrones por día/hora de mayor actividad
5. **Notas relevantes**: Resumen de observaciones más importantes
6. **Recomendaciones**: 3 acciones prioritarias basadas en los hallazgos

FORMATO DE SALIDA:
Usa secciones numeradas con datos cuantificados. Sé conciso y enfócate en hallazgos accionables.`
                },
                'quality-trends': {
                    title: 'Tendencias de Calidad',
                    prompt: `Eres un analista de tendencias de calidad. Identifica patrones y tendencias en las confirmaciones QC.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Tendencias temporales**: ¿La calidad mejora, empeora o se mantiene estable?
2. **Patrones por día/semana**: Días con más/menos confirmaciones
3. **Evolución por línea**: Líneas con tendencias positivas o negativas
4. **Evolución por operador**: Operadores con tendencias de mejora o deterioro
5. **Predicciones**: ¿Qué problemas podrían surgir próximamente?
6. **Alertas tempranas**: Señales de advertencia detectadas

FORMATO DE SALIDA:
Usa gráficas conceptuales (texto). Identifica tendencias claras con datos cuantitativos.`
                },
                'by-line': {
                    title: 'Rendimiento por Línea',
                    prompt: `Eres un analista de producción. Compara el rendimiento de calidad entre diferentes líneas de producción.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Ranking de líneas**: Por número de confirmaciones (mayor a menor)
2. **Comparativa de actividad**: Líneas más/menos activas
3. **Distribución de confirmaciones**: ¿Hay líneas con cargas desbalanceadas?
4. **Notas por línea**: Observaciones más frecuentes en cada línea
5. **Líneas problemáticas**: Identificar líneas que requieren atención
6. **Mejores prácticas**: Líneas con mejor desempeño y por qué

FORMATO DE SALIDA:
Tablas comparativas. Ranking claro. Identifica líneas de alto y bajo rendimiento.`
                },
                'by-operator': {
                    title: 'Rendimiento por Operador',
                    prompt: `Eres un supervisor de equipo. Analiza el rendimiento y patrones de los operadores en confirmaciones QC.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Ranking de operadores**: Por número de confirmaciones
2. **Productividad**: Operadores con mayor/menor número de confirmaciones
3. **Distribución de carga**: ¿Hay operadores sobrecargados o subutilizados?
4. **Calidad de notas**: Operadores con observaciones más detalladas/útiles
5. **Patrones individuales**: Operadores con patrones únicos (horarios, líneas)
6. **Desarrollo y capacitación**: Áreas de mejora identificadas

FORMATO DE SALIDA:
Sé objetivo y constructivo. Usa datos cuantitativos. Reconoce fortalezas y áreas de mejora.`
                },
                'confirmation-times': {
                    title: 'Tiempos de Confirmación',
                    prompt: `Eres un analista de eficiencia operativa. Analiza los tiempos en que se realizan las confirmaciones QC.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Distribución horaria**: ¿A qué horas se confirman más órdenes?
2. **Tiempos de respuesta**: ¿Cuánto tiempo pasa entre eventos?
3. **Picos de actividad**: Identificar horas/días de mayor carga
4. **Tiempos muertos**: Períodos con poca o ninguna actividad
5. **Eficiencia por turno**: Si aplica, comparar turnos
6. **Optimización**: Sugerencias para distribuir mejor la carga

FORMATO DE SALIDA:
Presenta horarios en formato claro. Identifica patrones de tiempo. Cuantifica todo.`
                },
                'time-patterns': {
                    title: 'Patrones Temporales',
                    prompt: `Eres un analista de patrones operativos. Identifica patrones temporales en las confirmaciones QC.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Patrones diarios**: ¿Hay días de la semana con más confirmaciones?
2. **Patrones semanales**: Tendencias semana a semana
3. **Patrones por hora**: Horas pico vs horas valle
4. **Correlaciones**: ¿Ciertos operadores trabajan en ciertos horarios/líneas?
5. **Anomalías temporales**: Detectar días/horas inusuales
6. **Estacionalidad**: Si hay suficientes datos, detectar patrones estacionales

FORMATO DE SALIDA:
Identifica patrones claros. Usa ejemplos específicos con fechas/horas. Cuantifica frecuencias.`
                },
                'full': {
                    title: 'Análisis Integral',
                    prompt: `Eres un director de control de calidad. Realiza un análisis ejecutivo integral de todas las confirmaciones QC.

FORMATO DE DATOS CSV:
Index,Original_Order,Production_Order,Info,Notes,Confirmed_At,Line_ID,Operator_ID

ANÁLISIS REQUERIDO:
1. **Resumen Ejecutivo**: Estado general, principal hallazgo, oportunidad principal
2. **Métricas Clave**:
   - Total de confirmaciones en el período
   - Número de líneas activas
   - Número de operadores activos
   - Promedio de confirmaciones por día
   - Distribución de actividad

3. **Análisis por Dimensión**:
   - Top 5 líneas más activas
   - Top 5 operadores más activos
   - Distribución temporal (días/horas)
   - Notas y observaciones más relevantes

4. **Tendencias y Patrones**:
   - ¿Qué tendencias se observan?
   - ¿Hay patrones preocupantes?
   - ¿Qué señales de alerta existen?

5. **Plan de Acción Estratégico**:
   - 3 Quick wins (acción inmediata)
   - 3 Iniciativas de mediano plazo
   - Metas cuantificadas de mejora

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

                // Recolectar datos
                const payload = collectCurrentRows();

                if (!payload.csv || payload.rowCount === 0) {
                    alert('No hay datos disponibles para analizar.');
                    return;
                }

                // Construir prompt completo con datos CSV
                const combinedPrompt = `${config.prompt}

=== DATOS CSV ===
${payload.csv}

=== Filtros Aplicados ===
Línea: ${payload.filters.line || 'Todas'}
Operador: ${payload.filters.operator || 'Todos'}
Fecha desde: ${payload.filters.date_from || 'Sin límite'}
Fecha hasta: ${payload.filters.date_to || 'Sin límite'}

Total de confirmaciones: ${payload.rowCount}`;

                // Guardar datos del prompt actual
                currentPromptData = {
                    type: analysisType,
                    title: config.title,
                    prompt: config.prompt,
                    data: payload,
                    combinedPrompt: combinedPrompt
                };

                // Mostrar modal con prompt editable
                $('#aiPrompt').val(combinedPrompt);
                const modal = new bootstrap.Modal(document.getElementById('aiPromptModal'));
                modal.show();
            });

            // Botón "Restaurar prompt original"
            $('#btn-ai-reset').on('click', function() {
                if (currentPromptData && currentPromptData.combinedPrompt) {
                    $('#aiPrompt').val(currentPromptData.combinedPrompt);
                }
            });

            // Send from modal
            $('#btn-ai-send').on('click', function(){
                const prompt = ($('#aiPrompt').val() || '').trim();
                if (!prompt) {
                    alert('El prompt no puede estar vacío');
                    return;
                }
                startAiTask(prompt);
            });
        });
    </script>
    <style>
        .legend-swatch {
            width: 18px;
            height: 18px;
            border-radius: 3px;
            display: inline-block;
        }

        .info-pagination-container {
            gap: 1rem;
        }

        .info-pagination-container .dataTables_info {
            margin-bottom: 0;
            white-space: nowrap;
        }

        .info-pagination-container .dataTables_paginate {
            margin-left: auto;
            margin-bottom: 0;
        }
    </style>
    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i>@lang('Análisis IA')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">@lang('¿Qué necesitas analizar?')</label>
                    <textarea class="form-control" id="aiPrompt" rows="4" placeholder="@lang('Describe qué análisis quieres sobre las confirmaciones mostradas')"></textarea>
                </div>
                <div class="modal-footer">
                    <div id="aiLoadingIndicator" class="d-none me-auto d-flex align-items-center text-primary small">
                        <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                        <span>{{ __('Analizando con IA...') }}</span>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">@lang('Limpiar prompt por defecto')</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send">@lang('Enviar a IA')</button>
                </div>
            </div>
        </div>
    </div>
    <!-- AI Result Modal -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Resultado IA')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted"><strong>@lang('Prompt'):</strong> <span id="aiResultPrompt"></span></p>
                    <pre id="aiResultData" class="bg-light p-3 rounded" style="white-space: pre-wrap;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
@endpush

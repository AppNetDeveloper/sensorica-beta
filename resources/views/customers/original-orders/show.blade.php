@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <style>
        /* Mini‑tarjetas 2x2 compactas */
        .mini-cards-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem .5rem; align-items: start; }
        .mini-card { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .45rem; border-radius: .375rem; font-size: .78rem; line-height: 1rem; width: 100%; justify-content: flex-start; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mini-card i { font-size: .85em; }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">@lang('Order Details') - {{ $originalOrder->order_id }}</h3>
                    <div>
                        <a href="{{ route('customers.original-orders.index', $customer->id) }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> @lang('Back to List')
                        </a>
                        @can('original-order-edit')
                        <a href="{{ route('customers.original-orders.edit', [$customer->id, $originalOrder->id]) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> @lang('Edit')
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>@lang('Order Information')</h4>
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th class="bg-light">@lang('Order ID')</th>
                                    <td>{{ $originalOrder->order_id }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Client Name')</th>
                                    <td>{{ $originalOrder->client_number }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Processed')</th>
                                    <td>
                                        @if($originalOrder->processed)
                                            <span class="badge bg-success">@lang('Yes')</span>
                                        @else
                                            <span class="badge bg-warning">@lang('No')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Delivery Date')</th>
                                    <td>
                                        @if($originalOrder->delivery_date)
                                            {{ $originalOrder->delivery_date->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-muted">@lang('Not specified')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Stock Status')</th>
                                    <td>
                                        @if($originalOrder->in_stock)
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> @lang('In Stock')</span>
                                        @else
                                            <span class="badge bg-warning"><i class="fas fa-exclamation-circle"></i> @lang('Out of Stock')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Created At')</th>
                                    <td>{{ $originalOrder->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Fecha Pedido ERP')</th>
                                    <td>
                                        @if($originalOrder->fecha_pedido_erp)
                                            {{ $originalOrder->fecha_pedido_erp->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-muted">@lang('Sin fecha ERP')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Updated At')</th>
                                    <td>{{ $originalOrder->updated_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Finished At')</th>
                                    <td>
                                        @if($originalOrder->finished_at)
                                            <span class="badge bg-success">{{ $originalOrder->finished_at->format('Y-m-d H:i') }}</span>
                                        @else
                                            <span class="badge bg-info">@lang('Pending')</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4>@lang('Order Details')</h4>
                            <div class="bg-light p-3 rounded border">
                                @php
                                    // Compatibilidad con ambos formatos: string JSON y array
                                    if (is_string($originalOrder->order_details)) {
                                        // Si es string, intentar decodificar JSON
                                        $details = json_decode($originalOrder->order_details, true);
                                    } elseif (is_array($originalOrder->order_details)) {
                                        // Si ya es array, usar directamente
                                        $details = $originalOrder->order_details;
                                    } else {
                                        // Fallback para otros tipos
                                        $details = null;
                                    }
                                @endphp
                                @if(is_array($details) && !empty($details))
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($details, JSON_PRETTY_PRINT) }}</pre>
                                @elseif(is_string($originalOrder->order_details))
                                    {{-- Mostrar string JSON tal como está si no se pudo decodificar --}}
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ $originalOrder->order_details }}</pre>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> @lang('No order details available')
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h4>@lang('Associated Processes')</h4>
                        
                        {{-- Leyenda visual para los iconos de tiempo y fechas estimadas --}}
                        <div class="mb-3 p-3 border rounded bg-light">
                            <h5 class="mb-2"><i class="fas fa-info-circle"></i> @lang('Leyenda de indicadores')</h5>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-info me-2"><i class="fas fa-hourglass-half"></i></span>
                                    <span>@lang('Tiempo de ocupación máquina')</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-2"><i class="fas fa-hourglass-start"></i></span>
                                    <span>@lang('Fecha estimada de inicio')</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success me-2"><i class="fas fa-hourglass-end"></i></span>
                                    <span>@lang('Fecha estimada de fin')</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="bg-light">
                                    <tr>
                                        <th>@lang('Code')</th>
                                        <th>@lang('Process')</th>
                                        <th>@lang('Sequence')</th>
                                        <th>@lang('Correction Factor')</th>
                                        <th>@lang('Time')</th>
                                        <th>@lang('Created')</th>
                                        <th>@lang('Process Status')</th>
                                        <th>@lang('Stock Status')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($originalOrder->processes->sortBy('sequence') as $process)
                                        @php
                                            $pivot = $process->pivot;
                                            $articles = $pivot->articles ?? collect();
                                            
                                            // Depurar los valores del pivot
                                            // Forzar la conversión a boolean
                                            $isFinished = (bool)$pivot->finished;
                                            
                                            $debugInfo = "Process ID: {$process->id}, Code: {$process->code}, ";
                                            $debugInfo .= "Pivot ID: {$pivot->id}, ";
                                            $debugInfo .= "finished (raw): {$pivot->finished}, ";
                                            $debugInfo .= "finished (bool): " . ($isFinished ? 'true' : 'false') . ", ";
                                            $debugInfo .= "finished_at: " . ($pivot->finished_at ?? 'null');
                                            
                                            // Asignar el valor convertido de vuelta al pivot
                                            $pivot->finished = $isFinished;
                                            
                                            // Escribir en el log para depuración
                                            \Log::info($debugInfo);
                                        @endphp
                                        <!-- Debug: {{ $debugInfo }} -->
                                        <tr>
                                            <td>{{ $process->code }} @if($pivot->grupo_numero)(Grupo {{ $pivot->grupo_numero }})@endif</td>
                                            <td>{{ $process->name }}</td>
                                            <td class="text-center">{{ $process->sequence }}</td>
                                            <td class="text-center">{{ number_format($process->factor_correccion, 2) }}</td>
                                            <!---<td class="text-center">{{ $pivot->time ? number_format($pivot->time, 2) . 'Seg' : '-' }}</td> tenemos que ponerlo como viene en segundos que lo formateamos a formato 00:00:00 -->
                                            <td class="text-center">{{ $pivot->time
                                                ? sprintf(
                                                    "%02d:%02d:%02d",
                                                    floor($pivot->time / 3600), // Horas
                                                    floor(($pivot->time / 60) % 60), // Minutos
                                                    $pivot->time % 60 // Segundos
                                                )
                                                : '-'
                                            }}</td>
                                            <td class="text-center">
                                                @if($pivot->created)
                                                    <span class="badge bg-success">@lang('Yes')</span>
                                                @else
                                                    <span class="badge bg-warning">@lang('No')</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $productionOrder = $pivot->productionOrders->first();
                                                    $status = $productionOrder ? $productionOrder->status : null;
                                                    $productionLineId = $productionOrder ? $productionOrder->production_line_id : null;
                                                    
                                                    if ($pivot->finished) {
                                                        $statusText = $pivot->finished_at ? $pivot->finished_at->format('Y-m-d H:i') : __('Finalizado');
                                                        $badgeClass = 'bg-success';
                                                    } else {
                                                        if ($status === 0) {
                                                            if (is_null($productionLineId)) {
                                                                $statusText = __('Sin asignar');
                                                                $badgeClass = 'bg-secondary';
                                                            } else {
                                                                $statusText = __('Asignada a máquina');
                                                                $badgeClass = 'bg-info';
                                                                
                                                                // Guardar el tiempo acumulado si existe
                                                                $accumulatedTime = $productionOrder->accumulated_time ?? null;
                                                            }
                                                        } elseif ($status === 1) {
                                                            $statusText = __('En fabricación');
                                                            $badgeClass = 'bg-primary';
                                                            
                                                            // Guardar el tiempo acumulado si existe
                                                            $accumulatedTime = $productionOrder->accumulated_time ?? null;
                                                        } elseif ($status > 2) {
                                                            $statusText = __('Con incidencia');
                                                            $badgeClass = 'bg-danger';
                                                        } else {
                                                            $statusText = __('Pendiente');
                                                            $badgeClass = 'bg-warning';
                                                        }
                                                    }
                                                @endphp
                                                <div class="mini-cards-grid mt-1">
                                                    {{-- Estado actual siempre presente como mini‑tarjeta --}}
                                                    <span class="badge {{ $badgeClass }} mini-card" title="@lang('Estado actual')">
                                                        {{ $statusText }}
                                                    </span>

                                                    {{-- Tiempo de ocupación máquina (solo si aplica) --}}
                                                    @if(($status === 1 || ($status === 0 && !is_null($productionLineId))))
                                                        <span class="badge bg-info mini-card" title="@lang('Tiempo de ocupación máquina')">
                                                            <i class="fas fa-hourglass-half"></i>
                                                            @if($productionOrder && !is_null($productionOrder->accumulated_time))
                                                                @php
                                                                    $seconds = (int)$productionOrder->accumulated_time;
                                                                    $hours = floor($seconds / 3600);
                                                                    $minutes = floor(($seconds % 3600) / 60);
                                                                    $secs = $seconds % 60;
                                                                    $formattedTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
                                                                @endphp
                                                                {{ $formattedTime }}
                                                            @else
                                                                @lang('Sin tiempo acumulado')
                                                            @endif
                                                        </span>

                                                        {{-- Fechas estimadas --}}
                                                        @if($productionOrder && $productionOrder->estimated_start_datetime)
                                                            <span class="badge bg-primary mini-card" title="@lang('Fecha estimada de inicio')">
                                                                <i class="fas fa-hourglass-start"></i>
                                                                {{ \Carbon\Carbon::parse($productionOrder->estimated_start_datetime)->format('d/m/Y H:i') }}
                                                            </span>
                                                        @endif
                                                        @if($productionOrder && $productionOrder->estimated_end_datetime)
                                                            <span class="badge bg-success mini-card" title="@lang('Fecha estimada de fin')">
                                                                <i class="fas fa-hourglass-end"></i>
                                                                {{ \Carbon\Carbon::parse($productionOrder->estimated_end_datetime)->format('d/m/Y H:i') }}
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($pivot->in_stock === 0)
                                                    <span class="badge bg-danger">@lang('Sin Stock')</span>
                                                @elseif($pivot->in_stock === 1)
                                                    <span class="badge bg-success">@lang('Con Stock')</span>
                                                @else
                                                    <span class="badge bg-secondary">@lang('No Especificado')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="process-files-row">
                                            <td colspan="8">
                                                <div class="mt-2 p-2 border rounded bg-white">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <strong>@lang('Process Files')</strong>
                                                        @can('original-order-edit')
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="file" accept="image/*,application/pdf" multiple class="form-control form-control-sm me-2"
                                                               data-file-input
                                                               data-upload-url="{{ route('customers.original-orders.processes.files.store', [$customer->id, $originalOrder->id, $pivot->id]) }}">
                                                            <button type="button" class="btn btn-sm btn-primary" data-upload-btn>
                                                                <i class="fas fa-upload"></i> @lang('Upload')
                                                            </button>
                                                        </div>
                                                        @endcan
                                                    </div>
                                                    <div class="small text-muted mb-2">@lang('Allowed types'): JPG, PNG, GIF, WEBP, PDF. @lang('Max') 10MB</div>
                                                    <div class="row" data-files-container
                                                         data-index-url="{{ route('customers.original-orders.processes.files.index', [$customer->id, $originalOrder->id, $pivot->id]) }}"
                                                    ></div>
                                                </div>
                                            </td>
                                        </tr>
                                        @if($articles->isNotEmpty())
                                            <tr class="articles-row">
                                                <td colspan="8" class="p-3 bg-light" style="border-top: 1px solid #e9ecef;">
                                                    <h6 class="mb-3 font-weight-bold"><i class="fas fa-cubes text-secondary mr-2"></i> @lang('Related Articles')</h6>
                                                    <table class="table table-sm table-hover mb-0 bg-white rounded">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th style="width: 25%;">@lang('Article Code')</th>
                                                                <th style="width: 45%;">@lang('Description')</th>
                                                                <th style="width: 15%;">@lang('Group')</th>
                                                                <th style="width: 15%;">@lang('Stock Status')</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($articles as $article)
                                                                <tr>
                                                                    <td>{{ $article->codigo_articulo }}</td>
                                                                    <td>{{ $article->descripcion_articulo }}</td>
                                                                    <td>{{ $article->grupo_articulo }}</td>
                                                                    <td class="text-center">
                                                                        @if($article->in_stock === 0)
                                                                            <span class="badge bg-danger">@lang('Sin Stock')</span>
                                                                        @elseif($article->in_stock === 1)
                                                                            <span class="badge bg-success">@lang('Con Stock')</span>
                                                                        @else
                                                                            <span class="badge bg-secondary">@lang('No Especificado')</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">@lang('No processes associated with this order.')</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal for Process Files -->
<div class="modal fade" id="processFileDeleteModal" tabindex="-1" role="dialog" aria-labelledby="processFileDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="processFileDeleteModalLabel">@lang('Delete File')</h5>
        <button type="button" class="btn btn-link text-muted p-0" style="font-size:1.1rem; margin-left:auto;" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" title="@lang('Close')">
            <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        @lang('Are you sure you want to delete this file?')
        <div class="small text-muted mt-2" data-delete-filename></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('Cancel')</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteProcessFile">@lang('Delete')</button>
      </div>
    </div>
  </div>
  </div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let deleteCtx = { url: null, container: null, filename: '' };
    const MAX_FILES = 8;

    function getBootstrapModal(el) {
        // Support Bootstrap 5 and 4
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
        // Fallback: simple toggle
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
            container.innerHTML = '<div class="col-12 text-muted">@lang('No files uploaded yet.')</div>';
            // update count and enable controls
            container.dataset.count = 0;
            const wrapper = container.closest('.border');
            if (wrapper) {
                const input = wrapper.querySelector('[data-file-input]');
                const btn = wrapper.querySelector('[data-upload-btn]');
                if (input) input.disabled = false;
                if (btn) btn.disabled = false;
            }
            return;
        }
        // Save count and toggle controls depending on limit
        const maxFiles = MAX_FILES;
        container.dataset.count = files.length;
        const wrapperForLimit = container.closest('.border');
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
                preview = `<a href="${f.public_url}" target="_blank"><img src="${f.public_url}" class="img-fluid rounded border" alt="${f.original_name}"></a>`;
            } else if (isPdf) {
                preview = `<a href="${f.public_url}" target="_blank" class="btn btn-outline-secondary btn-sm w-100"><i class="far fa-file-pdf"></i> PDF</a>`;
            } else {
                preview = `<a href="${f.public_url}" target="_blank" class="btn btn-outline-secondary btn-sm w-100"><i class="far fa-file"></i> ${f.extension || ''}</a>`;
            }
            col.innerHTML = `
                <div class="border rounded p-2 h-100 d-flex flex-column">
                    <div class="flex-grow-1 mb-2" style="min-height:70px">${preview}</div>
                    <div class="small text-truncate" title="${f.original_name}"><i class="far fa-file"></i> ${f.original_name}</div>
                    <div class="text-muted small">${formatBytes(f.size)} · ${f.created_at ? f.created_at : ''}</div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <a href="${f.public_url}" target="_blank" class="btn btn-link btn-sm p-0">@lang('Open')</a>
                        <button type="button" class="btn btn-link btn-sm p-0" data-copy data-url="${f.public_url}">@lang('Copy link')</button>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0" data-delete data-id="${f.id}" data-name="${f.original_name}">@lang('Delete')</button>
                    </div>
                </div>`;
            container.appendChild(col);
        });

        // Copy link handlers
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
                    btn.textContent = '@lang('Copied!')';
                    setTimeout(() => btn.textContent = '@lang('Copy link')', 1500);
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
            container.innerHTML = '<div class="col-12 text-danger">@lang('Error loading files')</div>';
        }
    }

    // Initialize per process widgets
    document.querySelectorAll('[data-files-container]').forEach(container => {
        loadFiles(container);
        const wrapper = container.closest('.border');
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
                    btn.disabled = false; input.disabled = false; btn.innerHTML = '<i class="fas fa-upload"></i> ' + '@lang('Upload')';
                }
            });
        }
    });

    // Confirm delete action handler
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

    // Ensure modal closes on cancel/close for BS4/BS5 and fallback
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
        attach('.close');
        // Also close on backdrop click (BS5 handles; fallback emulate)
        modalEl.addEventListener('click', (e) => {
            if (e.target === modalEl) {
                modal.hide();
            }
        });
    })();
});
</script>
@endpush
@endsection

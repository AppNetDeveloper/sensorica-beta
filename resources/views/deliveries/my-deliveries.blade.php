@extends('layouts.admin')

@section('title', __('My Deliveries'))
@section('page-title', __('My Deliveries'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item active">{{ __('My Deliveries') }}</li>
  </ul>
</div>
@endsection

@push('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<style>
  .delivery-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
  }
  
  .delivery-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
  }
  
  .delivery-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .delivery-body {
    padding: 20px;
  }
  
  .order-item {
    background: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 12px 15px;
    margin-bottom: 10px;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
  }
  
  .order-item.delivered {
    background: #d4edda;
    border-left-color: #28a745;
    opacity: 0.7;
  }
  
  .order-item:hover:not(.delivered) {
    background: #e7f1ff;
  }
  
  .vehicle-badge {
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
  }
  
  .stats-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
  }
  
  .stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  }
  
  .stats-card.total {
    border-color: #667eea;
  }
  
  .stats-card.delivered {
    border-color: #28a745;
  }
  
  .stats-card.pending {
    border-color: #ffc107;
  }
  
  .stats-card.vehicles {
    border-color: #17a2b8;
  }
  
  .stats-number {
    font-size: 42px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 8px;
  }
  
  .stats-card.total .stats-number {
    color: #667eea;
  }
  
  .stats-card.delivered .stats-number {
    color: #28a745;
  }
  
  .stats-card.pending .stats-number {
    color: #ffc107;
  }
  
  .stats-card.vehicles .stats-number {
    color: #17a2b8;
  }
  
  .stats-label {
    font-size: 13px;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .stats-icon {
    font-size: 24px;
    margin-bottom: 10px;
    opacity: 0.8;
  }
  
  .btn-deliver {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
  }
  
  .btn-deliver:hover {
    background: #218838;
    transform: scale(1.05);
  }
  
  .btn-deliver:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
  }
  
  @media (max-width: 768px) {
    .delivery-card {
      margin-bottom: 10px;
    }
    
    .delivery-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
  }
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('My Deliveries') }}</h5>
    <div>
      <input type="date" id="dateSelector" class="form-control" style="min-width: 180px;" value="{{ $date->format('Y-m-d') }}">
    </div>
  </div>
  <div class="card-body">
  @if($assignments->count() > 0)
    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
      <div class="col-lg-3 col-md-6">
        <div class="stats-card total">
          <div class="stats-icon"><i class="fas fa-map-marker-alt" style="color: #667eea;"></i></div>
          <div class="stats-number">{{ $deliveries->count() }}</div>
          <div class="stats-label">{{ __('Total Stops') }}</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stats-card delivered">
          <div class="stats-icon"><i class="fas fa-check-circle" style="color: #28a745;"></i></div>
          <div class="stats-number" id="deliveredCount">
            {{ $deliveries->sum(function($d) { 
              return $d['client']->orderAssignments->filter(function($o) { 
                return $o->originalOrder->actual_delivery_date != null; 
              })->count(); 
            }) }}
          </div>
          <div class="stats-label">{{ __('Delivered') }}</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stats-card pending">
          <div class="stats-icon"><i class="fas fa-clock" style="color: #ffc107;"></i></div>
          <div class="stats-number" id="pendingCount">
            {{ $deliveries->sum(function($d) { 
              return $d['client']->orderAssignments->filter(function($o) { 
                return $o->originalOrder->actual_delivery_date == null; 
              })->count(); 
            }) }}
          </div>
          <div class="stats-label">{{ __('Pending') }}</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stats-card vehicles">
          <div class="stats-icon"><i class="fas fa-truck" style="color: #17a2b8;"></i></div>
          <div class="stats-number">{{ $assignments->count() }}</div>
          <div class="stats-label">{{ __('Vehicles') }}</div>
        </div>
      </div>
    </div>

    {{-- Delivery Cards --}}

    @foreach($deliveries as $index => $delivery)
      @php
        $assignment = $delivery['assignment'];
        $clientAssignment = $delivery['client'];
        $client = $clientAssignment->customerClient;
        $orders = $clientAssignment->orderAssignments;
      @endphp
      
      <div class="delivery-card">
        <div class="delivery-header d-flex justify-content-between align-items-center">
          <div class="flex-grow-1">
            <h5 class="mb-1">{{ $index + 1 }}. {{ $client->name }}</h5>
            @if($client->address)
              <small style="opacity: 0.9;">📍 {{ $client->address }}</small>
            @else
              <small style="opacity: 0.7;">📍 {{ __('No address') }}</small>
            @endif
          </div>
          <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @if($client->phone)
              <a href="tel:{{ $client->phone }}" class="btn btn-sm btn-light" title="{{ __('Call') }}: {{ $client->phone }}" style="border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-phone" style="color: #28a745;"></i>
              </a>
            @endif
            @if($client->address)
              <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($client->address) }}" target="_blank" class="btn btn-sm btn-light" title="{{ __('Open in Maps') }}" style="border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
              </a>
            @endif
            <div class="vehicle-badge">
              🚚 {{ $assignment->fleetVehicle->plate }}
            </div>
          </div>
        </div>
        
        <div class="delivery-body">
          <h6 class="mb-3">{{ __('Orders') }} ({{ $orders->count() }})</h6>
          
          @foreach($orders as $orderAssignment)
            @php
              $order = $orderAssignment->originalOrder;
              $isDelivered = $order->actual_delivery_date != null;
            @endphp
            
            <div class="order-item {{ $isDelivered ? 'delivered' : '' }}" data-order-id="{{ $order->id }}">
              <div>
                <strong class="order-id-link" style="cursor: pointer; text-decoration: underline; color: #0d6efd;" data-order-id="{{ $order->id }}">
                  {{ $order->order_id }}
                </strong>
                @if($order->delivery_date)
                  <br><small class="text-muted">📅 {{ $order->delivery_date->format('d/m/Y') }}</small>
                @endif
                @if($isDelivered)
                  <br><small class="text-success">✓ {{ __('Delivered') }}: {{ $order->actual_delivery_date->format('d/m H:i') }}</small>
                @endif
              </div>
              <div>
                @if(!$isDelivered)
                  <button class="btn-deliver open-proof-modal" data-order-id="{{ $order->id }}">
                    <i class="fas fa-clipboard-check"></i> {{ __('Deliver') }}
                  </button>
                @else
                  <span class="badge bg-success">✓ {{ __('Delivered') }}</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  @else
    <div class="alert alert-info text-center" style="padding: 60px;">
      <div style="font-size: 64px; margin-bottom: 20px;">📦</div>
      <h4>{{ __('No deliveries assigned for this date') }}</h4>
      <p class="text-muted">{{ __('Select another date or contact your supervisor') }}</p>
    </div>
  @endif
  </div>
</div>

{{-- Modal de Firma y Fotos --}}
<div class="modal fade" id="deliveryProofModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> {{ __('Delivery Proof') }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="proofOrderId">
        
        {{-- Firma Digital --}}
        <div class="mb-4">
          <label class="form-label fw-bold"><i class="fas fa-signature"></i> {{ __('Customer Signature') }}</label>
          <div style="border: 2px solid #dee2e6; border-radius: 8px; background: #f8f9fa;">
            <canvas id="signaturePad" width="700" height="300" style="width: 100%; height: 300px; cursor: crosshair;"></canvas>
          </div>
          <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
              <i class="fas fa-eraser"></i> {{ __('Clear Signature') }}
            </button>
          </div>
        </div>

        {{-- Fotos --}}
        <div class="mb-4">
          <label class="form-label fw-bold"><i class="fas fa-camera"></i> {{ __('Delivery Photos') }}</label>
          <input type="file" class="form-control" id="deliveryPhotos" accept="image/*" multiple capture="environment">
          <small class="text-muted">{{ __('You can select multiple photos') }} ({{ __('Max') }}: 5MB {{ __('each') }})</small>
          <div id="photoPreview" class="mt-3 d-flex flex-wrap gap-2"></div>
        </div>

        {{-- Notas --}}
        <div class="mb-3">
          <label class="form-label fw-bold"><i class="fas fa-sticky-note"></i> {{ __('Delivery Notes') }}</label>
          <textarea class="form-control" id="deliveryNotes" rows="3" placeholder="{{ __('Optional notes about the delivery...') }}" maxlength="1000"></textarea>
          <small class="text-muted"><span id="notesCount">0</span>/1000</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-success" id="confirmDelivery">
          <i class="fas fa-check"></i> {{ __('Confirm Delivery') }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal de Albarán de Entrega --}}
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-file-invoice"></i> {{ __('Delivery Note') }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="orderDetailsContent">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Loading...') }}</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Inicializar SignaturePad
  const canvas = document.getElementById('signaturePad');
  let signaturePad = null;

  // Función para inicializar el canvas correctamente
  function initSignaturePad() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    canvas.style.width = rect.width + 'px';
    canvas.style.height = rect.height + 'px';
    
    const ctx = canvas.getContext("2d");
    ctx.scale(ratio, ratio);
    
    signaturePad = new SignaturePad(canvas, {
      backgroundColor: 'rgb(255, 255, 255)',
      penColor: 'rgb(0, 0, 0)',
      minWidth: 1,
      maxWidth: 3
    });
  }

  // Inicializar cuando se abre el modal
  document.getElementById('deliveryProofModal').addEventListener('shown.bs.modal', function() {
    if (!signaturePad) {
      initSignaturePad();
    }
  });

  window.addEventListener("resize", function() {
    if (signaturePad) {
      const data = signaturePad.toData();
      initSignaturePad();
      signaturePad.fromData(data);
    }
  });

  // Limpiar firma
  document.getElementById('clearSignature').addEventListener('click', function() {
    if (signaturePad) {
      signaturePad.clear();
    }
  });

  // Preview de fotos
  document.getElementById('deliveryPhotos').addEventListener('change', function(e) {
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';
    
    Array.from(e.target.files).forEach((file, index) => {
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const div = document.createElement('div');
          div.style.cssText = 'position: relative; width: 100px; height: 100px;';
          div.innerHTML = `
            <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6;">
            <button type="button" class="btn btn-sm btn-danger remove-photo" data-index="${index}" style="position: absolute; top: -5px; right: -5px; border-radius: 50%; width: 25px; height: 25px; padding: 0;">
              <i class="fas fa-times"></i>
            </button>
          `;
          preview.appendChild(div);
        };
        reader.readAsDataURL(file);
      }
    });
  });

  // Remover foto
  document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-photo')) {
      const index = parseInt(e.target.closest('.remove-photo').dataset.index);
      const input = document.getElementById('deliveryPhotos');
      const dt = new DataTransfer();
      
      Array.from(input.files).forEach((file, i) => {
        if (i !== index) dt.items.add(file);
      });
      
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    }
  });

  // Contador de caracteres en notas
  document.getElementById('deliveryNotes').addEventListener('input', function() {
    document.getElementById('notesCount').textContent = this.value.length;
  });

  // Cambio de fecha
  document.getElementById('dateSelector').addEventListener('change', function() {
    const newDate = this.value;
    window.location.href = '{{ route("deliveries.my-deliveries") }}?date=' + newDate;
  });

  // Click en número de pedido para ver albarán
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('order-id-link')) {
      const orderId = e.target.dataset.orderId;
      showOrderDetails(orderId);
    }
  });

  // Función para mostrar detalles del pedido
  function showOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    // Mostrar loading
    content.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">{{ __('Loading...') }}</span>
        </div>
      </div>
    `;
    
    modal.show();
    
    // Cargar datos
    fetch(`/deliveries/order-details/${orderId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const order = data.order;
          let html = `
            <div class="delivery-note">
              <div class="row mb-4">
                <div class="col-md-6">
                  <h6 class="text-muted mb-2">{{ __('Order Information') }}</h6>
                  <table class="table table-sm table-borderless">
                    <tr><td class="fw-bold">{{ __('Order ID') }}:</td><td>${order.order_id}</td></tr>
                    <tr><td class="fw-bold">{{ __('Client') }}:</td><td>${order.client_name}</td></tr>
                    ${order.client_number ? `<tr><td class="fw-bold">{{ __('Client Number') }}:</td><td>${order.client_number}</td></tr>` : ''}
                  </table>
                </div>
                <div class="col-md-6">
                  <h6 class="text-muted mb-2">{{ __('Delivery Information') }}</h6>
                  <table class="table table-sm table-borderless">
                    ${order.delivery_date ? `<tr><td class="fw-bold">{{ __('Delivery Date') }}:</td><td>${order.delivery_date}</td></tr>` : ''}
                    ${order.estimated_delivery_date ? `<tr><td class="fw-bold">{{ __('Estimated Date') }}:</td><td>${order.estimated_delivery_date}</td></tr>` : ''}
                    <tr><td class="fw-bold">{{ __('In Stock') }}:</td><td>${order.in_stock ? '<span class="badge bg-success">{{ __('Yes') }}</span>' : '<span class="badge bg-warning">{{ __('No') }}</span>'}</td></tr>
                  </table>
                </div>
              </div>
              
              <hr>
              
              <h6 class="text-muted mb-3"><i class="fas fa-cogs"></i> {{ __('Processes') }}</h6>
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>{{ __('Group') }}</th>
                      <th>{{ __('Code') }}</th>
                      <th>{{ __('Process') }}</th>
                      <th class="text-end">{{ __('Time') }}</th>
                      <th class="text-end">{{ __('Boxes') }}</th>
                      <th class="text-end">{{ __('Units/Box') }}</th>
                      <th class="text-end">{{ __('Pallets') }}</th>
                    </tr>
                  </thead>
                  <tbody>
          `;
          
          order.processes.forEach(process => {
            html += `
              <tr>
                <td><span class="badge bg-secondary">${process.grupo_numero}</span></td>
                <td><code>${process.code}</code></td>
                <td>${process.name}</td>
                <td class="text-end">${process.time || '-'}</td>
                <td class="text-end">${process.box || 0}</td>
                <td class="text-end">${process.units_box || 0}</td>
                <td class="text-end">${process.number_of_pallets || 0}</td>
              </tr>
            `;
          });
          
          html += `
                  </tbody>
                </table>
              </div>
              
              <hr>
              
              <h6 class="text-muted mb-3"><i class="fas fa-box"></i> {{ __('Articles') }}</h6>
          `;
          
          if (order.articles && order.articles.length > 0) {
            order.articles.forEach(group => {
              html += `
                <div class="mb-3">
                  <h6><span class="badge bg-info">{{ __('Group') }} ${group.grupo_numero}</span></h6>
                  <div class="table-responsive">
                    <table class="table table-sm table-striped">
                      <thead class="table-light">
                        <tr>
                          <th>{{ __('Code') }}</th>
                          <th>{{ __('Description') }}</th>
                          <th class="text-center">{{ __('In Stock') }}</th>
                        </tr>
                      </thead>
                      <tbody>
              `;
              
              group.items.forEach(article => {
                html += `
                  <tr>
                    <td><code>${article.codigo_articulo}</code></td>
                    <td>${article.descripcion_articulo || '-'}</td>
                    <td class="text-center">${article.in_stock ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'}</td>
                  </tr>
                `;
              });
              
              html += `
                      </tbody>
                    </table>
                  </div>
                </div>
              `;
            });
          } else {
            html += '<p class="text-muted">{{ __('No articles found') }}</p>';
          }
          
          html += `
            </div>
          `;
          
          content.innerHTML = html;
        } else {
          content.innerHTML = `<div class="alert alert-danger">{{ __('Error loading order details') }}</div>`;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `<div class="alert alert-danger">{{ __('Error loading order details') }}</div>`;
      });
  }

  // Abrir modal de firma y fotos
  document.addEventListener('click', function(e) {
    if (e.target.closest('.open-proof-modal')) {
      const btn = e.target.closest('.open-proof-modal');
      const orderId = btn.dataset.orderId;
      
      // Guardar el ID del pedido
      document.getElementById('proofOrderId').value = orderId;
      
      // Limpiar el modal
      if (signaturePad) {
        signaturePad.clear();
      }
      document.getElementById('deliveryPhotos').value = '';
      document.getElementById('photoPreview').innerHTML = '';
      document.getElementById('deliveryNotes').value = '';
      document.getElementById('notesCount').textContent = '0';
      
      // Abrir modal
      const modal = new bootstrap.Modal(document.getElementById('deliveryProofModal'));
      modal.show();
    }
  });

  // Confirmar entrega con firma yotos
  document.getElementById('confirmDelivery').addEventListener('click', function() {
    const orderId = document.getElementById('proofOrderId').value;
    const notes = document.getElementById('deliveryNotes').value;
    const photosInput = document.getElementById('deliveryPhotos');
    
    // Crear FormData
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('notes', notes);
    
    // Añadir firma si existe
    if (!signaturePad.isEmpty()) {
      formData.append('signature', signaturePad.toDataURL());
    }
    
    // Añadir fotos si existen
    if (photosInput.files.length > 0) {
      Array.from(photosInput.files).forEach((file, index) => {
        formData.append(`photos[${index}]`, file);
      });
    }
    
    // Deshabilitar botón
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Processing...') }}';
    
    // Enviar
    fetch('{{ route("deliveries.mark-delivered") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('deliveryProofModal')).hide();
        
        // Recargar página para actualizar
        location.reload();
      } else {
        alert(data.message);
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check"></i> {{ __('Confirm Delivery') }}';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al confirmar entrega');
      this.disabled = false;
      this.innerHTML = '<i class="fas fa-check"></i> {{ __('Confirm Delivery') }}';
    });
  });
});
</script>
@endpush

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  /* Fondo moderno con gradiente */
  body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
  }

  .delivery-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
  }

  .delivery-card:hover {
    box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3);
    transform: translateY(-5px);
  }

  .delivery-card::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }

  .delivery-card:hover::before {
    transform: scaleX(1);
  }
  
  .delivery-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
  }

  .delivery-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
  }

  @keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(-20px, -20px) rotate(180deg); }
  }

  .delivery-header h5 {
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .delivery-body {
    padding: 20px;
  }
  
  .order-item {
    background: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 15px 20px;
    margin-bottom: 12px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .order-item::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, #0d6efd 0%, #00f2fe 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }

  .order-item:hover:not(.delivered)::after {
    transform: scaleX(1);
  }

  .order-item.delivered {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-left-color: #28a745;
    opacity: 0.8;
  }

  .order-item:hover:not(.delivered) {
    background: linear-gradient(135deg, #e7f1ff 0%, #d4e9ff 100%);
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
  }
  
  .vehicle-badge {
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(10px);
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 700;
    border: 2px solid rgba(255,255,255,0.3);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
  }

  .vehicle-badge:hover {
    background: rgba(255,255,255,0.35);
    transform: scale(1.05);
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
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .btn-deliver::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
  }

  .btn-deliver:hover::before {
    width: 300px;
    height: 300px;
  }

  .btn-deliver:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea87a 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  }

  .btn-deliver:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }

  /* Botón Entregar Todos - estilo especial */
  .open-proof-modal-all {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-right: 10px;
  }

  .open-proof-modal-all::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
  }

  .open-proof-modal-all:hover::before {
    width: 300px;
    height: 300px;
  }

  .open-proof-modal-all:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
  }

  /* Botón Ver Todos los Albaranes - estilo especial */
  .btn-view-all-invoices {
    background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-right: 10px;
  }

  .btn-view-all-invoices::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
  }

  .btn-view-all-invoices:hover::before {
    width: 300px;
    height: 300px;
  }

  .btn-view-all-invoices:hover {
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(13, 202, 240, 0.5);
  }

  /* Card principal con glassmorfismo */
  .card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
    overflow: hidden;
  }

  .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: none;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
  }

  .card-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
  }

  .card-header h5 {
    color: white;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  /* Breadcrumb moderno */
  .breadcrumb {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  }

  .breadcrumb-item a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .breadcrumb-item a:hover {
    color: #764ba2;
    transform: translateX(3px);
  }

  .breadcrumb-item.active {
    color: #6c757d;
    font-weight: 600;
  }

  /* Loading overlay personalizado */
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }

  .loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Animaciones de entrada para las delivery cards */
  @keyframes slideInUp {
    0% {
      opacity: 0;
      transform: translateY(30px);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .delivery-card {
    animation: slideInUp 0.5s ease-out;
    animation-fill-mode: both;
  }

  .delivery-card:nth-child(1) { animation-delay: 0.1s; }
  .delivery-card:nth-child(2) { animation-delay: 0.2s; }
  .delivery-card:nth-child(3) { animation-delay: 0.3s; }
  .delivery-card:nth-child(4) { animation-delay: 0.4s; }
  .delivery-card:nth-child(5) { animation-delay: 0.5s; }

  /* Mejoras en los botones de acción circulares */
  .btn-sm.btn-light {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
  }

  .btn-sm.btn-light:hover {
    background: white;
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }

  /* Modales mejorados */
  .modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.25);
    backdrop-filter: blur(10px);
  }

  .modal-header {
    border-bottom: none;
    border-radius: 20px 20px 0 0;
    position: relative;
    overflow: hidden;
  }

  .modal-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
  }

  .modal-header.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  }

  .modal-header.bg-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
  }

  .modal-header .modal-title {
    position: relative;
    z-index: 1;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .modal-body {
    padding: 2rem;
  }

  .modal-footer {
    border-top: none;
    padding: 1rem 2rem 2rem;
  }

  .modal-footer .btn {
    border-radius: 12px;
    padding: 0.8rem 2rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
  }

  .modal-footer .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
  }

  .modal-footer .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  }

  .modal-footer .btn-secondary {
    background: #6c757d;
    border: none;
  }

  .modal-footer .btn-secondary:hover {
    background: #5c636a;
    transform: translateY(-2px);
  }

  .form-control {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
  }

  .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  /* Alerta de información mejorada */
  .alert-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: none;
    border-radius: 15px;
    padding: 2rem;
    margin-top: 2rem;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.1);
    border-left: 5px solid #2196f3;
  }

  /* Estilos para vista de múltiples albaranes */
  .all-delivery-notes .delivery-note {
    animation: slideInUp 0.5s ease-out;
    animation-fill-mode: both;
  }

  .all-delivery-notes .delivery-note:nth-child(2) { animation-delay: 0.1s; }
  .all-delivery-notes .delivery-note:nth-child(3) { animation-delay: 0.2s; }
  .all-delivery-notes .delivery-note:nth-child(4) { animation-delay: 0.3s; }
  .all-delivery-notes .delivery-note:nth-child(5) { animation-delay: 0.4s; }

  /* Scroll suave para modales con múltiples albaranes */
  #orderDetailsModal .modal-dialog {
    max-width: 900px;
  }

  #orderDetailsModal .modal-body {
    max-height: 75vh;
    overflow-y: auto;
  }

  /* Scrollbar personalizado */
  #orderDetailsModal .modal-body::-webkit-scrollbar {
    width: 8px;
  }

  #orderDetailsModal .modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  #orderDetailsModal .modal-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
  }

  #orderDetailsModal .modal-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  }

  @media (max-width: 768px) {
    .delivery-card {
      margin-bottom: 15px;
    }

    .delivery-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }

    .delivery-header .d-flex.gap-2 {
      width: 100%;
      justify-content: flex-start;
    }

    .btn-view-all-invoices,
    .open-proof-modal-all {
      font-size: 12px;
      padding: 8px 16px;
      margin-bottom: 5px;
    }

    .stats-card {
      margin-bottom: 1rem;
    }

    .modal-body {
      padding: 1.5rem;
    }

    #orderDetailsModal .modal-dialog {
      max-width: 95%;
      margin: 1rem;
    }

    #orderDetailsModal .modal-body {
      max-height: 70vh;
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
          <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
            @php
              $pendingOrders = $orders->filter(function($o) {
                return $o->originalOrder->actual_delivery_date == null;
              });
              $pendingOrderIds = $pendingOrders->pluck('originalOrder.id')->toArray();
              $pendingOrderNames = $pendingOrders->pluck('originalOrder.order_id')->toArray();

              // Todos los pedidos para ver albaranes
              $allOrderIds = $orders->pluck('originalOrder.id')->toArray();
              $allOrderNames = $orders->pluck('originalOrder.order_id')->toArray();
            @endphp

            @if($pendingOrders->count() > 1)
              <button class="btn-deliver open-proof-modal-all"
                      data-order-ids="{{ json_encode($pendingOrderIds) }}"
                      data-order-names="{{ json_encode($pendingOrderNames) }}"
                      data-client-name="{{ $client->name }}"
                      title="{{ __('Deliver all pending orders') }}">
                <i class="fas fa-clipboard-list"></i> {{ __('Deliver All') }} ({{ $pendingOrders->count() }})
              </button>
            @endif

            @if($orders->count() > 1)
              <button class="btn-view-all-invoices"
                      data-order-ids="{{ json_encode($allOrderIds) }}"
                      data-order-names="{{ json_encode($allOrderNames) }}"
                      data-client-name="{{ $client->name }}"
                      title="{{ __('View all delivery notes') }}">
                <i class="fas fa-file-invoice"></i> {{ __('All Notes') }} ({{ $orders->count() }})
              </button>
            @endif

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
        <input type="hidden" id="proofOrderIds">
        <input type="hidden" id="deliveryMode" value="single">

        {{-- Lista de pedidos a entregar (solo visible en modo múltiple) --}}
        <div id="ordersList" style="display: none;" class="mb-4">
          <label class="form-label fw-bold"><i class="fas fa-list"></i> {{ __('Orders to Deliver') }}</label>
          <div id="ordersListContent" class="p-3" style="background: #f8f9fa; border-radius: 10px; border-left: 4px solid #667eea;">
          </div>
        </div>

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
        <button type="button" class="btn btn-info" id="btnEmailDeliveryNote" title="{{ __('Send delivery note by email') }}">
          <i class="fas fa-envelope"></i> {{ __('Send by Email') }}
        </button>
        <button type="button" class="btn btn-success" id="btnDownloadPDF" title="{{ __('Download delivery note as PDF') }}">
          <i class="fas fa-file-pdf"></i> {{ __('Download PDF') }}
        </button>
        <button type="button" class="btn btn-primary" id="btnPrintDeliveryNote" title="{{ __('Print delivery note') }}">
          <i class="fas fa-print"></i> {{ __('Print') }}
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Variables globales para los albaranes
  let currentOrderIds = [];
  let currentOrderMode = 'single'; // 'single' o 'multiple'
  let currentClientEmail = '';

  // Función para mostrar/ocultar loading overlay
  function showLoading() {
    if (!document.querySelector('.loading-overlay')) {
      const overlay = document.createElement('div');
      overlay.className = 'loading-overlay';
      overlay.innerHTML = '<div class="loading-spinner"></div>';
      document.body.appendChild(overlay);
    }
  }

  function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
      overlay.style.opacity = '0';
      setTimeout(() => overlay.remove(), 300);
    }
  }

  // Interceptar peticiones fetch para mostrar loading
  const originalFetch = window.fetch;
  window.fetch = function(...args) {
    showLoading();
    return originalFetch.apply(this, args)
      .finally(() => {
        setTimeout(hideLoading, 300);
      });
  };

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

  // Click en número de pedido para ver albarán individual
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('order-id-link')) {
      const orderId = e.target.dataset.orderId;
      showOrderDetails(orderId);
    }
  });

  // Click en "Ver Todos los Albaranes"
  document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-view-all-invoices')) {
      const btn = e.target.closest('.btn-view-all-invoices');
      const orderIds = JSON.parse(btn.dataset.orderIds);
      const orderNames = JSON.parse(btn.dataset.orderNames);
      const clientName = btn.dataset.clientName;
      showAllOrderDetails(orderIds, orderNames, clientName);
    }
  });

  // Función para mostrar detalles del pedido
  function showOrderDetails(orderId) {
    // Actualizar variables globales
    currentOrderIds = [orderId];
    currentOrderMode = 'single';

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

  // Función para mostrar todos los albaranes de un cliente
  function showAllOrderDetails(orderIds, orderNames, clientName) {
    // Actualizar variables globales
    currentOrderIds = orderIds;
    currentOrderMode = 'multiple';

    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');

    // Mostrar loading
    content.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">{{ __('Loading...') }}</span>
        </div>
        <p class="mt-3">{{ __('Loading') }} ${orderIds.length} {{ __('delivery notes') }}...</p>
      </div>
    `;

    modal.show();

    // Cargar todos los pedidos en paralelo
    const promises = orderIds.map(orderId =>
      fetch(`/deliveries/order-details/${orderId}`)
        .then(response => response.json())
    );

    Promise.all(promises)
      .then(results => {
        let html = `
          <div class="all-delivery-notes">
            <div class="alert alert-info mb-4">
              <h5 class="mb-2"><i class="fas fa-building"></i> <strong>${clientName}</strong></h5>
              <p class="mb-0">{{ __('Total Orders') }}: <strong>${orderIds.length}</strong></p>
            </div>
        `;

        results.forEach((data, index) => {
          if (data.success) {
            const order = data.order;
            html += `
              <div class="delivery-note mb-5" style="border: 2px solid #dee2e6; border-radius: 15px; padding: 20px; background: #f8f9fa;">
                <h4 class="mb-3" style="color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                  <i class="fas fa-file-invoice"></i> {{ __('Order') }} ${index + 1}: ${order.order_id}
                </h4>

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

            html += `</div>`; // Cierra delivery-note
          } else {
            html += `
              <div class="alert alert-danger mb-3">
                {{ __('Error loading order') }} ${orderNames[index] || orderIds[index]}
              </div>
            `;
          }
        });

        html += `</div>`; // Cierra all-delivery-notes
        content.innerHTML = html;
      })
      .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `<div class="alert alert-danger">{{ __('Error loading delivery notes') }}</div>`;
      });
  }

  // Abrir modal de firma y fotos (Pedido individual)
  document.addEventListener('click', function(e) {
    if (e.target.closest('.open-proof-modal')) {
      const btn = e.target.closest('.open-proof-modal');
      const orderId = btn.dataset.orderId;

      // Guardar el ID del pedido
      document.getElementById('proofOrderId').value = orderId;
      document.getElementById('deliveryMode').value = 'single';
      document.getElementById('ordersList').style.display = 'none';

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

  // Abrir modal de firma y fotos (Todos los pedidos del cliente)
  document.addEventListener('click', function(e) {
    if (e.target.closest('.open-proof-modal-all')) {
      console.log('Botón Entregar Todos clickeado'); // Debug
      const btn = e.target.closest('.open-proof-modal-all');
      const orderIds = JSON.parse(btn.dataset.orderIds);
      const orderNames = JSON.parse(btn.dataset.orderNames);
      const clientName = btn.dataset.clientName;

      // Guardar los IDs de los pedidos
      document.getElementById('proofOrderIds').value = JSON.stringify(orderIds);
      document.getElementById('deliveryMode').value = 'multiple';

      // Mostrar lista de pedidos
      const ordersList = document.getElementById('ordersList');
      const ordersListContent = document.getElementById('ordersListContent');
      ordersList.style.display = 'block';

      let ordersHtml = `<h6 class="mb-2"><strong>Cliente:</strong> ${clientName}</h6>`;
      ordersHtml += '<ul class="mb-0" style="padding-left: 20px;">';
      orderNames.forEach(orderName => {
        ordersHtml += `<li style="padding: 5px 0;"><strong>${orderName}</strong></li>`;
      });
      ordersHtml += '</ul>';
      ordersListContent.innerHTML = ordersHtml;

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

  // Confirmar entrega con firma y fotos
  document.getElementById('confirmDelivery').addEventListener('click', function() {
    const deliveryMode = document.getElementById('deliveryMode').value;
    const notes = document.getElementById('deliveryNotes').value;
    const photosInput = document.getElementById('deliveryPhotos');

    // Crear FormData
    const formData = new FormData();
    formData.append('notes', notes);

    // Añadir orden(es) según el modo
    if (deliveryMode === 'single') {
      const orderId = document.getElementById('proofOrderId').value;
      formData.append('order_id', orderId);
    } else {
      // Modo múltiple
      const orderIds = JSON.parse(document.getElementById('proofOrderIds').value);
      formData.append('order_ids', JSON.stringify(orderIds));
      formData.append('multiple', 'true');
    }

    // Añadir firma si existe
    if (signaturePad && !signaturePad.isEmpty()) {
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

  // ========== BOTONES DE ALBARANES ==========

  // Enviar por Email
  const btnEmail = document.getElementById('btnEmailDeliveryNote');
  if (btnEmail) {
    btnEmail.addEventListener('click', function() {
    // Cerrar el modal de albaranes primero
    const orderModal = bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal'));
    if (orderModal) {
      orderModal.hide();
    }

    // Esperar un poco para que se cierre el modal antes de abrir SweetAlert2
    setTimeout(() => {
      Swal.fire({
      title: '{{ __('Send by Email') }}',
      html: `
        <input type="email" id="emailInput" class="swal2-input" placeholder="{{ __('Enter email address') }}" value="${currentClientEmail}">
      `,
      showCancelButton: true,
      confirmButtonText: '{{ __('Send') }}',
      cancelButtonText: '{{ __('Cancel') }}',
      preConfirm: () => {
        const email = document.getElementById('emailInput').value;
        if (!email || !email.includes('@')) {
          Swal.showValidationMessage('{{ __('Enter email address') }}');
          return false;
        }
        return email;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const email = result.value;
        const formData = new FormData();
        formData.append('email', email);
        formData.append('order_ids', JSON.stringify(currentOrderIds));
        formData.append('mode', currentOrderMode);

        // Mostrar loading
        Swal.fire({
          title: '{{ __('Sending...') }}',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        fetch('{{ route('deliveries.send-email') }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: '{{ __('Email sent successfully') }}',
              text: currentOrderMode === 'single'
                ? '{{ __('Delivery note sent to') }} ' + email
                : '{{ __('All delivery notes sent to') }} ' + email,
              timer: 3000
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: '{{ __('Error sending email') }}',
              text: data.message
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: '{{ __('Error sending email') }}',
            text: error.message
          });
        });
      }
    });
    }, 300); // Esperar 300ms para que se cierre el modal
    });
  }

  // Descargar PDF
  const btnPDF = document.getElementById('btnDownloadPDF');
  if (btnPDF) {
    btnPDF.addEventListener('click', function() {
      const orderIdsParam = currentOrderIds.join(',');
      const url = `{{ route('deliveries.download-pdf') }}?order_ids=${orderIdsParam}&mode=${currentOrderMode}`;
      window.open(url, '_blank');
    });
  }

  // Imprimir
  const btnPrint = document.getElementById('btnPrintDeliveryNote');
  if (btnPrint) {
    btnPrint.addEventListener('click', function() {
      const orderIdsParam = currentOrderIds.join(',');
      const url = `{{ route('deliveries.print') }}?order_ids=${orderIdsParam}&mode=${currentOrderMode}`;
      const printWindow = window.open(url, '_blank');
      printWindow.addEventListener('load', function() {
        printWindow.print();
      });
    });
  }
});
</script>
@endpush

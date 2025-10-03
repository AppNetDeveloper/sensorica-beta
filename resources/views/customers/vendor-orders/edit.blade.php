@extends('layouts.admin')

@section('title', __('Editar pedido a proveedor') . ' - ' . $customer->name)
@section('page-title', __('Editar pedido a proveedor'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.index', $customer) }}">{{ __('Pedidos a proveedor') }}</a></li>
    <li class="breadcrumb-item active">{{ $vendorOrder->reference }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Actualizar datos del pedido') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.vendor-orders.update', [$customer, $vendorOrder]) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">{{ __('Proveedor') }}</label>
          <select name="vendor_supplier_id" class="form-select @error('vendor_supplier_id') is-invalid @enderror" required>
            @foreach($suppliers as $id => $name)
              <option value="{{ $id }}" {{ old('vendor_supplier_id', $vendorOrder->vendor_supplier_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
          @error('vendor_supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Referencia') }}</label>
          <input type="text" name="reference" value="{{ old('reference', $vendorOrder->reference) }}" class="form-control @error('reference') is-invalid @enderror" required>
          @error('reference')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Estado') }}</label>
          <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach($statuses as $status)
              <option value="{{ $status }}" {{ old('status', $vendorOrder->status) === $status ? 'selected' : '' }}>{{ __($status) }}</option>
            @endforeach
          </select>
          @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Moneda') }}</label>
          <input type="text" name="currency" value="{{ old('currency', $vendorOrder->currency) }}" class="form-control @error('currency') is-invalid @enderror" required>
          @error('currency')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Fecha esperada') }}</label>
          <input type="date" name="expected_at" value="{{ old('expected_at', optional($vendorOrder->expected_at)->format('Y-m-d')) }}" class="form-control @error('expected_at') is-invalid @enderror">
          @error('expected_at')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label class="form-label">{{ __('Notas') }}</label>
          <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $vendorOrder->notes) }}</textarea>
          @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <hr class="my-4">

      <h5>{{ __('Líneas del pedido') }}</h5>
      <div id="order-lines">
        @php($oldLines = old('lines', $vendorOrder->lines->map(function($line){
            return [
              'vendor_item_id' => $line->vendor_item_id,
              'description' => $line->description,
              'quantity_ordered' => $line->quantity_ordered,
              'unit_price' => $line->unit_price,
              'tax_rate' => $line->tax_rate,
            ];
          })->toArray()))
        @foreach($oldLines as $index => $line)
          <div class="card mb-3 order-line">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">{{ __('Producto catalogado') }}</label>
                  <select name="lines[{{ $index }}][vendor_item_id]" class="form-select">
                    <option value="">{{ __('Sin asignar') }}</option>
                    @foreach($items as $item)
                      <option value="{{ $item->id }}" {{ ($line['vendor_item_id'] ?? null) == $item->id ? 'selected' : '' }}>
                        {{ $item->name }} ({{ $item->sku ?? '—' }})
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">{{ __('Descripción') }}</label>
                  <input type="text" name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" class="form-control @error("lines.$index.description") is-invalid @enderror" required>
                  @error("lines.$index.description")
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-2">
                  <label class="form-label">{{ __('Cantidad') }}</label>
                  <input type="number" step="0.0001" name="lines[{{ $index }}][quantity_ordered]" value="{{ $line['quantity_ordered'] ?? 1 }}" class="form-control @error("lines.$index.quantity_ordered") is-invalid @enderror" required>
                  @error("lines.$index.quantity_ordered")
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-2">
                  <label class="form-label">{{ __('Precio unitario') }}</label>
                  <input type="number" step="0.0001" name="lines[{{ $index }}][unit_price]" value="{{ $line['unit_price'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                  <label class="form-label">{{ __('IVA (%)') }}</label>
                  <input type="number" step="0.01" name="lines[{{ $index }}][tax_rate]" value="{{ $line['tax_rate'] ?? '' }}" class="form-control">
                </div>
              </div>
              <div class="mt-3 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-line" {{ $loop->count === 1 ? 'disabled' : '' }}>
                  <i class="ti ti-trash"></i> {{ __('Eliminar línea') }}
                </button>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <button type="button" class="btn btn-outline-primary" id="add-line">
        <i class="ti ti-plus"></i> {{ __('Añadir línea') }}
      </button>

      <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('customers.vendor-orders.show', [$customer, $vendorOrder]) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Actualizar pedido') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const addLineButton = document.getElementById('add-line');
    const linesContainer = document.getElementById('order-lines');

    addLineButton.addEventListener('click', () => {
      const index = linesContainer.querySelectorAll('.order-line').length;
      const template = document.createElement('div');
      template.classList.add('card', 'mb-3', 'order-line');
      template.innerHTML = `
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">{{ __('Producto catalogado') }}</label>
              <select name="lines[${index}][vendor_item_id]" class="form-select">
                <option value="">{{ __('Sin asignar') }}</option>
                @foreach($items as $item)
                  <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->sku ?? '—' }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">{{ __('Descripción') }}</label>
              <input type="text" name="lines[${index}][description]" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">{{ __('Cantidad') }}</label>
              <input type="number" step="0.0001" name="lines[${index}][quantity_ordered]" value="1" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">{{ __('Precio unitario') }}</label>
              <input type="number" step="0.0001" name="lines[${index}][unit_price]" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">{{ __('IVA (%)') }}</label>
              <input type="number" step="0.01" name="lines[${index}][tax_rate]" class="form-control">
            </div>
          </div>
          <div class="mt-3 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-line">
              <i class="ti ti-trash"></i> {{ __('Eliminar línea') }}
            </button>
          </div>
        </div>
      `;
      linesContainer.appendChild(template);
      refreshRemoveButtons();
    });

    linesContainer.addEventListener('click', (event) => {
      if (event.target.closest('.remove-line')) {
        const lines = linesContainer.querySelectorAll('.order-line');
        if (lines.length > 1) {
          event.target.closest('.order-line').remove();
        }
        refreshRemoveButtons();
      }
    });

    function refreshRemoveButtons() {
      const lines = linesContainer.querySelectorAll('.order-line');
      lines.forEach(line => {
        const btn = line.querySelector('.remove-line');
        btn.disabled = lines.length === 1;
      });
    }

    refreshRemoveButtons();
  });
</script>
@endpush

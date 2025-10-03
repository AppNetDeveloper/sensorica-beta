<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{{ __('Etiqueta de activo') }} {{ $asset->label_code }}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 24px; }
    .label { border: 1px solid #000; padding: 16px; width: 420px; }
    .label h2 { margin: 0 0 8px; font-size: 20px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid div { font-size: 12px; }
    .code-block { text-align: center; margin-top: 16px; }
    canvas { max-width: 100%; }
    .meta { margin-top: 12px; font-size: 11px; }
  </style>
</head>
<body>
  <div class="label">
    <h2>{{ Str::limit($customer->name, 35) }}</h2>
    <div class="grid">
      <div><strong>{{ __('Etiqueta') }}:</strong><br><code>{{ $asset->label_code }}</code></div>
      <div><strong>{{ __('Artículo') }}:</strong><br><code>{{ $asset->article_code }}</code></div>
      <div><strong>{{ __('Categoría') }}:</strong><br>{{ optional($asset->category)->name ?? '—' }}</div>
      <div><strong>{{ __('Centro coste') }}:</strong><br>{{ optional($asset->costCenter)->code ?? '—' }}</div>
    </div>
    <div class="code-block">
      <canvas id="barcode"></canvas>
    </div>
    <div class="code-block">
      <div id="qrcode"></div>
    </div>
    <div class="meta">
      <div><strong>{{ __('RFID EPC') }}:</strong> {{ $asset->rfid_epc ?? '—' }}</div>
      <div><strong>{{ __('RFID TID') }}:</strong> {{ $asset->rfid_tid ?? '—' }}</div>
      <div><strong>{{ __('Descripción') }}:</strong> {{ Str::limit($asset->description, 70) }}</div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <script>
    const labelCode = @json($asset->label_code);

    JsBarcode('#barcode', labelCode, {
      format: 'CODE128',
      lineColor: '#000',
      displayValue: true,
      fontSize: 14,
      height: 60,
      margin: 6
    });

    new QRCode(document.getElementById('qrcode'), {
      text: labelCode,
      width: 128,
      height: 128
    });

    window.onload = function(){
      setTimeout(() => window.print(), 500);
    };
  </script>
</body>
</html>

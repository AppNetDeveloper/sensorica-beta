<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Delivery Note') }} - {{ __('Print') }}</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 15mm;
            }
            .page-break {
                page-break-after: always;
            }
            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #667eea;
            font-size: 24px;
            margin: 0 0 5px 0;
        }

        .header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .order-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .order-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }

        .info-col {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .info-col h3 {
            font-size: 14px;
            color: #667eea;
            margin: 0 0 10px 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }

        .info-col p {
            margin: 5px 0;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #667eea;
            font-weight: bold;
        }

        table td {
            padding: 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #333;
        }

        .badge-secondary {
            background: #6c757d;
            color: white;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        hr {
            border: none;
            border-top: 2px solid #dee2e6;
            margin: 20px 0;
        }

        .section-title {
            color: #667eea;
            font-size: 16px;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #667eea;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #999;
            padding: 15px;
            border-top: 1px solid #ddd;
            margin-top: 30px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Botón de imprimir */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .print-button i {
            margin-right: 8px;
        }

        @media print {
            .print-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="fas fa-print"></i> {{ __('Print') }}
    </button>

    <div class="print-container">
        <div class="header">
            <h1>{{ $mode === 'single' ? __('Delivery Note') : __('All Delivery Notes') }}</h1>
            <p>{{ __('Date') }}: {{ $date->format('d/m/Y H:i') }}</p>
        </div>

        @foreach($ordersData as $index => $orderData)
            @php
                $order = $orderData['order'];
                $articles = $orderData['articles'];
            @endphp

            <div class="order-section">
                <div class="order-header">
                    <h2>{{ __('Order') }} {{ $mode === 'multiple' ? ($index + 1) . ': ' : '' }}{{ $order->order_id }}</h2>
                </div>

                <div class="info-section">
                    <div class="info-col">
                        <h3>{{ __('Order Information') }}</h3>
                        <p><strong>{{ __('Order ID') }}:</strong> {{ $order->order_id }}</p>
                        <p><strong>{{ __('Client') }}:</strong> {{ $order->customerClient->name ?? 'N/A' }}</p>
                        @if($order->client_number)
                            <p><strong>{{ __('Client Number') }}:</strong> {{ $order->client_number }}</p>
                        @endif
                    </div>
                    <div class="info-col">
                        <h3>{{ __('Delivery Information') }}</h3>
                        @if($order->delivery_date)
                            <p><strong>{{ __('Delivery Date') }}:</strong> {{ $order->delivery_date->format('d/m/Y') }}</p>
                        @endif
                        @if($order->estimated_delivery_date)
                            <p><strong>{{ __('Estimated Date') }}:</strong> {{ $order->estimated_delivery_date->format('d/m/Y') }}</p>
                        @endif
                        <p><strong>{{ __('In Stock') }}:</strong>
                            @if($order->in_stock)
                                <span class="badge badge-success">{{ __('Yes') }}</span>
                            @else
                                <span class="badge badge-warning">{{ __('No') }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <hr>

                <h3 class="section-title"><i class="fas fa-cogs"></i> {{ __('Processes') }}</h3>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Group') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Process') }}</th>
                            <th class="text-right">{{ __('Time') }}</th>
                            <th class="text-right">{{ __('Boxes') }}</th>
                            <th class="text-right">{{ __('Units/Box') }}</th>
                            <th class="text-right">{{ __('Pallets') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->processes as $process)
                            <tr>
                                <td><span class="badge badge-secondary">{{ $process->pivot->grupo_numero }}</span></td>
                                <td><code>{{ $process->code }}</code></td>
                                <td>{{ $process->name }}</td>
                                <td class="text-right">{{ $process->pivot->time ?? '-' }}</td>
                                <td class="text-right">{{ $process->pivot->box ?? 0 }}</td>
                                <td class="text-right">{{ $process->pivot->units_box ?? 0 }}</td>
                                <td class="text-right">{{ $process->pivot->number_of_pallets ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <hr>

                <h3 class="section-title"><i class="fas fa-box"></i> {{ __('Articles') }}</h3>
                @if($articles && count($articles) > 0)
                    @foreach($articles as $group)
                        <p style="margin: 15px 0 8px 0;">
                            <span class="badge badge-info">{{ __('Group') }} {{ $group['grupo_numero'] }}</span>
                        </p>
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-center">{{ __('In Stock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['items'] as $article)
                                    <tr>
                                        <td><code>{{ $article->codigo_articulo }}</code></td>
                                        <td>{{ $article->descripcion_articulo ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($article->in_stock)
                                                <i class="fas fa-check text-success" style="color: #28a745;"></i>
                                            @else
                                                <i class="fas fa-times text-danger" style="color: #dc3545;"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                @else
                    <p style="color: #999; font-style: italic;">{{ __('No articles found') }}</p>
                @endif
            </div>

            @if(!$loop->last && $mode === 'multiple')
                <div class="page-break"></div>
            @endif
        @endforeach

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>{{ __('Generated on') }}: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Auto-print después de cargar si viene de un botón de imprimir
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_print') === '1') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        });
    </script>
</body>
</html>

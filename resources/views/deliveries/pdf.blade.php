<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Delivery Note') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #667eea;
            font-size: 20px;
            margin: 0 0 5px 0;
        }
        .header p {
            margin: 0;
            color: #666;
        }
        .order-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .order-header h2 {
            margin: 0;
            font-size: 14px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .info-col h3 {
            font-size: 11px;
            color: #667eea;
            margin: 0 0 8px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background: #f4f4f4;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
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
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
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

            <h3 style="color: #667eea; font-size: 11px;">{{ __('Processes') }}</h3>
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
                            <td>{{ $process->code }}</td>
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

            <h3 style="color: #667eea; font-size: 11px;">{{ __('Articles') }}</h3>
            @if($articles && count($articles) > 0)
                @foreach($articles as $group)
                    <p style="margin: 10px 0 5px 0;">
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
                                    <td>{{ $article->codigo_articulo }}</td>
                                    <td>{{ $article->descripcion_articulo ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($article->in_stock)
                                            ✓
                                        @else
                                            ✗
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
        <p>{{ config('app.name') }} - {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>

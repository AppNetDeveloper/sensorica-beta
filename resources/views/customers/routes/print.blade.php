<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Ruta - {{ $assignment->fleetVehicle->plate }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20mm;
            background: #fff;
            color: #333;
        }
        
        .header {
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #0d6efd;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header-info {
            text-align: right;
            font-size: 14px;
        }
        
        .vehicle-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        
        .vehicle-info h2 {
            color: #0d6efd;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
        }
        
        .clients-section {
            margin-top: 25px;
        }
        
        .client-card {
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .client-header {
            background: #0d6efd;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            margin: -15px -15px 15px -15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .client-name {
            font-size: 18px;
            font-weight: 700;
        }
        
        .client-number {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .orders-table thead {
            background: #f8f9fa;
        }
        
        .orders-table th {
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            text-transform: uppercase;
        }
        
        .orders-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        
        .order-id {
            font-weight: 700;
            color: #0d6efd;
        }
        
        .date-badge {
            background: #e7f1ff;
            color: #0d6efd;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .checkbox-col {
            width: 40px;
            text-align: center;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #6c757d;
            border-radius: 4px;
            display: inline-block;
        }
        
        .summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            border-top: 3px solid #0d6efd;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
        }
        
        .summary-item {
            padding: 10px;
        }
        
        .summary-value {
            font-size: 32px;
            font-weight: 700;
            color: #0d6efd;
        }
        
        .summary-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .signature-box {
            border-top: 2px solid #212529;
            padding-top: 10px;
            text-align: center;
        }
        
        .signature-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        @media print {
            body {
                padding: 10mm;
            }
            
            .client-card {
                page-break-inside: avoid;
            }
            
            @page {
                margin: 10mm;
            }
        }
        
        .no-orders {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📋 HOJA DE RUTA</h1>
            <p style="color: #6c757d; font-size: 14px;">{{ $customer->name }}</p>
        </div>
        <div class="header-info">
            <div style="font-weight: 600; font-size: 16px;">{{ \Carbon\Carbon::parse($assignment->assignment_date)->format('d/m/Y') }}</div>
            <div style="color: #6c757d;">{{ \Carbon\Carbon::parse($assignment->assignment_date)->locale('es')->isoFormat('dddd') }}</div>
        </div>
    </div>

    <div class="vehicle-info">
        <h2>🚚 Información del Vehículo</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Matrícula</span>
                <span class="info-value">{{ $assignment->fleetVehicle->plate }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tipo</span>
                <span class="info-value">{{ $assignment->fleetVehicle->vehicle_type ?? 'Estándar' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Ruta</span>
                <span class="info-value">{{ $assignment->routeName->name ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <div class="clients-section">
        @if($clientAssignments->count() > 0)
            @foreach($clientAssignments as $index => $clientAssignment)
                @php
                    $client = $clientAssignment->customerClient;
                    $orders = $clientAssignment->orderAssignments;
                @endphp
                <div class="client-card">
                    <div class="client-header">
                        <span class="client-name">{{ $index + 1 }}. {{ $client->name }}</span>
                        <span class="client-number">{{ $orders->count() }} {{ $orders->count() === 1 ? 'pedido' : 'pedidos' }}</span>
                    </div>

                    @if($client->address || $client->phone)
                        <div style="margin-bottom: 15px; font-size: 13px; color: #6c757d;">
                            @if($client->address)
                                <div>📍 {{ $client->address }}</div>
                            @endif
                            @if($client->phone)
                                <div>📞 {{ $client->phone }}</div>
                            @endif
                        </div>
                    @endif

                    @if($orders->count() > 0)
                        @foreach($orders as $orderIndex => $orderAssignment)
                            @php
                                $order = $orderAssignment->originalOrder;
                                $order->load('processes');
                                $processesWithArticles = \App\Models\OriginalOrderProcess::where('original_order_id', $order->id)
                                    ->with('articles')
                                    ->orderBy('grupo_numero')
                                    ->get();
                            @endphp
                            <div style="margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; background: #f8f9fa;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #0d6efd;">
                                    <div>
                                        <strong style="font-size: 16px; color: #0d6efd;">{{ $orderIndex + 1 }}. Pedido: {{ $order->order_id }}</strong>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        @if($order->delivery_date)
                                            <span class="date-badge">{{ $order->delivery_date->format('d/m/Y') }}</span>
                                        @elseif($order->estimated_delivery_date)
                                            <span class="date-badge" style="background: #fff3cd; color: #856404;">~{{ $order->estimated_delivery_date->format('d/m/Y') }}</span>
                                        @endif
                                        <span class="checkbox"></span>
                                    </div>
                                </div>

                                @if($order->processes->count() > 0)
                                    <div style="margin-top: 10px;">
                                        <div style="font-size: 12px; font-weight: 600; color: #6c757d; margin-bottom: 8px; text-transform: uppercase;">Procesos:</div>
                                        <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                                            <thead style="background: #e9ecef;">
                                                <tr>
                                                    <th style="padding: 6px; text-align: left; border: 1px solid #dee2e6;">Grupo</th>
                                                    <th style="padding: 6px; text-align: left; border: 1px solid #dee2e6;">Código</th>
                                                    <th style="padding: 6px; text-align: left; border: 1px solid #dee2e6;">Proceso</th>
                                                    <th style="padding: 6px; text-align: center; border: 1px solid #dee2e6;">Cajas</th>
                                                    <th style="padding: 6px; text-align: center; border: 1px solid #dee2e6;">Uds/Caja</th>
                                                    <th style="padding: 6px; text-align: center; border: 1px solid #dee2e6;">Pallets</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($order->processes as $process)
                                                    <tr>
                                                        <td style="padding: 5px; border: 1px solid #dee2e6; background: #fff;"><span style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;">{{ $process->pivot->grupo_numero }}</span></td>
                                                        <td style="padding: 5px; border: 1px solid #dee2e6; background: #fff;"><code style="background: #e9ecef; padding: 2px 4px; border-radius: 3px;">{{ $process->code }}</code></td>
                                                        <td style="padding: 5px; border: 1px solid #dee2e6; background: #fff;">{{ $process->name }}</td>
                                                        <td style="padding: 5px; text-align: center; border: 1px solid #dee2e6; background: #fff;">{{ $process->pivot->box ?? 0 }}</td>
                                                        <td style="padding: 5px; text-align: center; border: 1px solid #dee2e6; background: #fff;">{{ $process->pivot->units_box ?? 0 }}</td>
                                                        <td style="padding: 5px; text-align: center; border: 1px solid #dee2e6; background: #fff;">{{ $process->pivot->number_of_pallets ?? 0 }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @php
                                    $articlesByGroup = $processesWithArticles->groupBy('grupo_numero');
                                @endphp

                                @if($articlesByGroup->count() > 0)
                                    <div style="margin-top: 12px;">
                                        <div style="font-size: 12px; font-weight: 600; color: #6c757d; margin-bottom: 8px; text-transform: uppercase;">Artículos:</div>
                                        @foreach($articlesByGroup as $grupoNum => $processes)
                                            @php
                                                $allArticles = collect();
                                                foreach ($processes as $proc) {
                                                    $allArticles = $allArticles->merge($proc->articles);
                                                }
                                                $uniqueArticles = $allArticles->unique('codigo_articulo');
                                            @endphp
                                            @if($uniqueArticles->count() > 0)
                                                <div style="margin-bottom: 8px;">
                                                    <div style="font-size: 10px; font-weight: 600; color: #0d6efd; margin-bottom: 4px;">Grupo {{ $grupoNum }}:</div>
                                                    <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                                                        <thead style="background: #e9ecef;">
                                                            <tr>
                                                                <th style="padding: 4px; text-align: left; border: 1px solid #dee2e6;">Código</th>
                                                                <th style="padding: 4px; text-align: left; border: 1px solid #dee2e6;">Descripción</th>
                                                                <th style="padding: 4px; text-align: center; border: 1px solid #dee2e6; width: 60px;">Stock</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($uniqueArticles as $article)
                                                                <tr>
                                                                    <td style="padding: 4px; border: 1px solid #dee2e6; background: #fff;"><code style="background: #e9ecef; padding: 2px 4px; border-radius: 3px; font-size: 9px;">{{ $article->codigo_articulo }}</code></td>
                                                                    <td style="padding: 4px; border: 1px solid #dee2e6; background: #fff;">{{ $article->descripcion_articulo ?? '-' }}</td>
                                                                    <td style="padding: 4px; text-align: center; border: 1px solid #dee2e6; background: #fff;">
                                                                        @if($article->in_stock)
                                                                            <span style="color: #28a745; font-weight: 600;">✓</span>
                                                                        @else
                                                                            <span style="color: #dc3545; font-weight: 600;">✗</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="no-orders">No hay pedidos activos para este cliente</div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="no-orders" style="padding: 60px;">
                <div style="font-size: 48px; margin-bottom: 20px;">📦</div>
                <div style="font-size: 18px;">No hay clientes asignados a este vehículo</div>
            </div>
        @endif
    </div>

    @php
        $totalClients = $clientAssignments->count();
        $totalOrders = $clientAssignments->sum(function($ca) { return $ca->orderAssignments->count(); });
    @endphp

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ $totalClients }}</div>
                <div class="summary-label">Clientes</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ $totalOrders }}</div>
                <div class="summary-label">Pedidos Activos</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">___</div>
                <div class="summary-label">Entregados</div>
            </div>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-label">Conductor</div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Supervisor</div>
        </div>
    </div>

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
        <p style="margin-top: 5px;">{{ $customer->name }} - Sistema de Gestión de Rutas</p>
    </div>

    <script>
        // Auto-imprimir al cargar
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

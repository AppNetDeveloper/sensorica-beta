<?php

namespace App\Exports;

use App\Models\RouteName;
use App\Models\RouteClientVehicleAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EntireRouteExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $routeNameId;
    protected $dayDate;
    protected $customerId;

    public function __construct($routeNameId, $dayDate, $customerId)
    {
        $this->routeNameId = $routeNameId;
        $this->dayDate = $dayDate;
        $this->customerId = $customerId;
    }

    public function collection()
    {
        $routeName = RouteName::findOrFail($this->routeNameId);
        $dayDate = \Carbon\Carbon::parse($this->dayDate);

        $clientAssignments = RouteClientVehicleAssignment::where('customer_id', $this->customerId)
            ->where('route_name_id', $this->routeNameId)
            ->whereDate('assignment_date', $dayDate)
            ->with([
                'customerClient',
                'fleetVehicle',
                'orderAssignments' => function ($query) {
                    $query->where('active', true)->orderBy('sort_order', 'asc');
                },
                'orderAssignments.originalOrder'
            ])
            ->orderBy('fleet_vehicle_id')
            ->orderBy('sort_order', 'asc')
            ->get();

        $data = collect();
        
        // Información de la ruta
        $data->push([
            'RUTA COMPLETA',
            '',
            '',
            '',
            '',
        ]);
        $data->push([
            'Ruta:',
            $routeName->name,
            'Fecha:',
            $dayDate->format('d/m/Y'),
            $dayDate->locale('es')->isoFormat('dddd'),
        ]);
        $data->push(['', '', '', '', '']); // Línea vacía

        $currentVehicleId = null;
        $clientNumber = 1;

        foreach ($clientAssignments as $clientAssignment) {
            // Cabecera de vehículo si cambia
            if ($currentVehicleId !== $clientAssignment->fleet_vehicle_id) {
                if ($currentVehicleId !== null) {
                    $data->push(['', '', '', '', '']); // Separador entre vehículos
                }
                $data->push([
                    '🚚 VEHÍCULO',
                    $clientAssignment->fleetVehicle->plate,
                    $clientAssignment->fleetVehicle->vehicle_type ?? '',
                    '',
                    '',
                ]);
                $currentVehicleId = $clientAssignment->fleet_vehicle_id;
            }

            $client = $clientAssignment->customerClient;
            $orders = $clientAssignment->orderAssignments;

            // Cliente
            $data->push([
                "  Cliente #{$clientNumber}",
                $client->name,
                $client->phone ?? '',
                $client->address ?? '',
                "{$orders->count()} pedidos",
            ]);

            // Pedidos
            if ($orders->count() > 0) {
                foreach ($orders as $index => $orderAssignment) {
                    $order = $orderAssignment->originalOrder;
                    $deliveryDate = '';
                    
                    if ($order->delivery_date) {
                        $deliveryDate = $order->delivery_date->format('d/m/Y');
                    } elseif ($order->estimated_delivery_date) {
                        $deliveryDate = '~' . $order->estimated_delivery_date->format('d/m/Y');
                    }

                    $data->push([
                        '',
                        "    Pedido " . ($index + 1),
                        $order->order_id,
                        $deliveryDate,
                        '☐ Entregado',
                    ]);
                }
            }

            $clientNumber++;
        }

        // Resumen
        $totalVehicles = $clientAssignments->pluck('fleet_vehicle_id')->unique()->count();
        $totalClients = $clientAssignments->count();
        $totalOrders = $clientAssignments->sum(function($ca) { 
            return $ca->orderAssignments->count(); 
        });

        $data->push(['', '', '', '', '']);
        $data->push(['', '', '', '', '']);
        $data->push([
            'RESUMEN TOTAL',
            "Vehículos: {$totalVehicles}",
            "Clientes: {$totalClients}",
            "Pedidos: {$totalOrders}",
            '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['Col1', 'Col2', 'Col3', 'Col4', 'Col5'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(20);

        // Título
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0d6efd'],
            ],
        ]);

        // Info ruta
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'e7f1ff'],
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Ruta Completa';
    }
}

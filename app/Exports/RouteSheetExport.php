<?php

namespace App\Exports;

use App\Models\RouteDayAssignment;
use App\Models\RouteClientVehicleAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RouteSheetExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $assignmentId;
    protected $customerId;

    public function __construct($assignmentId, $customerId)
    {
        $this->assignmentId = $assignmentId;
        $this->customerId = $customerId;
    }

    public function collection()
    {
        $assignment = RouteDayAssignment::with(['fleetVehicle', 'routeName'])
            ->where('id', $this->assignmentId)
            ->where('customer_id', $this->customerId)
            ->firstOrFail();

        $clientAssignments = RouteClientVehicleAssignment::where('fleet_vehicle_id', $assignment->fleet_vehicle_id)
            ->whereDate('assignment_date', $assignment->assignment_date)
            ->where('customer_id', $this->customerId)
            ->with([
                'customerClient',
                'orderAssignments' => function ($query) {
                    $query->where('active', true)->orderBy('sort_order', 'asc');
                },
                'orderAssignments.originalOrder'
            ])
            ->orderBy('sort_order', 'asc')
            ->get();

        $data = collect();
        
        // Información del vehículo
        $data->push([
            'HOJA DE RUTA',
            '',
            '',
            '',
            '',
        ]);
        $data->push([
            'Vehículo:',
            $assignment->fleetVehicle->plate,
            'Ruta:',
            $assignment->routeName->name ?? 'N/A',
            'Fecha: ' . $assignment->assignment_date->format('d/m/Y'),
        ]);
        $data->push(['', '', '', '', '']); // Línea vacía

        $clientNumber = 1;
        foreach ($clientAssignments as $clientAssignment) {
            $client = $clientAssignment->customerClient;
            $orders = $clientAssignment->orderAssignments;

            // Cabecera del cliente
            $data->push([
                "Cliente #{$clientNumber}",
                $client->name,
                $client->phone ?? '',
                $client->address ?? '',
                "{$orders->count()} pedidos",
            ]);

            // Pedidos del cliente
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
                        "  Pedido " . ($index + 1),
                        $order->order_id,
                        $deliveryDate,
                        '☐ Entregado',
                    ]);
                }
            } else {
                $data->push(['', '  Sin pedidos activos', '', '', '']);
            }

            $data->push(['', '', '', '', '']); // Línea vacía entre clientes
            $clientNumber++;
        }

        // Resumen
        $totalClients = $clientAssignments->count();
        $totalOrders = $clientAssignments->sum(function($ca) { 
            return $ca->orderAssignments->count(); 
        });

        $data->push(['', '', '', '', '']);
        $data->push([
            'RESUMEN',
            "Total Clientes: {$totalClients}",
            "Total Pedidos: {$totalOrders}",
            '',
            '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Col1',
            'Col2',
            'Col3',
            'Col4',
            'Col5',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(20);

        // Estilo para el título
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

        // Estilo para info del vehículo
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
        return 'Hoja de Ruta';
    }
}

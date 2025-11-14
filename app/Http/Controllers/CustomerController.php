<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // Agrega esta línea para usar Carbon
use Illuminate\Support\Facades\DB;
use App\Models\Sensor;
use App\Models\ProductionLineHourlyTotal;
use App\Models\ProductionLineWaitTimeHistory;
use Yajra\DataTables\Facades\DataTables;
use App\Models\BarcodeScanAfter;


class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function getCustomers(Request $request)
    {
        // Construye la consulta base para los clientes
        $query = Customer::query();

        // Usa DataTables para procesar la consulta y añadir las columnas adicionales
        return DataTables::of($query)
            ->addColumn('checkbox', function($customer) {
                // Esta columna se renderiza en el cliente con JavaScript
                return '';
            })
            ->addColumn('action', function ($customer) {
                // Solo mostrar el botón de expandir
                return "<button class='btn btn-sm btn-outline-primary toggle-actions' data-customer-id='{$customer->id}' title='" . __('Show Actions') . "'>
                    <i class='fas fa-chevron-down'></i> " . __('Actions') . "
                </button>";
            })
            ->addColumn('action_buttons', function ($customer) {
                // URLs para las diferentes acciones
                $editUrl = route('customers.edit', $customer->id);
                $productionLinesUrl = route('productionlines.index', ['customer_id' => $customer->id]);
                $deleteUrl = route('customers.destroy', $customer->id);
                $csrfToken = csrf_token();
                $liveViewUrl = secure_url('/modbuses/liststats/weight?token=' . $customer->token);
                $liveViewUrlProd = secure_url('/productionlines/liststats?token=' . $customer->token);
                $customerSensorsUrl = route('customers.sensors.index', $customer->id);

// FRAGMENTO PARA REEMPLAZAR EN CustomerController.php líneas 51-207
// Construir botones organizados por categorías temáticas
$allButtons = '';

// 🏭 FÁBRICA: Producción y planificación
$factoryActions = [];
if (auth()->user()->can('productionline-kanban')) {
    $orderOrganizerUrl = route('customers.order-organizer', $customer->id);
    $factoryActions[] = "<a href='{$orderOrganizerUrl}' class='btn btn-sm btn-primary me-1 mb-1'><i class='fas fa-tasks'></i> " . __('Kanban') . "</a>";
}
if (auth()->user()->can('productionline-show')) {
    $factoryActions[] = "<a href='{$productionLinesUrl}' class='btn btn-sm btn-secondary me-1 mb-1'><i class='fas fa-sitemap'></i> " . __('Líneas') . "</a>";
}
if (auth()->user()->can('productionline-orders')) {
    $originalOrdersUrl = route('customers.original-orders.index', $customer->id);
    $factoryActions[] = "<a href='{$originalOrdersUrl}' class='btn btn-sm btn-dark me-1 mb-1'><i class='fas fa-clipboard-list'></i> " . __('Pedidos') . "</a>";
}
if (auth()->user()->can('original-order-list')) {
    $finishedProcessesUrl = route('customers.original-orders.finished-processes.view', $customer->id);
    $factoryActions[] = "<a href='{$finishedProcessesUrl}' class='btn btn-sm btn-outline-dark me-1 mb-1'><i class='fas fa-chart-line'></i> " . __('Procesos finalizados') . "</a>";
}
if (auth()->user()->can('workcalendar-list')) {
    $workCalendarUrl = route('customers.work-calendars.index', $customer->id);
    $factoryActions[] = "<a href='{$workCalendarUrl}' class='btn btn-sm btn-info me-1 mb-1'><i class='fas fa-calendar-alt'></i> " . __('Calendario') . "</a>";
}
if (!empty($factoryActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-industry me-1'></i>" . __('Fábrica') . "</div>" . implode('', $factoryActions) . "</div>";
}

// 📦 ALMACÉN: Activos, inventario y compras
$warehouseActions = [];
if (auth()->user()->can('assets-view')) {
    $assetsUrl = route('customers.assets.index', $customer->id);
    $inventoryUrl = route('customers.assets.inventory', $customer->id);
    $warehouseActions[] = "<a href='{$assetsUrl}' class='btn btn-sm btn-primary me-1 mb-1'><i class='fas fa-box'></i> " . __('Inventario') . "</a>";
    $warehouseActions[] = "<a href='{$inventoryUrl}' class='btn btn-sm btn-outline-primary me-1 mb-1'><i class='fas fa-chart-column'></i> " . __('Activos disponibles') . "</a>";
}
if (auth()->user()->can('asset-categories-view')) {
    $assetCategoriesUrl = route('customers.asset-categories.index', $customer->id);
    $warehouseActions[] = "<a href='{$assetCategoriesUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-layer-group'></i> " . __('Categorías') . "</a>";
}
if (auth()->user()->can('asset-cost-centers-view')) {
    $assetCostCentersUrl = route('customers.asset-cost-centers.index', $customer->id);
    $warehouseActions[] = "<a href='{$assetCostCentersUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-coins'></i> " . __('Centros coste') . "</a>";
}
if (auth()->user()->can('asset-locations-view')) {
    $assetLocationsUrl = route('customers.asset-locations.index', $customer->id);
    $warehouseActions[] = "<a href='{$assetLocationsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-warehouse'></i> " . __('Ubicaciones') . "</a>";
}
if (auth()->user()->can('vendor-suppliers-view')) {
    $supplierUrl = route('customers.vendor-suppliers.index', $customer->id);
    $warehouseActions[] = "<a href='{$supplierUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-industry'></i> " . __('Proveedores') . "</a>";
}
if (auth()->user()->can('vendor-items-view')) {
    $itemsUrl = route('customers.vendor-items.index', $customer->id);
    $warehouseActions[] = "<a href='{$itemsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-boxes-stacked'></i> " . __('Productos') . "</a>";
}
if (auth()->user()->can('vendor-orders-view')) {
    $ordersUrl = route('customers.vendor-orders.index', $customer->id);
    $warehouseActions[] = "<a href='{$ordersUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-file-invoice-dollar'></i> " . __('Pedidos proveedor') . "</a>";
}
if (!empty($warehouseActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-warehouse me-1'></i>" . __('Almacén') . "</div>" . implode('', $warehouseActions) . "</div>";
}

// 🔧 MANTENIMIENTO
if (auth()->user()->can('maintenance-show')) {
    $maintenancesUrl = route('customers.maintenances.index', $customer->id);
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-wrench me-1'></i>" . __('Mantenimiento') . "</div><a href='{$maintenancesUrl}' class='btn btn-sm btn-primary me-1 mb-1'><i class='fas fa-wrench'></i> " . __('Mantenimiento') . "</a></div>";
}

// 🚚 LOGÍSTICA: Flota, clientes y rutas
$logisticsActions = [];
if (auth()->user()->can('routes-view')) {
    $routesUrl = route('customers.routes.index', $customer->id);
    $logisticsActions[] = "<a href='{$routesUrl}' class='btn btn-sm btn-primary me-1 mb-1'><i class='fas fa-route'></i> " . __('Rutas') . "</a>";
}
if (auth()->user()->can('fleet-view')) {
    $fleetUrl = route('customers.fleet-vehicles.index', $customer->id);
    $logisticsActions[] = "<a href='{$fleetUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-truck'></i> " . __('Flota') . "</a>";
}
if (auth()->user()->can('customer-clients-view')) {
    $clientsUrl = route('customers.clients.index', $customer->id);
    $logisticsActions[] = "<a href='{$clientsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-user-friends'></i> " . __('Clientes') . "</a>";
}
if (auth()->user()->can('route-names-view')) {
    $routeNamesUrl = route('customers.route-names.index', $customer->id);
    $logisticsActions[] = "<a href='{$routeNamesUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-list'></i> " . __('Diccionario rutas') . "</a>";
}
if (!empty($logisticsActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-truck-moving me-1'></i>" . __('Logística') . "</div>" . implode('', $logisticsActions) . "</div>";
}

// 📊 ESTADÍSTICAS: Monitorización
$statsActions = [];
if (auth()->user()->can('original-order-list')) {
    $productionTimesUrl = route('customers.production-times.view', $customer->id);
    $statsActions[] = "<a href='{$productionTimesUrl}' class='btn btn-sm btn-outline-dark me-1 mb-1'><i class='fas fa-stopwatch'></i> " . __('Tiempos fabricación') . "</a>";
}
if (auth()->user()->can('hourly-totals-view')) {
    $hourlyTotalsUrl = route('customers.hourly-totals', $customer->id);
    $statsActions[] = "<a href='{$hourlyTotalsUrl}' class='btn btn-sm btn-outline-primary me-1 mb-1'><i class='fas fa-chart-area'></i> " . __('Carga por hora') . "</a>";
}
if (auth()->user()->can('productionline-weight-stats')) {
    $statsActions[] = "<a href='{$liveViewUrl}' target='_blank' class='btn btn-sm btn-success me-1 mb-1'><i class='fas fa-weight-hanging'></i> " . __('Weight Stats') . "</a>";
}
if (auth()->user()->can('productionline-production-stats')) {
    $statsActions[] = "<a href='{$liveViewUrlProd}' target='_blank' class='btn btn-sm btn-warning me-1 mb-1'><i class='fas fa-chart-line'></i> " . __('Production Stats') . "</a>";
    $statsActions[] = "<a href='{$customerSensorsUrl}' class='btn btn-sm btn-outline-success me-1 mb-1'><i class='fas fa-microchip'></i> " . __('Sensors') . "</a>";
    if (auth()->user()->can('productionline-kanban')) {
        $optimalTimesUrl = route('customers.optimal-sensor-times.index', $customer->id);
        $statsActions[] = "<a href='{$optimalTimesUrl}' class='btn btn-sm btn-outline-info me-1 mb-1'><i class='fas fa-clock'></i> " . __('Optimal Times') . "</a>";
    }
}
if (!empty($statsActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-chart-bar me-1'></i>" . __('Estadísticas') . "</div>" . implode('', $statsActions) . "</div>";
}

// ⚠️ INCIDENCIAS: Calidad
$qualityActions = [];
if (auth()->user()->can('productionline-incidents')) {
    $incidentsUrl = route('customers.production-order-incidents.index', $customer->id);
    $qualityActions[] = "<a href='{$incidentsUrl}' class='btn btn-sm btn-danger me-1 mb-1'><i class='fas fa-exclamation-triangle'></i> " . __('Incidencias') . "</a>";
    $qcIncidentsUrl = route('customers.quality-incidents.index', $customer->id);
    $qualityActions[] = "<a href='{$qcIncidentsUrl}' class='btn btn-sm btn-outline-danger me-1 mb-1'><i class='fas fa-vial'></i> " . __('Incidencias Calidad') . "</a>";
    $qcConfirmationsUrl = route('customers.qc-confirmations.index', $customer->id);
    $qualityActions[] = "<a href='{$qcConfirmationsUrl}' class='btn btn-sm btn-outline-primary me-1 mb-1'><i class='fas fa-clipboard-check'></i> " . __('Control calidad') . "</a>";
}
if (!empty($qualityActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-exclamation-circle me-1'></i>" . __('Incidencias y control de calidad') . "</div>" . implode('', $qualityActions) . "</div>";
}

// 🔌 INTEGRACIONES
if (auth()->user()->can('callbacks.view')) {
    $callbacksUrl = route('customers.callbacks.index', $customer->id);
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-plug me-1'></i>" . __('Integraciones') . "</div><a href='{$callbacksUrl}' class='btn btn-sm btn-outline-dark me-1 mb-1'><i class='fas fa-plug'></i> " . __('Callbacks') . "</a></div>";
}

// ⚙️ AJUSTES: Configuración
$settingsActions = [];
if (auth()->user()->can('productionline-edit')) {
    $settingsActions[] = "<a href='{$editUrl}' class='btn btn-sm btn-info me-1 mb-1'><i class='fas fa-edit'></i> " . __('Editar') . "</a>";
}
if (!empty($settingsActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-sliders-h me-1'></i>" . __('Ajustes') . "</div>" . implode('', $settingsActions) . "</div>";
}

// ☠️ CRÍTICO
if (auth()->user()->can('productionline-delete')) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label text-danger'><i class='fas fa-skull-crossbones me-1'></i>" . __('Crítico') . "</div><form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit='return confirm(\"" . __('Are you sure?') . "\");'><input type='hidden' name='_token' value='{$csrfToken}'><input type='hidden' name='_method' value='DELETE'><button type='submit' class='btn btn-sm btn-outline-danger me-1 mb-1'><i class='fas fa-trash'></i> " . __('Delete') . "</button></form></div>";
}

return "<div class='action-buttons-row d-flex flex-wrap' style='display: none; gap: 12px; padding: 12px; background-color: #f8f9fa; border-radius: 8px; margin-top: 8px;'>" . $allButtons . "</div>";
            })
            // Indica a DataTables que las columnas contienen HTML y no deben ser escapadas
            ->rawColumns(['action', 'action_buttons'])
            // Genera la respuesta JSON para DataTables
            ->make(true);
    }

     

    public function testCustomers()
    {
        $customers = Customer::all();
        dd($customers); 
    }
    
    /**
     * Eliminar múltiples clientes seleccionados
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->ids;
            
            // Verificar si hay IDs válidos
            if (!$ids || !is_array($ids) || count($ids) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('No customers selected for deletion')
                ], 400);
            }
            
            // Eliminar los clientes seleccionados
            $deleted = Customer::whereIn('id', $ids)->delete();
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => __(':count customers have been deleted successfully', ['count' => count($ids)])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete customers')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Muestra el organizador de órdenes para un cliente
     *
     * @param Customer $customer
     * @return \Illuminate\View\View
     */
    public function showOrderOrganizer(Customer $customer)
    {
        // Cargar las líneas de producción del cliente con sus procesos
        $productionLines = $customer->productionLines()
            ->with('processes')
            ->get()
            ->filter(function($item) {
                return $item->processes->isNotEmpty(); // Filtra solo líneas con procesos
            });
            
        // Obtener procesos únicos con sus líneas
        $uniqueProcesses = collect();
        
        foreach ($productionLines as $line) {
            $process = $line->processes->first();
            if ($process) {
                $description = $process->description ?: 'Sin descripción';
                if (!$uniqueProcesses->has($description)) {
                    $uniqueProcesses->put($description, [
                        'process' => $process,
                        'lines' => collect()
                    ]);
                }
                $uniqueProcesses[$description]['lines']->push($line);
            }
        }
        
        // Ordenar por la posición en el kanban, luego por descripción si no tienen posición
        $sortedProcesses = $uniqueProcesses->sortBy(function($item) {
            $process = $item['process'];
            // Si tiene posición kanban, usarla; si no, poner al final con valor alto
            $position = $process->posicion_kanban ?? 9999;
            return $position;
        });
            
        // Obtener el estado actual del filtro (prioriza .env, default false si no existe)
        $filterEnabled = \App\Models\Setting::getGlobal('PRODUCTION_FILTER_NOT_READY_KANBAN', false);
        $filterEnabled = $filterEnabled === 'true' || $filterEnabled === true || $filterEnabled === '1';

        return view('customers.order-organizer', [
            'customer' => $customer,
            'groupedProcesses' => $sortedProcesses,
            'totalLines' => $productionLines->count(),
            'filterEnabled' => $filterEnabled
        ]);
    }

    /**
     * Toggle Kanban filter setting
     *
     * @param Customer $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleKanbanFilter(Customer $customer)
    {
        try {
            $newValue = \App\Models\Setting::toggleGlobal('PRODUCTION_FILTER_NOT_READY_KANBAN', false);

            // Limpiar caches para asegurar que se recargue el nuevo valor
            \Artisan::call('config:clear');
            \Artisan::call('cache:clear');
            \Cache::forget('production.filter_not_ready_machine_kanban');

            return response()->json([
                'success' => true,
                'value' => $newValue,
                'message' => $newValue
                    ? __('Filtro activado. Las órdenes no listas se ocultarán en Kanban.')
                    : __('Filtro desactivado. Todas las órdenes serán visibles en Kanban.')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling Kanban filter: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error al cambiar la configuración del filtro.')
            ], 500);
        }
    }

    public function hourlyTotals(Customer $customer)
    {
        $productionLines = $customer->productionLines()
            ->with(['processes' => function ($query) {
                $query->orderBy('production_line_process.order');
            }])
            ->get()
            ->filter(function ($line) {
                return $line->processes->isNotEmpty();
            });

        if ($productionLines->isEmpty()) {
            return redirect()->route('customers.index')->with('error', __('El cliente no tiene líneas de producción configuradas.'));
        }

        $lineIds = $productionLines->pluck('id');

        $totals = ProductionLineHourlyTotal::query()
            ->whereIn('production_line_id', $lineIds)
            ->orderBy('captured_at')
            ->get()
            ->groupBy('production_line_id');

        $series = [];
        $lastCapture = null;

        foreach ($productionLines as $line) {
            $lineTotals = $totals->get($line->id, collect());
            $process = $line->processes->first();
            $series[] = [
                'name' => sprintf('%s - %s', $line->name, $process?->description ?? __('Sin proceso')),
                'data' => $lineTotals->map(function (ProductionLineHourlyTotal $total) use (&$lastCapture) {
                    $lastCapture = $total->captured_at;
                    return [
                        'x' => $total->captured_at->format('Y-m-d H:i:s'),
                        'y' => round($total->total_time / 60, 2),
                    ];
                })->values(),
            ];
        }

        return view('customers.hourly-totals', [
            'customer' => $customer,
            'series' => $series,
            'lastCapture' => $lastCapture?->format('Y-m-d H:i:s'),
        ]);
    }

    public function hourlyTotalsData(Request $request, Customer $customer)
    {
        $lineIds = collect($request->input('line_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $query = ProductionLine::query()
            ->where('customer_id', $customer->id)
            ->with(['processes' => function ($query) {
                $query->orderBy('production_line_process.order');
            }]);

        if ($lineIds->isNotEmpty()) {
            $query->whereIn('id', $lineIds);
        }

        $productionLines = $query->get()->filter(function ($line) {
            return $line->processes->isNotEmpty();
        });

        if ($productionLines->isEmpty()) {
            return response()->json([
                'series' => [],
                'lastCapture' => null,
            ]);
        }

        $totalsQuery = ProductionLineHourlyTotal::query()
            ->whereIn('production_line_id', $productionLines->pluck('id'))
            ->orderBy('captured_at');

        if ($request->filled('range_start')) {
            $rangeStart = Carbon::parse($request->input('range_start')); // from string
            $totalsQuery->where('captured_at', ">=", $rangeStart);
        }

        $totals = $totalsQuery->get()->groupBy('production_line_id');

        $series = [];
        $lastCapture = null;

        foreach ($productionLines as $line) {
            $lineTotals = $totals->get($line->id, collect());
            if ($lineTotals->isEmpty()) {
                continue;
            }

            $process = $line->processes->first();
            $series[] = [
                'name' => sprintf('%s - %s', $line->name, $process?->description ?? __('Sin proceso')),
                'data' => $lineTotals->map(function (ProductionLineHourlyTotal $total) use (&$lastCapture) {
                    $lastCapture = $total->captured_at;
                    return [
                        'x' => $total->captured_at->format('Y-m-d H:i:s'),
                        'y' => round($total->total_time / 60, 2),
                    ];
                })->values(),
            ];
        }

        return response()->json([
            'series' => $series,
            'lastCapture' => $lastCapture?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtiene datos de WT/WTM (historial de tiempos de espera) para gráfica del Kanban
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function waitTimeHistoryData(Request $request, Customer $customer)
    {
        $productionLines = $customer->productionLines()
            ->with('processes')
            ->when($request->filled('line_ids'), function ($query) use ($request) {
                $lineIds = $request->input('line_ids', []);
                $query->whereIn('id', $lineIds);
            })
            ->get();

        if ($productionLines->isEmpty()) {
            return response()->json([
                'series' => [],
                'lastCapture' => null,
            ]);
        }

        $historyQuery = ProductionLineWaitTimeHistory::query()
            ->whereIn('production_line_id', $productionLines->pluck('id'))
            ->orderBy('captured_at');

        if ($request->filled('range_start')) {
            $rangeStart = Carbon::parse($request->input('range_start'));
            $historyQuery->where('captured_at', '>=', $rangeStart);
        }

        $history = $historyQuery->get()->groupBy('production_line_id');

        $seriesWT = [];
        $seriesWTM = [];
        $lastCapture = null;

        foreach ($productionLines as $line) {
            $lineHistory = $history->get($line->id, collect());
            if ($lineHistory->isEmpty()) {
                continue;
            }

            $dataWT = $lineHistory->map(function ($record) use (&$lastCapture) {
                $lastCapture = $record->captured_at;
                return [
                    'x' => $record->captured_at->format('Y-m-d H:i:s'),
                    'y' => $record->wait_time_mean ? round($record->wait_time_mean, 2) : null,
                ];
            })->filter(fn($item) => $item['y'] !== null)->values();

            $dataWTM = $lineHistory->map(function ($record) {
                return [
                    'x' => $record->captured_at->format('Y-m-d H:i:s'),
                    'y' => $record->wait_time_median ? round($record->wait_time_median, 2) : null,
                ];
            })->filter(fn($item) => $item['y'] !== null)->values();

            if ($dataWT->isNotEmpty()) {
                $seriesWT[] = [
                    'name' => $line->name . ' (WT)',
                    'data' => $dataWT,
                ];
            }

            if ($dataWTM->isNotEmpty()) {
                $seriesWTM[] = [
                    'name' => $line->name . ' (WTM)',
                    'data' => $dataWTM,
                ];
            }
        }

        return response()->json([
            'series' => array_merge($seriesWT, $seriesWTM),
            'lastCapture' => $lastCapture?->format('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Muestra el tablero Kanban para un proceso específico
     *
     * @param  \App\Models\Customer  $customer
     * @param  \App\Models\Process  $process
     * @return \Illuminate\View\View
     */
    public function showOrderKanban(Customer $customer, \App\Models\Process $process)
    {
        // Verificar que el proceso pertenece al cliente y obtener las líneas de producción
        $productionLines = $customer->productionLines()
            ->whereHas('processes', function($query) use ($process) {
                $query->where('process_id', $process->id);
            })
            ->with('processes')
            ->get();
            
        if ($productionLines->isEmpty()) {
            return redirect()->back()->with('error', 'No se encontraron líneas de producción para este proceso.');
        }
        
        // Obtener todas las órdenes para este proceso específico y este cliente
    // Para status 0 y 1 (pendientes y en progreso) mostramos todas
    // Para status 2, 3, 4 y 5 (completadas, pausadas, canceladas e incidencias) solo mostramos las de los últimos 3 días
    $query = \App\Models\ProductionOrder::where('process_category', $process->description)
        ->with(['originalOrder', 'transferredTo.toCustomer', 'transferredFrom.fromCustomer'])
        ->where(function($q) use ($customer) {
            // Filtrar por customer_id a través de la relación con original_orders
            // O mostrar si original_order_id es NULL (órdenes compartidas entre todos los centros)
            $q->whereHas('originalOrder', function($subq) use ($customer) {
                $subq->where('customer_id', $customer->id);
            })
            ->orWhereNull('original_order_id');
        }); // Filtrar por la categoría del proceso actual y por el cliente

    // Aplicamos filtros por status
    $query->where(function($q) {
        $fiveDaysAgo = now()->subDays(5)->startOfDay();

        // Status 0 y 1 (pendientes y en progreso) - mostrar todas
        $q->whereIn('status', [0, 1]);

        // Status 3, 4 y 5 (incidencias, pausadas, canceladas) - solo últimos 5 días
        $q->orWhere(function($subq) use ($fiveDaysAgo) {
            $subq->whereIn('status', [3, 4, 5])
                 ->where('updated_at', '>=', $fiveDaysAgo);
        });
    });

    // Ejecutamos la primera consulta para obtener órdenes con status 0, 1, 3, 4, 5
    $mainOrders = $query->get();

    // Consulta separada para status 2 (finalizadas) - últimos 5 días con límite de 100 tarjetas
    $status2Query = \App\Models\ProductionOrder::where('process_category', $process->description)
        ->with(['originalOrder', 'transferredTo.toCustomer', 'transferredFrom.fromCustomer'])
        ->where(function($q) use ($customer) {
            // Filtrar por customer_id a través de la relación con original_orders
            // O mostrar si original_order_id es NULL (órdenes compartidas entre todos los centros)
            $q->whereHas('originalOrder', function($subq) use ($customer) {
                $subq->where('customer_id', $customer->id);
            })
            ->orWhereNull('original_order_id');
        })
        ->where('status', 2)
        ->where('finished_at', '>=', now()->subDays(5)->startOfDay())
        ->orderBy('orden', 'desc')
        ->limit(100)
        ->get();

    // Unimos los resultados
    $processOrders = $mainOrders->merge($status2Query);
    
    // Ordenamos los resultados combinados
    $processOrders = $processOrders->sortBy('orden')->values()
            ->map(function($order){
                // Determinar el estado y color según el código de status
                $statusName = 'pending';
                $statusColor = '#6b7280'; // Gris por defecto
                
                switch ($order->status) {
                    case 0:
                        $statusName = 'pending'; // Pendiente
                        $statusColor = '#6b7280'; // Gris
                        break;
                    case 1:
                        $statusName = 'in_progress'; // En proceso
                        $statusColor = '#3b82f6'; // Azul
                        break;
                    case 2:
                        $statusName = 'completed'; // Finalizado
                        $statusColor = '#10b981'; // Verde
                        break;
                    case 3:
                        $statusName = 'paused'; // Pausado
                        $statusColor = '#f59e0b'; // Amarillo/ámbar
                        break;
                    case 4:
                        $statusName = 'cancelled'; // Cancelado
                        $statusColor = '#6b7280'; // Gris oscuro
                        break;
                    case 5:
                        $statusName = 'incidents'; // Con incidencia
                        $statusColor = '#ef4444'; // Rojo
                        break;
                }
                
                // 1. Preparamos la variable con el valor por defecto
                $tiempoTeoricoFormateado = 'Sin Tiempo Teórico';

                // 2. Si existe el tiempo teórico en segundos, lo convertimos
                if (isset($order->theoretical_time)) {
                    // *** CORRECCIÓN: Llamada al método estático con `self::` ***
                    $tiempoTeoricoFormateado = self::convertirSegundosA_H_M_S($order->theoretical_time);
                }
                
                // Obtener las descripciones de artículos asociados al proceso
                $articlesDescriptions = [];
                if ($order->original_order_process_id) {
                    $articles = \App\Models\OriginalOrderArticle::where('original_order_process_id', $order->original_order_process_id)
                        ->pluck('descripcion_articulo')
                        ->filter() // Filtrar valores nulos o vacíos
                        ->toArray();
                    $articlesDescriptions = $articles;
                }
                
                // Build AFTER info for this order
                $afterItems = ($afterByOrder[$order->id] ?? collect())->map(function($a){
                    return [
                        'id' => $a->id,
                        'barcode_scan_id' => $a->barcode_scan_id,
                        'production_line_id' => $a->production_line_id,
                        'barcoder_id' => $a->barcoder_id,
                        'order_id' => $a->order_id,
                        'grupo_numero' => $a->grupo_numero,
                        'scanned_at' => $a->scanned_at,
                        'barcode' => $a->barcode ?? null,
                    ];
                })->values();

                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'status' => $statusName,
                    'status_code' => $order->status,
                    'productionLineId' => $order->production_line_id,
                    'box' => $order->box ?? 0,
                    'units' => $order->units ?? 0,
                    'created_at' => $order->created_at,
                    'delivery_date' => $order->delivery_date,
                    'json' => $order->json ?? [],
                    'statusColor' => $statusColor,
                    'grupo_numero' => $order->grupo_numero ?? '0',
                    'processes_to_do' => $order->processes_to_do ?? 'Sin Procesos',
                    'processes_done' => $order->processes_done ?? '',
                    'theoretical_time' => $tiempoTeoricoFormateado,
                    'customerId' => $order->customerId ?? 'Sin Cliente',
                    'original_order_id' => $order->original_order_id ?? 'Sin Orden Original',
                    'ref_order' => optional($order->originalOrder)->ref_order,
                    'articles_descriptions' => $articlesDescriptions,
                //en lugar de 0 por defecto aqui 'orden' => (int)($order->orden ?? '0') ponemos que sea por production_line_id el orden mas grande que existe y le damos +1
                'orden' => $order->production_line_id ? ProductionOrder::where('production_line_id', $order->production_line_id)->max('orden') + 1 : 0,
                'has_stock' => $order->has_stock ?? 1, // Añadimos el campo has_stock, por defecto 1 si no existe
                'is_priority' => $order->is_priority ?? false,
                'accumulated_time' => $order->accumulated_time ?? 0,
                'fecha_pedido_erp' => $order->fecha_pedido_erp,  
                'estimated_start_datetime' => $order->estimated_start_datetime,
                'estimated_end_datetime' => $order->estimated_end_datetime,
                'ready_after_datetime' => $order->ready_after_datetime,
                // Flags de readiness precomputados para simplificar el JS
                'is_ready' => (function() use ($order) {
                    if (!$order->ready_after_datetime) return true;
                    $target = Carbon::parse($order->ready_after_datetime, 'Europe/Madrid');
                    return now('Europe/Madrid')->greaterThanOrEqualTo($target);
                })(),
                'ready_in_seconds' => (function() use ($order) {
                    if (!$order->ready_after_datetime) return 0;
                    $now = now('Europe/Madrid');
                    $target = Carbon::parse($order->ready_after_datetime, 'Europe/Madrid');
                    $diff = $now->diffInSeconds($target, false); // negativo si ya pasó
                    return $diff > 0 ? $diff : 0;
                })(),
                'number_of_pallets' => $order->number_of_pallets ?? 0,
                // AFTER aggregation for UI usage
                'after' => $afterItems,
                'after_count' => $afterItems->count(),
                // Transfer information
                'transferred_to' => $order->transferredTo ? [
                    'customer_name' => $order->transferredTo->toCustomer->name ?? 'Desconocido',
                    'transferred_at' => optional($order->transferredTo->transferred_at)->format('d/m/Y H:i'),
                    'status' => $order->transferredTo->status,
                ] : null,
                'transferred_from' => $order->transferredFrom ? [
                    'customer_name' => $order->transferredFrom->fromCustomer->name ?? 'Desconocido',
                    'transferred_at' => optional($order->transferredFrom->transferred_at)->format('d/m/Y H:i'),
                    'status' => $order->transferredFrom->status,
                ] : null,
                ];
            });
        
        // Registrar en el log para depuración
        \Log::info('Órdenes para el proceso ' . $process->description . ':', [
            'count' => $processOrders->count(),
            'process_id' => $process->id
        ]);
        
        // Preparar datos de líneas de producción para la vista
        $productionLinesData = $productionLines->map(function($line) {
            return [
                'id' => $line->id,
                'name' => $line->name,
                'token' => $line->token // Añadimos el token de la línea de producción
            ];
        })->toArray();
        
        // Registrar en el log para depuración
        \Log::info('Líneas de producción para el proceso ' . $process->id . ':', $productionLinesData);
        
        // Obtener todos los customers excepto el actual para el modal de transferencia
        $otherCustomers = Customer::where('id', '!=', $customer->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('customers.order-kanban', [
            'customer' => $customer,
            'process' => $process,
            'productionLines' => $productionLinesData,
            'processOrders' => $processOrders,
            'otherCustomers' => $otherCustomers
        ]);
    }
    /**
     * Obtiene los datos del Kanban para actualización mediante AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKanbanData(Customer $customer, \App\Models\Process $process)
    {

        // Obtener todas las órdenes para este proceso específico y este cliente
        // Para status 0 y 1 (pendientes y en progreso) mostramos todas
        // Para status 2, 3, 4 y 5 (completadas, pausadas, canceladas e incidencias) solo mostramos las de los últimos 5 días
        $query = \App\Models\ProductionOrder::where('process_category', $process->description)
            ->with(['originalOrder', 'transferredTo.toCustomer', 'transferredFrom.fromCustomer'])
            ->where(function($q) use ($customer) {
                // Filtrar por customer_id a través de la relación con original_orders
                // O mostrar si original_order_id es NULL (órdenes compartidas entre todos los centros)
                $q->whereHas('originalOrder', function($subq) use ($customer) {
                    $subq->where('customer_id', $customer->id);
                })
                ->orWhereNull('original_order_id');
            }); // Filtrar por la categoría del proceso actual y por el cliente

        // Aplicamos filtros por status
        $query->where(function($q) {
            $fiveDaysAgo = now()->subDays(5)->startOfDay();

            // Status 0 y 1 (pendientes y en progreso) - mostrar todas
            $q->whereIn('status', [0, 1]);

            // Status 3, 4 y 5 (incidencias, pausadas, canceladas) - solo últimos 5 días
            $q->orWhere(function($subq) use ($fiveDaysAgo) {
                $subq->whereIn('status', [3, 4, 5])
                     ->where('updated_at', '>=', $fiveDaysAgo);
            });
        });

        // Ejecutamos la primera consulta para obtener órdenes con status 0, 1, 3, 4, 5
        $mainOrders = $query->get();

        // Consulta separada para status 2 (finalizadas) - últimos 5 días con límite de 100 tarjetas
        $status2Query = \App\Models\ProductionOrder::where('process_category', $process->description)
            ->with(['originalOrder'])
            ->where(function($q) use ($customer) {
                // Filtrar por customer_id a través de la relación con original_orders
                // O mostrar si original_order_id es NULL (órdenes compartidas entre todos los centros)
                $q->whereHas('originalOrder', function($subq) use ($customer) {
                    $subq->where('customer_id', $customer->id);
                })
                ->orWhereNull('original_order_id');
            })
            ->where('status', 2)
            ->where('finished_at', '>=', now()->subDays(5)->startOfDay())
            ->orderBy('orden', 'desc')
            ->limit(100)
            ->get();

        // Unimos los resultados
        $processOrders = $mainOrders->merge($status2Query);

        // Prefetch BarcodeScanAfter entries (with barcode) grouped by target production_order_id
        try {
            $afterByOrder = BarcodeScanAfter::leftJoin('barcode_scans', 'barcode_scans.id', '=', 'barcode_scans_after.barcode_scan_id')
                ->whereIn('barcode_scans_after.production_order_id', $processOrders->pluck('id')->all())
                ->select(
                    'barcode_scans_after.*',
                    DB::raw('barcode_scans.barcode as barcode')
                )
                ->orderBy('barcode_scans_after.id','desc')
                ->get()
                ->groupBy('production_order_id');
        } catch (\Throwable $e) {
            $afterByOrder = collect();
            Log::warning('showOrderKanban: error preloading BarcodeScanAfter: '.$e->getMessage());
        }
        
        // Ordenamos los resultados combinados
        $processOrders = $processOrders->sortBy('orden')->values()
                ->map(function($order) use ($afterByOrder){
                    // Determinar el estado y color según el código de status
                    $statusName = 'pending';
                    $statusColor = '#6b7280'; // Gris por defecto
                    
                    switch ($order->status) {
                        case 0:
                            $statusName = 'pending'; // Pendiente
                            $statusColor = '#6b7280'; // Gris
                            break;
                        case 1:
                            $statusName = 'in_progress'; // En proceso
                            $statusColor = '#3b82f6'; // Azul
                            break;
                        case 2:
                            $statusName = 'completed'; // Finalizado
                            $statusColor = '#10b981'; // Verde
                            break;
                        case 3:
                            $statusName = 'paused'; // Pausado
                            $statusColor = '#f59e0b'; // Amarillo/ámbar
                            break;
                        case 4:
                            $statusName = 'cancelled'; // Cancelado
                            $statusColor = '#6b7280'; // Gris oscuro
                            break;
                        case 5:
                            $statusName = 'incidents'; // Con incidencia
                            $statusColor = '#ef4444'; // Rojo
                            break;
                    }
                    
                    // 1. Preparamos la variable con el valor por defecto
                    $tiempoTeoricoFormateado = 'Sin Tiempo Teórico';

                    // 2. Si existe el tiempo teórico en segundos, lo convertimos
                    if (isset($order->theoretical_time)) {
                        $tiempoTeoricoFormateado = self::convertirSegundosA_H_M_S($order->theoretical_time);
                    }
                    
                    // Obtener las descripciones de artículos asociados al proceso
                    $articlesDescriptions = [];
                    if ($order->original_order_process_id) {
                        $articles = \App\Models\OriginalOrderArticle::where('original_order_process_id', $order->original_order_process_id)
                            ->pluck('descripcion_articulo')
                            ->filter() // Filtrar valores nulos o vacíos
                            ->toArray();
                        $articlesDescriptions = $articles;
                    }
                    
                    $afterItems = ($afterByOrder[$order->id] ?? collect())->map(function($a){
                        return [
                            'id' => $a->id,
                            'barcode_scan_id' => $a->barcode_scan_id,
                            'production_line_id' => $a->production_line_id,
                            'barcoder_id' => $a->barcoder_id,
                            'order_id' => $a->order_id,
                            'grupo_numero' => $a->grupo_numero,
                            'scanned_at' => $a->scanned_at,
                            'barcode' => $a->barcode ?? null,
                        ];
                    })->values();

                    return [
                        'id' => $order->id,
                        'order_id' => $order->order_id,
                        'status' => $statusName,
                        'status_code' => $order->status,
                        'productionLineId' => $order->production_line_id,
                        'box' => $order->box ?? 0,
                        'units' => $order->units ?? 0,
                        'created_at' => $order->created_at,
                        'delivery_date' => $order->delivery_date,
                        'json' => $order->json ?? [],
                        'statusColor' => $statusColor,
                        'grupo_numero' => $order->grupo_numero ?? '0',
                        'processes_to_do' => $order->processes_to_do ?? 'Sin Procesos',
                        'processes_done' => $order->processes_done ?? '',
                        'theoretical_time' => $tiempoTeoricoFormateado,
                        'customerId' => $order->customerId ?? 'Sin Cliente',
                        'original_order_id' => $order->original_order_id ?? 'Sin Orden Original',
                        'ref_order' => optional($order->originalOrder)->ref_order,
                        'articles_descriptions' => $articlesDescriptions,
                        'orden' => $order->orden ?? 0,
                        'has_stock' => $order->has_stock ?? 1,
                        'is_priority' => $order->is_priority ?? false,
                        'accumulated_time' => $order->accumulated_time ?? 0,
                        'fecha_pedido_erp' => $order->fecha_pedido_erp,
                        'estimated_start_datetime' => $order->estimated_start_datetime,
                        'estimated_end_datetime' => $order->estimated_end_datetime,
                        'ready_after_datetime' => $order->ready_after_datetime,
                        'is_ready' => (function() use ($order) {
                            if (!$order->ready_after_datetime) return true;
                            $target = Carbon::parse($order->ready_after_datetime, 'Europe/Madrid');
                            return now('Europe/Madrid')->greaterThanOrEqualTo($target);
                        })(),
                        'ready_in_seconds' => (function() use ($order) {
                            if (!$order->ready_after_datetime) return 0;
                            $now = now('Europe/Madrid');
                            $target = Carbon::parse($order->ready_after_datetime, 'Europe/Madrid');
                            $diff = $now->diffInSeconds($target, false);
                            return $diff > 0 ? $diff : 0;
                        })(),
                        'number_of_pallets' => $order->number_of_pallets ?? 0,
                        'after' => $afterItems,
                        'after_count' => $afterItems->count(),
                        'note' => $order->note,
                        // Transfer information
                        'transferred_to' => $order->transferredTo ? [
                            'customer_name' => $order->transferredTo->toCustomer->name ?? 'Desconocido',
                            'transferred_at' => optional($order->transferredTo->transferred_at)->format('d/m/Y H:i'),
                            'status' => $order->transferredTo->status,
                        ] : null,
                        'transferred_from' => $order->transferredFrom ? [
                            'customer_name' => $order->transferredFrom->fromCustomer->name ?? 'Desconocido',
                            'transferred_at' => optional($order->transferredFrom->transferred_at)->format('d/m/Y H:i'),
                            'status' => $order->transferredFrom->status,
                        ] : null,
                    ];
                });

        return response()->json([
            'processOrders' => $processOrders
        ]);
    }
    
    /**
     * Convierte un número total de segundos a formato HH:MM:SS.
     *
     * @param int $segundos El número total de segundos.
     * @return string El tiempo formateado como "H:i:s".
     */
    private function convertirSegundosA_H_M_S(int $segundos) {
        // Evita valores negativos o no numéricos
        if (!is_numeric($segundos) || $segundos < 0) {
            return '00:00:00';
        }

        // Calcula horas, minutos y segundos
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segundos_restantes = $segundos % 60;

        // Formatea la salida para que siempre tenga dos dígitos (01, 02, etc.)
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos_restantes);
    }
    /**
     * Muestra el formulario para editar un cliente existente.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            // Cargar el cliente con sus mapeos de campos ordenados
            $customer = Customer::with([
                'fieldMappings' => function($query) {
                    $query->orderBy('id');
                },
                'processFieldMappings' => function($query) {
                    $query->orderBy('id');
                },
                'articleFieldMappings' => function($query) {
                    $query->orderBy('id');
                },
                'callbackFieldMappings' => function($query) {
                    $query->orderBy('id');
                }
            ])->findOrFail($id);
            
            // Campos estándar que podríamos querer mapear para orders
            $standardFields = [
                'order_id' => 'ID del Pedido',
                'client_number' => 'Número de Cliente',
                'address' => 'Dirección',
                'phone' => 'Teléfono',
                'cif_nif' => 'CIF / NIF',
                'ref_order' => 'Referencia de Pedido',
                'route_name' => 'Nombre de Ruta',
                'created_at' => 'Fecha de Creación',
                'delivery_date' => 'Fecha de Entrega',
                'fecha_pedido_erp' => 'Fecha de Creación en ERP',
                'in_stock' => 'En Stock (1/0)'
            ];
            
            // Campos estándar que podríamos querer mapear para procesos
            $processStandardFields = [
                'process_id' => 'ID del Proceso',
                'time' => 'Tiempo del Proceso',
                'box' => 'Caja',
                'units_box' => 'Unidades por Caja',
                'number_of_pallets' => 'Número de Palets'
            ];
            
            // Opciones de transformaciones disponibles
            $transformationOptions = [
                'trim' => 'Eliminar espacios',
                'uppercase' => 'Convertir a mayúsculas',
                'lowercase' => 'Convertir a minúsculas',
                'to_integer' => 'Convertir a entero',
                'to_float' => 'Convertir a decimal',
                'to_boolean' => 'Convertir a booleano (1/0)'
            ];
            
            // Define article standard fields
            $articleStandardFields = [
                'codigo_articulo' => 'Código de Artículo (Requerido)',
                'descripcion_articulo' => 'Descripción del Artículo',
                'grupo_articulo' => 'Grupo del Artículo',
                'in_stock' => 'En Stock (1/0)'
            ];
            
            // Define callback standard fields (campos de production_orders)
            $callbackStandardFields = [
                'id' => 'ID de la Orden de Producción',
                'order_id' => 'ID del Pedido',
                'production_line_id' => 'ID de Línea de Producción',
                'status' => 'Estado (0=Pendiente, 1=En Curso, 2=Finalizada)',
                'box' => 'Número de Cajas',
                'units_box' => 'Unidades por Caja',
                'units' => 'Total de Unidades',
                'orden' => 'Orden de Fabricación',
                'theoretical_time' => 'Tiempo Teórico (segundos)',
                'accumulated_time' => 'Tiempo Acumulado (segundos)',
                'process_category' => 'Categoría del Proceso',
                'delivery_date' => 'Fecha de Entrega',
                'customerId' => 'ID del Cliente',
                'original_order_id' => 'ID de Orden Original',
                'original_order_process_id' => 'ID de Proceso de Orden Original',
                'processes_code' => 'Código del Proceso (desde original_order_process_id → processes.code)',
                'grupo_numero' => 'Número de Grupo',
                'processes_to_do' => 'Procesos por Hacer',
                'processes_done' => 'Procesos Completados',
                'is_priority' => 'Es Prioritaria (1/0)',
                'fecha_pedido_erp' => 'Fecha del Pedido en ERP',
                'estimated_start_datetime' => 'Fecha/Hora Estimada de Inicio',
                'estimated_end_datetime' => 'Fecha/Hora Estimada de Fin',
                'ready_after_datetime' => 'Disponible Después de',
                'finished_at' => 'Fecha/Hora de Finalización',
                'created_at' => 'Fecha de Creación',
                'updated_at' => 'Fecha de Actualización',
                'number_of_pallets' => 'Número de Palets',
                'note' => 'Notas'
            ];
            
            return view('customers.edit', compact(
                'customer', 
                'standardFields', 
                'processStandardFields', 
                'articleStandardFields',
                'callbackStandardFields',
                'callbackStandardFields',
            'transformationOptions'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Error al cargar el formulario de edición del cliente: ' . $e->getMessage());
            return redirect()->route('customers.index')
                ->with('error', 'Error al cargar el formulario de edición: ' . $e->getMessage());
        }
    }

    /**
     * Validate that the URL is valid, including URLs with {order_id} placeholders
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateUrlWithPlaceholder($attribute, $value, $parameters, $validator)
    {
        if (empty($value)) {
            return true;
        }
        
        // Replace the {order_id} placeholder with a valid ID for validation
        $testUrl = str_replace('{order_id}', '12345', $value);
        
        return filter_var($testUrl, FILTER_VALIDATE_URL) !== false;
    }
    
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        // Validación personalizada
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'order_listing_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de listado de pedidos no es válido.');
                }
            }],
            'order_detail_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de detalle de pedido no es válido.');
                }
            }],
            'token' => 'nullable|string|max:255',
            'callback_finish_process' => 'nullable|boolean',
            'callback_url' => 'nullable|url|required_if:callback_finish_process,1',
            // Nuevos campos de configuración de tiempos
            'api_timeout' => 'required|integer|min:5|max:300',
            'lock_timeout' => 'required|integer|min:5|max:180',
            'search_delay' => 'required|integer|min:0|max:5000',
            'lock_timeout_tolerance' => 'required|numeric|min:0|max:0.50',
            'enable_parallel_processing' => 'nullable|boolean',
            'field_mappings' => 'nullable|array',
            'field_mappings.*.source_field' => 'required_with:field_mappings|string',
            'field_mappings.*.target_field' => 'required_with:field_mappings|string',
            'field_mappings.*.transformations' => 'nullable|array',
            'field_mappings.*.transformations.*' => 'string',
            'field_mappings.*.is_required' => 'nullable|boolean',
            'process_field_mappings' => 'nullable|array',
            'process_field_mappings.*.source_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.target_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.transformations' => 'nullable|array',
            'process_field_mappings.*.transformations.*' => 'string',
            'process_field_mappings.*.is_required' => 'nullable|boolean',
            'article_field_mappings' => 'nullable|array',
            'article_field_mappings.*.source_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.target_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.transformations' => 'nullable|array',
            'article_field_mappings.*.transformations.*' => 'string',
            'article_field_mappings.*.is_required' => 'nullable|boolean',
            'callback_field_mappings' => 'nullable|array',
            'callback_field_mappings.*.source_field' => 'required_with:callback_field_mappings|string',
            'callback_field_mappings.*.target_field' => 'required_with:callback_field_mappings|string',
            'callback_field_mappings.*.transformations' => 'nullable|array',
            'callback_field_mappings.*.transformations.*' => 'string',
            'callback_field_mappings.*.is_required' => 'nullable|boolean'
        ]);

        // Validar la solicitud
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // DEBUG: registrar qué llega desde el frontend para callback_field_mappings y qué pasa la validación
        try {
            \Log::info('Customers.update incoming callback_field_mappings (raw input)', [
                'callback_field_mappings' => $request->input('callback_field_mappings')
            ]);
            \Log::info('Customers.update validated callback_field_mappings', [
                'callback_field_mappings' => $validatedData['callback_field_mappings'] ?? null
            ]);
        } catch (\Throwable $e) {
            // evitar romper flujo de actualización por fallo de log
        }

        try {
            // Iniciar transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // Actualizar los datos básicos del cliente
            $customer->update([
                'name' => $validatedData['name'],
                'order_listing_url' => $validatedData['order_listing_url'] ?? null,
                'order_detail_url' => $validatedData['order_detail_url'] ?? null,
                'token' => $validatedData['token'] ?? null,
                'callback_finish_process' => $validatedData['callback_finish_process'] ?? false,
                'callback_url' => $validatedData['callback_url'] ?? null,
                // Nuevos campos de configuración de tiempos
                'api_timeout' => $validatedData['api_timeout'] ?? 30,
                'lock_timeout' => $validatedData['lock_timeout'] ?? 30,
                'search_delay' => $validatedData['search_delay'] ?? 100,
                'lock_timeout_tolerance' => $validatedData['lock_timeout_tolerance'] ?? 0.10,
                'enable_parallel_processing' => $validatedData['enable_parallel_processing'] ?? true,
            ]);

            // Sincronizar los mapeos de campos de orders si existen
            if (isset($validatedData['field_mappings'])) {
                $updatedMappingIds = [];
                
                // Procesar cada mapeo
                foreach ($validatedData['field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->fieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformations' => $mappingData['transformations'] ?? [],
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->fieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformations' => $mappingData['transformations'] ?? [],
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedMappingIds)) {
                    $customer->fieldMappings()->whereNotIn('id', $updatedMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->fieldMappings()->delete();
            }

            // Sincronizar los mapeos de campos de procesos si existen
            if (isset($validatedData['process_field_mappings'])) {
                $updatedProcessMappingIds = [];
                
                // Procesar cada mapeo de proceso
                foreach ($validatedData['process_field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->processFieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformations' => $mappingData['transformations'] ?? [],
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedProcessMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->processFieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformations' => $mappingData['transformations'] ?? [],
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedProcessMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedProcessMappingIds)) {
                    $customer->processFieldMappings()->whereNotIn('id', $updatedProcessMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->processFieldMappings()->delete();
            }

            // Sincronizar los mapeos de campos de artículos si existen
            if (isset($validatedData['article_field_mappings'])) {
                $updatedArticleMappingIds = [];
                
                // Procesar cada mapeo de artículo
                foreach ($validatedData['article_field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->articleFieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformations' => $mappingData['transformations'] ?? [],
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedArticleMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->articleFieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformations' => $mappingData['transformations'] ?? [],
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedArticleMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedArticleMappingIds)) {
                    $customer->articleFieldMappings()->whereNotIn('id', $updatedArticleMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->articleFieldMappings()->delete();
            }

            // Sincronizar los mapeos de campos de callback si existen (SIEMPRE ejecutar, independientemente de artículos)
            if (isset($validatedData['callback_field_mappings'])) {
                $updatedCallbackMappingIds = [];
                
                // Procesar cada mapeo de callback
                foreach ($validatedData['callback_field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->callbackFieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformation' => (isset($mappingData['transformations']) && is_array($mappingData['transformations']))
                                    ? implode(',', $mappingData['transformations'])
                                    : ($mappingData['transformations'] ?? null),
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedCallbackMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->callbackFieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformation' => (isset($mappingData['transformations']) && is_array($mappingData['transformations']))
                                ? implode(',', $mappingData['transformations'])
                                : ($mappingData['transformations'] ?? null),
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedCallbackMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedCallbackMappingIds)) {
                    $customer->callbackFieldMappings()->whereNotIn('id', $updatedCallbackMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->callbackFieldMappings()->delete();
            }

            // Confirmar la transacción
            DB::commit();

            return redirect()->route('customers.edit', $customer->id)
                ->with('success', 'Cliente actualizado correctamente.');

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            \Log::error('Error al actualizar el cliente: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el cliente: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
    
    /**
     * Devuelve el HTML para una fila de mapeo de campos
     *
     * @param int $customerId
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function fieldMappingRow($customerId, Request $request)
    {
        try {
            $index = $request->input('index', 0);
            $type = $request->input('type', 'order'); // 'order', 'process' o 'article'
            
            if ($type === 'process') {
                // Campos estándar para procesos
                $standardFields = [
                    'process_id' => 'ID del Proceso',
                    'time' => 'Tiempo del Proceso',
                    'box' => 'Caja',
                    'units_box' => 'Unidades por Caja'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de procesos
                $html = view('customers.partials.process_field_mappings', [
                    'index' => $index,
                    'processStandardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
                
            } else if ($type === 'article') {
                // Campos estándar para artículos
                $standardFields = [
                    'codigo_articulo' => 'Código de Artículo (Requerido)',
                    'descripcion_articulo' => 'Descripción del Artículo',
                    'grupo_articulo' => 'Grupo del Artículo',
                    'in_stock' => 'En Stock (1/0)'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de artículos
                $html = view('customers.partials.article_field_mappings', [
                    'index' => $index,
                    'articleStandardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
                
            } else if ($type === 'callback') {
                // Campos estándar para callback (campos de production_orders)
                $standardFields = [
                    'id' => 'ID de la Orden de Producción',
                    'order_id' => 'ID del Pedido',
                    'production_line_id' => 'ID de Línea de Producción',
                    'status' => 'Estado (0=Pendiente, 1=En Curso, 2=Finalizada)',
                    'box' => 'Número de Cajas',
                    'units_box' => 'Unidades por Caja',
                    'units' => 'Total de Unidades',
                    'orden' => 'Orden de Fabricación',
                    'theoretical_time' => 'Tiempo Teórico (segundos)',
                    'accumulated_time' => 'Tiempo Acumulado (segundos)',
                    'process_category' => 'Categoría del Proceso',
                    'delivery_date' => 'Fecha de Entrega',
                    'customerId' => 'ID del Cliente',
                    'original_order_id' => 'ID de Orden Original',
                    'original_order_process_id' => 'ID de Proceso de Orden Original',
                    'processes_code' => 'Código del Proceso (desde original_order_process_id → processes.code)',
                    'grupo_numero' => 'Número de Grupo',
                    'processes_to_do' => 'Procesos por Hacer',
                    'processes_done' => 'Procesos Completados',
                    'is_priority' => 'Es Prioritaria (1/0)',
                    'fecha_pedido_erp' => 'Fecha del Pedido en ERP',
                    'estimated_start_datetime' => 'Fecha/Hora Estimada de Inicio',
                    'estimated_end_datetime' => 'Fecha/Hora Estimada de Fin',
                    'ready_after_datetime' => 'Disponible Después de',
                    'finished_at' => 'Fecha/Hora de Finalización',
                    'created_at' => 'Fecha de Creación',
                    'updated_at' => 'Fecha de Actualización',
                    'number_of_pallets' => 'Número de Palets',
                    'note' => 'Notas'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de callback
                $html = view('customers.partials.callback_field_mappings', [
                    'index' => $index,
                    'callbackStandardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
                
            } else {
                // Usar el mismo array de campos estándar que en create/edit para orders
                $standardFields = [
                    'order_id' => 'ID del Pedido',
                    'client_number' => 'Número de Cliente',
                    'address' => 'Dirección',
                    'phone' => 'Teléfono',
                    'cif_nif' => 'CIF / NIF',
                    'ref_order' => 'Referencia de Pedido',
                    'route_name' => 'Nombre de Ruta',
                    'created_at' => 'Fecha de Creación',
                    'delivery_date' => 'Fecha de Entrega',
                    'fecha_pedido_erp' => 'Fecha de Creación en ERP',
                    'in_stock' => 'En Stock (1/0)'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de orders
                $html = view('customers.partials.field_mappings', [
                    'index' => $index,
                    'standardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
            }
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en fieldMappingRow: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la fila de mapeo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        // Campos estándar que podríamos querer mapear
        $standardFields = [
            'order_id' => 'ID del Pedido',
            'client_number' => 'Número de Cliente',
            'address' => 'Dirección',
            'phone' => 'Teléfono',
            'cif_nif' => 'CIF / NIF',
            'ref_order' => 'Referencia de Pedido',
            'route_name' => 'Nombre de Ruta',
            'created_at' => 'Fecha de Creación',
            'delivery_date' => 'Fecha de Entrega',
            'fecha_pedido_erp' => 'Fecha de Creación en ERP',
            'in_stock' => 'En Stock (1/0)'
        ];
        
        // Campos estándar para procesos
        $processStandardFields = [
            'process_id' => 'ID del Proceso',
            'time' => 'Tiempo del Proceso',
            'box' => 'Caja',
            'units_box' => 'Unidades por Caja',
            'number_of_pallets' => 'Número de Palets'
        ];
        
        // Campos estándar para artículos
        $articleStandardFields = [
            'codigo_articulo' => 'Código de Artículo (Requerido)',
            'descripcion_articulo' => 'Descripción del Artículo',
            'grupo_articulo' => 'Grupo del Artículo',
            'in_stock' => 'En Stock (1/0)'
        ];
        
        // Opciones de transformaciones disponibles
        $transformationOptions = [
            'trim' => 'Eliminar espacios',
            'uppercase' => 'Convertir a mayúsculas',
            'lowercase' => 'Convertir a minúsculas',
            'to_integer' => 'Convertir a entero',
            'to_float' => 'Convertir a decimal',
            'to_boolean' => 'Convertir a booleano (1/0)'
        ];
        
        // Define callback standard fields (campos de production_orders)
        $callbackStandardFields = [
            'id' => 'ID de la Orden de Producción',
            'order_id' => 'ID del Pedido',
            'production_line_id' => 'ID de Línea de Producción',
            'status' => 'Estado (0=Pendiente, 1=En Curso, 2=Finalizada)',
            'box' => 'Número de Cajas',
            'units_box' => 'Unidades por Caja',
            'units' => 'Total de Unidades',
            'orden' => 'Orden de Fabricación',
            'theoretical_time' => 'Tiempo Teórico (segundos)',
            'accumulated_time' => 'Tiempo Acumulado (segundos)',
            'process_category' => 'Categoría del Proceso',
            'delivery_date' => 'Fecha de Entrega',
            'customerId' => 'ID del Cliente',
            'original_order_id' => 'ID de Orden Original',
            'original_order_process_id' => 'ID de Proceso de Orden Original',
            'processes_code' => 'Código del Proceso (desde original_order_process_id → processes.code)',
            'grupo_numero' => 'Número de Grupo',
            'processes_to_do' => 'Procesos por Hacer',
            'processes_done' => 'Procesos Completados',
            'is_priority' => 'Es Prioritaria (1/0)',
            'fecha_pedido_erp' => 'Fecha del Pedido en ERP',
            'estimated_start_datetime' => 'Fecha/Hora Estimada de Inicio',
            'estimated_end_datetime' => 'Fecha/Hora Estimada de Fin',
            'ready_after_datetime' => 'Disponible Después de',
            'finished_at' => 'Fecha/Hora de Finalización',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Fecha de Actualización',
            'number_of_pallets' => 'Número de Palets',
            'note' => 'Notas'
        ];
        
        return view('customers.create', compact(
            'standardFields', 
            'processStandardFields',
            'articleStandardFields',
            'callbackStandardFields',
            'transformationOptions'
        ));
    }

    public function store(Request $request)
    {
        // Validación personalizada
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token_zerotier' => 'required|string|max:255',
            'order_listing_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de listado de pedidos no es válido.');
                }
            }],
            'order_detail_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de detalle de pedido no es válido.');
                }
            }],
            'callback_finish_process' => 'nullable|boolean',
            'callback_url' => 'nullable|url|required_if:callback_finish_process,1',
            'field_mappings' => 'nullable|array',
            'field_mappings.*.source_field' => 'required_with:field_mappings|string',
            'field_mappings.*.target_field' => 'required_with:field_mappings|string',
            'field_mappings.*.transformations' => 'nullable|array',
            'field_mappings.*.transformations.*' => 'string',
            'field_mappings.*.is_required' => 'nullable|boolean',
            'process_field_mappings' => 'nullable|array',
            'process_field_mappings.*.source_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.target_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.transformations' => 'nullable|array',
            'process_field_mappings.*.transformations.*' => 'string',
            'process_field_mappings.*.is_required' => 'nullable|boolean',
            'article_field_mappings' => 'nullable|array',
            'article_field_mappings.*.source_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.target_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.transformations' => 'nullable|array',
            'article_field_mappings.*.transformations.*' => 'string',
            'article_field_mappings.*.is_required' => 'nullable|boolean',
            'callback_field_mappings' => 'nullable|array',
            'callback_field_mappings.*.source_field' => 'required_with:callback_field_mappings|string',
            'callback_field_mappings.*.target_field' => 'required_with:callback_field_mappings|string',
            'callback_field_mappings.*.transformations' => 'nullable|array',
            'callback_field_mappings.*.transformations.*' => 'string',
            'callback_field_mappings.*.is_required' => 'nullable|boolean'
        ]);

        // Validar la solicitud
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        try {
            // Iniciar transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // Generar un token único
            $token = bin2hex(random_bytes(16));

            // Crear el cliente
            $customer = Customer::create([
                'name' => $validatedData['name'],
                'token_zerotier' => $validatedData['token_zerotier'],
                'token' => $token,
                'order_listing_url' => $validatedData['order_listing_url'] ?? null,
                'order_detail_url' => $validatedData['order_detail_url'] ?? null,
                'callback_finish_process' => $validatedData['callback_finish_process'] ?? false,
                'callback_url' => $validatedData['callback_url'] ?? null,
            ]);

            // Sincronizar los mapeos de campos si existen
            if (isset($validatedData['field_mappings'])) {
                foreach ($validatedData['field_mappings'] as $mappingData) {
                    $customer->fieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Sincronizar los mapeos de procesos si existen
            if (isset($validatedData['process_field_mappings'])) {
                foreach ($validatedData['process_field_mappings'] as $mappingData) {
                    $customer->processFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Sincronizar los mapeos de artículos si existen
            if (isset($validatedData['article_field_mappings'])) {
                foreach ($validatedData['article_field_mappings'] as $mappingData) {
                    $customer->articleFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }
            // Sincronizar los mapeos de campos de procesos si existen
            if (isset($validatedData['process_field_mappings'])) {
                foreach ($validatedData['process_field_mappings'] as $mappingData) {
                    $customer->processFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Sincronizar los mapeos de campos de artículos si existen
            if (isset($validatedData['article_field_mappings'])) {
                foreach ($validatedData['article_field_mappings'] as $mappingData) {
                    $customer->articleFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Sincronizar los mapeos de campos de callback si existen
            if (isset($validatedData['callback_field_mappings'])) {
                foreach ($validatedData['callback_field_mappings'] as $mappingData) {
                    $customer->callbackFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformation' => is_array($mappingData['transformations'] ?? []) ? implode(',', $mappingData['transformations']) : ($mappingData['transformations'] ?? null),
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Confirmar la transacción
            DB::commit();

            return redirect()->route('customers.edit', $customer->id)
                ->with('success', 'Cliente creado correctamente.');

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            \Log::error('Error al crear el cliente: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error al crear el cliente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Lista los sensores asociados a todas las líneas de producción de un cliente.
     * Ruta: customers/{customer}/sensors
     */
    public function sensorsIndex(Customer $customer)
    {
        // IDs de líneas del cliente
        $lineIds = $customer->productionLines()->pluck('id');

        // Sensores con su relación de línea
        $sensors = Sensor::with('productionLine')
            ->whereIn('production_line_id', $lineIds)
            ->orderBy('production_line_id')
            ->orderBy('id', 'desc')
            ->get();

        // Líneas para filtros en la vista
        $lines = $customer->productionLines()->select('id', 'name')->orderBy('name')->get();

        return view('customers.sensors.index', compact('customer', 'sensors', 'lines'));
    }
}

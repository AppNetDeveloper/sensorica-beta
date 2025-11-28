<?php

namespace App\Http\Controllers;

use App\Facades\UtilityFacades;
use App\Models\Modual;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class HomeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        
        if (!file_exists(storage_path() . "/installed")) {
            header('location:install');
            die;
        } else {
            // Datos básicos del dashboard
            $user = User::count();
            $modual = Modual::count();
            $role = Role::count();
            $languages = count(UtilityFacades::languages());
            
            // Datos de trabajadores (operadores) - solo si tiene permiso
            $operators = null;
            $operatorsCount = 0;
            if (auth()->user()->can('workers-show')) {
                $operators = \App\Models\Operator::orderBy('name', 'asc')->get();
                $operatorsCount = $operators->count();
            }

            // Datos de Mantenimiento - solo si tiene permiso maintenance-show
            $maintenanceStats = null;
            $customersForMaintenance = null;
            if (auth()->user()->can('maintenance-show')) {
                $customersForMaintenance = \App\Models\Customer::all();

                // Mantenimientos de los últimos 7 días
                $maintenanceLast7Days = \App\Models\Maintenance::where('created_at', '>=', now()->subDays(7))->count();

                $maintenanceStats = [
                    'pending' => $maintenanceLast7Days
                ];
            }

            // Datos de Original Orders (Pedidos) - solo si tiene permiso productionline-orders
            $originalOrdersStats = null;
            $customersForOrders = null;
            if (auth()->user()->can('productionline-orders')) {
                $allCustomers = \App\Models\Customer::all();
                $customersForOrders = $allCustomers;

                // Contar pedidos sin finalizar de todos los customers
                $totalNotFinished = \App\Models\OriginalOrder::whereNull('finished_at')->count();

                // Contar en curso (tienen production_orders con status > 0)
                $inProgress = \App\Models\OriginalOrder::whereNull('finished_at')
                    ->whereHas('productionOrders', function($q) {
                        $q->where('status', '>', 0);
                    })->count();

                // Sin iniciar = total sin finalizar - en curso
                $notStarted = $totalNotFinished - $inProgress;

                $originalOrdersStats = [
                    'total' => $totalNotFinished,
                    'in_progress' => $inProgress,
                    'not_started' => $notStarted
                ];
            }

            // Datos de Incidencias y Control de Calidad - solo si tiene permiso productionline-incidents
            $incidentsStats = null;
            $customersForIncidents = null;
            if (auth()->user()->can('productionline-incidents')) {
                $customersForIncidents = \App\Models\Customer::all();

                // QC Confirmations - últimas 24 horas
                $qcConfirmations24h = \App\Models\QcConfirmation::where('created_at', '>=', now()->subHours(24))->count();

                // Production Order Incidents - en curso (productionOrder.status == 3)
                $productionIncidentsActive = \App\Models\ProductionOrderIncident::whereHas('productionOrder', function($q) {
                    $q->where('status', 3);
                })->count();

                // Quality Issues - últimas 24 horas
                $qualityIssuesTotal = \App\Models\QualityIssue::where('created_at', '>=', now()->subHours(24))->count();

                $incidentsStats = [
                    'qc_confirmations' => $qcConfirmations24h,
                    'production_incidents' => $productionIncidentsActive,
                    'quality_issues' => $qualityIssuesTotal
                ];
            }

            // Datos de Order Organizer (grupos y máquinas) - solo si tiene permiso productionline-kanban
            $orderOrganizerStats = null;
            $customersForKanban = null;
            if (auth()->user()->can('productionline-kanban')) {
                // Obtener todos los customers con sus líneas de producción
                $customers = \App\Models\Customer::with(['productionLines.processes'])->get();
                $customersForKanban = $customers;

                $totalGroups = 0;
                $totalMachines = 0;

                foreach ($customers as $customer) {
                    $customerProductionLines = $customer->productionLines->filter(function ($line) {
                        return $line->processes->isNotEmpty();
                    });

                    // Contar grupos únicos (procesos) por customer
                    $uniqueProcesses = collect();
                    foreach ($customerProductionLines as $line) {
                        $process = $line->processes->first();
                        if ($process) {
                            $description = $process->description ?: 'Sin descripción';
                            if (!$uniqueProcesses->has($description)) {
                                $uniqueProcesses->put($description, true);
                            }
                        }
                    }

                    $totalGroups += $uniqueProcesses->count();
                    $totalMachines += $customerProductionLines->count();
                }

                $orderOrganizerStats = [
                    'groups' => $totalGroups,
                    'machines' => $totalMachines
                ];
            }

            // Datos de Órdenes Completadas Hoy y Pedidos Retrasados - permiso productionline-orders
            $completedTodayStats = null;
            $delayedOrdersStats = null;
            if (auth()->user()->can('productionline-orders')) {
                // Órdenes completadas hoy
                $completedToday = \App\Models\OriginalOrder::whereNotNull('finished_at')
                    ->whereDate('finished_at', now()->toDateString())
                    ->count();

                // Órdenes completadas ayer para comparar
                $completedYesterday = \App\Models\OriginalOrder::whereNotNull('finished_at')
                    ->whereDate('finished_at', now()->subDay()->toDateString())
                    ->count();

                // Sparkline - órdenes completadas por día (últimos 7 días)
                $completedSparkline = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $count = \App\Models\OriginalOrder::whereNotNull('finished_at')
                        ->whereDate('finished_at', $date->toDateString())
                        ->count();
                    $completedSparkline[] = $count;
                }

                $completedTodayStats = [
                    'today' => $completedToday,
                    'yesterday' => $completedYesterday,
                    'sparkline' => $completedSparkline,
                ];

                // Pedidos Retrasados - órdenes sin finalizar que han pasado su fecha de entrega
                $delayedOrders = \App\Models\OriginalOrder::whereNull('finished_at')
                    ->where(function($q) {
                        $q->where('delivery_date', '<', now())
                          ->orWhere('actual_delivery_date', '<', now());
                    })
                    ->count();

                // Pedidos retrasados de hace una semana para comparar tendencia
                $delayedLastWeek = \App\Models\OriginalOrder::whereNull('finished_at')
                    ->where('created_at', '<', now()->subDays(7))
                    ->where(function($q) {
                        $q->where('delivery_date', '<', now()->subDays(7))
                          ->orWhere('actual_delivery_date', '<', now()->subDays(7));
                    })
                    ->count();

                // Sparkline - pedidos retrasados acumulados por día (snapshot diario)
                $delayedSparkline = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    // Contar pedidos que estaban retrasados a esa fecha
                    $count = \App\Models\OriginalOrder::where(function($q) use ($date) {
                            $q->whereNull('finished_at')
                              ->orWhere('finished_at', '>', $date->endOfDay());
                        })
                        ->where('created_at', '<=', $date->endOfDay())
                        ->where(function($q) use ($date) {
                            $q->where('delivery_date', '<', $date->startOfDay())
                              ->orWhere('actual_delivery_date', '<', $date->startOfDay());
                        })
                        ->count();
                    $delayedSparkline[] = $count;
                }

                $delayedOrdersStats = [
                    'count' => $delayedOrders,
                    'last_week' => $delayedLastWeek,
                    'sparkline' => $delayedSparkline,
                ];
            }

            // Datos de Lead Time - solo si tiene permiso original-order-list
            $leadTimeStats = null;
            $customersForLeadTime = null;
            if (auth()->user()->can('original-order-list')) {
                $customersForLeadTime = \App\Models\Customer::all();

                // Calcular Lead Time promedio de los últimos 7 días de TODOS los centros
                // Lead Time 1: Pedido a Entrega (created_at -> delivery_date)
                // Lead Time 2: Pedido a Fin Producción (created_at -> finished_at)
                $tz = config('app.timezone');
                $dateStart = now()->subDays(7)->startOfDay();
                $dateEnd = now()->endOfDay();

                $orders = \App\Models\OriginalOrder::whereNotNull('finished_at')
                    ->whereBetween('finished_at', [$dateStart, $dateEnd])
                    ->select('created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
                    ->get();

                $createdToDeliveryValues = [];
                $createdToFinishedValues = [];
                $totalOrders = $orders->count();

                foreach ($orders as $order) {
                    $createdAt = $order->created_at ? $order->created_at->copy()->timezone($tz) : null;
                    $finishedAt = $order->finished_at ? $order->finished_at->copy()->timezone($tz) : null;
                    $deliveryDate = $order->actual_delivery_date
                        ? $order->actual_delivery_date->copy()->timezone($tz)->endOfDay()
                        : ($order->delivery_date ? $order->delivery_date->copy()->timezone($tz)->endOfDay() : null);

                    // Pedido a Entrega
                    if ($createdAt && $deliveryDate) {
                        $seconds = $createdAt->diffInSeconds($deliveryDate, false);
                        if ($seconds > 0) {
                            $createdToDeliveryValues[] = $seconds;
                        }
                    }

                    // Pedido a Fin Producción
                    if ($createdAt && $finishedAt) {
                        $seconds = $createdAt->diffInSeconds($finishedAt, false);
                        if ($seconds > 0) {
                            $createdToFinishedValues[] = $seconds;
                        }
                    }
                }

                // Calcular promedios en días
                $avgCreatedToDeliveryDays = count($createdToDeliveryValues) > 0
                    ? round(array_sum($createdToDeliveryValues) / count($createdToDeliveryValues) / 86400, 1)
                    : 0;
                $avgCreatedToFinishedDays = count($createdToFinishedValues) > 0
                    ? round(array_sum($createdToFinishedValues) / count($createdToFinishedValues) / 86400, 1)
                    : 0;

                $leadTimeStats = [
                    'avg_to_delivery_days' => $avgCreatedToDeliveryDays,
                    'avg_to_finished_days' => $avgCreatedToFinishedDays,
                    'total_orders' => $totalOrders,
                    'orders_with_delivery' => count($createdToDeliveryValues),
                    'orders_with_finished' => count($createdToFinishedValues),
                ];
            }

            // Datos de OEE (Production Stats) - solo si tiene permiso productionline-production-stats
            $oeeStats = null;
            $customersForOee = null;
            if (auth()->user()->can('productionline-production-stats')) {
                $customersForOee = \App\Models\Customer::all();

                // Calcular OEE promedio de los últimos 7 días de todas las líneas
                $oeeData = \App\Models\OrderStat::where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('oee')
                    ->where('oee', '>', 0)
                    ->selectRaw('AVG(oee) as avg_oee, COUNT(*) as total_records')
                    ->first();

                $avgOee = $oeeData->avg_oee ? round($oeeData->avg_oee, 1) : 0;
                $totalRecords = $oeeData->total_records ?? 0;

                $oeeStats = [
                    'avg_oee' => $avgOee,
                    'total_records' => $totalRecords
                ];
            }

            // Datos de líneas de producción y turnos - solo si tiene permiso
            $productionLines = null;
            $productionLineStats = null;
            if (auth()->user()->can('shift-show')) {
                $productionLines = \App\Models\ProductionLine::with('lastShiftHistory')
                    ->orderBy('name', 'asc')
                    ->get();
                
                // Estadísticas de líneas de producción por estado
                $productionLineStats = [
                    'total' => $productionLines->count(),
                    'active' => 0,
                    'paused' => 0,
                    'stopped' => 0,
                    'incident' => 0,
                    'inactive' => 0
                ];
                
                // Contar líneas por estado según el último historial
                foreach ($productionLines as $line) {
                    if ($line->lastShiftHistory) {
                        // Verificar si el turno fue reanudado (tiene un historial previo de pausa)
                        $wasResumed = false;
                        
                        // Obtener el historial de la línea ordenado por fecha descendente (más reciente primero)
                        $lineHistory = \App\Models\ShiftHistory::where('production_line_id', $line->id)
                            ->orderBy('created_at', 'desc')
                            ->limit(10) // Limitamos a los 10 registros más recientes para eficiencia
                            ->get();
                        
                        // Si el último registro es 'start', verificamos si hubo una pausa anterior
                        if ($line->lastShiftHistory->action == 'start' && count($lineHistory) > 1) {
                            // Recorremos el historial buscando una pausa antes del último start
                            $foundStart = false;
                            foreach ($lineHistory as $record) {
                                // Saltamos el primer registro (que es el start actual)
                                if (!$foundStart) {
                                    $foundStart = true;
                                    continue;
                                }
                                
                                // Si encontramos una pausa antes de un stop, es un turno reanudado
                                if ($record->action == 'pause') {
                                    $wasResumed = true;
                                    break;
                                }
                                
                                // Si encontramos un stop antes de una pausa, no es reanudado
                                if ($record->action == 'stop') {
                                    break;
                                }
                            }
                        }
                        
                        // Guardar el estado de reanudación en el objeto para usarlo en la vista
                        $line->wasResumed = $wasResumed;
                        
                        // Asegurarse de que el tipo y acción estén definidos para evitar "Unknown"
                        if (!isset($line->lastShiftHistory->type)) {
                            $line->lastShiftHistory->type = 'unknown';
                        }
                        
                        if (!isset($line->lastShiftHistory->action)) {
                            $line->lastShiftHistory->action = 'unknown';
                        }
                        
                        // Verificar si es un turno reanudado (start después de pause)
                        if ($line->lastShiftHistory->action == 'start') {
                            // Tanto los turnos activos normales como los reanudados cuentan como activos
                            $productionLineStats['active']++;
                        }
                        // Verificar si es un turno final_pausa (tipo stop, acción end) - línea activa
                        elseif ($line->lastShiftHistory->type === 'stop' && $line->lastShiftHistory->action === 'end') {
                            // Las líneas que han finalizado una pausa cuentan como activas
                            $productionLineStats['active']++;
                        }
                        // Verificar si es fin de turno normal (tipo shift, acción end) - línea parada
                        elseif ($line->lastShiftHistory->type === 'shift' && $line->lastShiftHistory->action === 'end') {
                            // Las líneas que han finalizado un turno cuentan como paradas
                            $productionLineStats['stopped']++;
                        }
                        // Resto de casos
                        else {
                            switch ($line->lastShiftHistory->action) {
                                case 'pause':
                                    $productionLineStats['paused']++;
                                    break;
                                case 'stop':
                                case 'end':
                                    $productionLineStats['stopped']++;
                                    break;
                                case 'incident':
                                    $productionLineStats['incident']++;
                                    break;
                                default:
                                    $productionLineStats['inactive']++;
                                    break;
                            }
                        }
                    } else {
                        $line->wasResumed = false;
                        $productionLineStats['inactive']++;
                    }
                }
            }

            return view('dashboard.homepage', compact(
                'operators', 'operatorsCount', 'productionLines', 'productionLineStats',
                'orderOrganizerStats', 'customersForKanban',
                'originalOrdersStats', 'customersForOrders',
                'incidentsStats', 'customersForIncidents',
                'maintenanceStats', 'customersForMaintenance',
                'oeeStats', 'customersForOee',
                'leadTimeStats', 'customersForLeadTime',
                'completedTodayStats', 'delayedOrdersStats'
            ));
        }
    }

    public function chart(Request $request)
    {

        if ($request->type == 'year') {

            $arrLable = [];
            $arrValue = [];

            for ($i = 0; $i < 12; $i++) {
                $arrLable[] = Carbon::now()->subMonth($i)->format('F');
                $arrValue[Carbon::now()->subMonth($i)->format('M')] = 0;
            }
            $arrLable = array_reverse($arrLable);
            $arrValue = array_reverse($arrValue);

            $t = User::select(DB::raw('DATE_FORMAT(created_at,"%b") AS user_month,COUNT(id) AS usr_cnt'))
                ->where('created_at', '>=', Carbon::now()->subDays(365)->toDateString())
                ->where('created_at', '<=', Carbon::now()->toDateString())
                ->groupBy(DB::raw('DATE_FORMAT(created_at,"%b") '))
                ->get()
                ->pluck('usr_cnt', 'user_month')
                ->toArray();

            foreach ($t as $key => $val) {
                $arrValue[$key] = $val;
            }
            $arrValue = array_values($arrValue);
            return response()->json(['lable' => $arrLable, 'value' => $arrValue], 200);
        }

        if ($request->type == 'month') {

            $arrLable = [];
            $arrValue = [];

            for ($i = 0; $i < 30; $i++) {
                $arrLable[] = date("d M", strtotime('-' . $i . ' days'));

                $arrValue[date("d-m", strtotime('-' . $i . ' days'))] = 0;
            }
            $arrLable = array_reverse($arrLable);
            $arrValue = array_reverse($arrValue);

            $t = User::select(DB::raw('DATE_FORMAT(created_at,"%d-%m") AS user_month,COUNT(id) AS usr_cnt'))
                ->where('created_at', '>=', Carbon::now()->subDays(365)->toDateString())
                ->where('created_at', '<=', Carbon::now()->toDateString())
                ->groupBy(DB::raw('DATE_FORMAT(created_at,"%d-%m") '))
                ->get()
                ->pluck('usr_cnt', 'user_month')
                ->toArray();

            foreach ($t as $key => $val) {
                $arrValue[$key] = $val;
            }
            $arrValue = array_values($arrValue);

            return response()->json(['lable' => $arrLable, 'value' => $arrValue], 200);
        }
    }

    /**
     * Obtener datos de producción para la gráfica del dashboard
     */
    public function productionChart(Request $request)
    {
        if ($request->type == 'week') {
            // Últimos 7 días
            $arrLable = [];
            $arrValue = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $arrLable[] = $date->format('d M');
                $arrValue[$date->format('Y-m-d')] = 0;
            }

            // Pedidos finalizados por día (usando finished_at de original_orders)
            $orders = \App\Models\OriginalOrder::select(DB::raw('DATE(finished_at) AS finish_date, COUNT(id) AS order_count'))
                ->whereNotNull('finished_at')
                ->where('finished_at', '>=', Carbon::now()->subDays(7)->startOfDay())
                ->where('finished_at', '<=', Carbon::now()->endOfDay())
                ->groupBy(DB::raw('DATE(finished_at)'))
                ->get()
                ->pluck('order_count', 'finish_date')
                ->toArray();

            foreach ($orders as $key => $val) {
                if (isset($arrValue[$key])) {
                    $arrValue[$key] = $val;
                }
            }
            $arrValue = array_values($arrValue);

            return response()->json(['lable' => $arrLable, 'value' => $arrValue], 200);
        }

        if ($request->type == 'month') {
            // Últimos 30 días
            $arrLable = [];
            $arrValue = [];

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $arrLable[] = $date->format('d M');
                $arrValue[$date->format('Y-m-d')] = 0;
            }

            // Pedidos finalizados por día
            $orders = \App\Models\OriginalOrder::select(DB::raw('DATE(finished_at) AS finish_date, COUNT(id) AS order_count'))
                ->whereNotNull('finished_at')
                ->where('finished_at', '>=', Carbon::now()->subDays(30)->startOfDay())
                ->where('finished_at', '<=', Carbon::now()->endOfDay())
                ->groupBy(DB::raw('DATE(finished_at)'))
                ->get()
                ->pluck('order_count', 'finish_date')
                ->toArray();

            foreach ($orders as $key => $val) {
                if (isset($arrValue[$key])) {
                    $arrValue[$key] = $val;
                }
            }
            $arrValue = array_values($arrValue);

            return response()->json(['lable' => $arrLable, 'value' => $arrValue], 200);
        }

        return response()->json(['lable' => [], 'value' => []], 200);
    }

    /**
     * API para obtener datos de KPIs en tiempo real (auto-refresh)
     */
    public function getKpiData()
    {
        $data = [];

        // Mantenimiento - últimos 7 días
        if (auth()->user()->can('maintenance-show')) {
            $maintenanceLast7Days = \App\Models\Maintenance::where('created_at', '>=', now()->subDays(7))->count();
            $maintenancePrev7Days = \App\Models\Maintenance::where('created_at', '>=', now()->subDays(14))
                ->where('created_at', '<', now()->subDays(7))->count();

            // Sparkline últimos 7 días
            $maintenanceSparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = \App\Models\Maintenance::whereDate('created_at', $date->toDateString())->count();
                $maintenanceSparkline[] = $count;
            }

            // Contar mantenimientos no finalizados (sin end_datetime)
            $maintenanceNotClosed = \App\Models\Maintenance::whereNull('end_datetime')->count();

            $data['maintenance'] = [
                'value' => $maintenanceLast7Days,
                'previous' => $maintenancePrev7Days,
                'trend' => $maintenanceLast7Days > $maintenancePrev7Days ? 'up' : ($maintenanceLast7Days < $maintenancePrev7Days ? 'down' : 'same'),
                'sparkline' => $maintenanceSparkline,
                'isAlert' => $maintenanceNotClosed > 0, // Alerta si hay mantenimientos sin cerrar
                'notClosed' => $maintenanceNotClosed
            ];
        }

        // Trabajadores
        if (auth()->user()->can('workers-show')) {
            $operatorsCount = \App\Models\Operator::count();
            $data['workers'] = [
                'value' => $operatorsCount,
                'previous' => $operatorsCount,
                'trend' => 'same',
                'sparkline' => array_fill(0, 7, $operatorsCount)
            ];
        }

        // Order Organizer
        if (auth()->user()->can('productionline-kanban')) {
            $customers = \App\Models\Customer::with(['productionLines.processes'])->get();
            $totalGroups = 0;
            $totalMachines = 0;

            foreach ($customers as $customer) {
                $customerProductionLines = $customer->productionLines->filter(function ($line) {
                    return $line->processes->isNotEmpty();
                });
                $uniqueProcesses = collect();
                foreach ($customerProductionLines as $line) {
                    $process = $line->processes->first();
                    if ($process) {
                        $description = $process->description ?: 'Sin descripción';
                        if (!$uniqueProcesses->has($description)) {
                            $uniqueProcesses->put($description, true);
                        }
                    }
                }
                $totalGroups += $uniqueProcesses->count();
                $totalMachines += $customerProductionLines->count();
            }

            $data['orderOrganizer'] = [
                'groups' => $totalGroups,
                'machines' => $totalMachines,
                'trend' => 'same',
                'sparkline' => array_fill(0, 7, $totalMachines)
            ];
        }

        // Pedidos pendientes
        if (auth()->user()->can('productionline-orders')) {
            $totalNotFinished = \App\Models\OriginalOrder::whereNull('finished_at')->count();
            $inProgress = \App\Models\OriginalOrder::whereNull('finished_at')
                ->whereHas('productionOrders', function($q) {
                    $q->where('status', '>', 0);
                })->count();
            $notStarted = $totalNotFinished - $inProgress;

            // Sparkline - pedidos finalizados por día
            $ordersSparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = \App\Models\OriginalOrder::whereDate('finished_at', $date->toDateString())->count();
                $ordersSparkline[] = $count;
            }

            // Comparar con semana anterior
            $prevWeekPending = \App\Models\OriginalOrder::whereNull('finished_at')
                ->where('created_at', '<', now()->subDays(7))->count();

            $data['pendingOrders'] = [
                'total' => $totalNotFinished,
                'inProgress' => $inProgress,
                'notStarted' => $notStarted,
                'previous' => $prevWeekPending,
                'trend' => $totalNotFinished > $prevWeekPending ? 'up' : ($totalNotFinished < $prevWeekPending ? 'down' : 'same'),
                'sparkline' => $ordersSparkline
            ];
        }

        // Incidencias
        if (auth()->user()->can('productionline-incidents')) {
            $qcConfirmations24h = \App\Models\QcConfirmation::where('created_at', '>=', now()->subHours(24))->count();
            $qcConfirmationsPrev24h = \App\Models\QcConfirmation::where('created_at', '>=', now()->subHours(48))
                ->where('created_at', '<', now()->subHours(24))->count();

            $productionIncidentsActive = \App\Models\ProductionOrderIncident::whereHas('productionOrder', function($q) {
                $q->where('status', 3);
            })->count();

            $qualityIssues24h = \App\Models\QualityIssue::where('created_at', '>=', now()->subHours(24))->count();
            $qualityIssuesPrev24h = \App\Models\QualityIssue::where('created_at', '>=', now()->subHours(48))
                ->where('created_at', '<', now()->subHours(24))->count();

            // Sparklines
            $qcSparkline = [];
            $incidentsSparkline = [];
            $qualitySparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $qcSparkline[] = \App\Models\QcConfirmation::whereDate('created_at', $date->toDateString())->count();
                $incidentsSparkline[] = \App\Models\ProductionOrderIncident::whereDate('created_at', $date->toDateString())->count();
                $qualitySparkline[] = \App\Models\QualityIssue::whereDate('created_at', $date->toDateString())->count();
            }

            // QC Confirmations del día actual
            $qcConfirmationsToday = \App\Models\QcConfirmation::whereDate('created_at', now()->toDateString())->count();

            $data['qcConfirmations'] = [
                'value' => $qcConfirmations24h,
                'previous' => $qcConfirmationsPrev24h,
                'trend' => $qcConfirmations24h > $qcConfirmationsPrev24h ? 'up' : ($qcConfirmations24h < $qcConfirmationsPrev24h ? 'down' : 'same'),
                'sparkline' => $qcSparkline,
                'isAlert' => $qcConfirmationsToday == 0, // Alerta si hay 0 confirmaciones hoy (mal)
                'todayCount' => $qcConfirmationsToday
            ];

            $data['productionIncidents'] = [
                'value' => $productionIncidentsActive,
                'isAlert' => $productionIncidentsActive > 0,
                'sparkline' => $incidentsSparkline
            ];

            // Quality Issues del día actual
            $qualityIssuesToday = \App\Models\QualityIssue::whereDate('created_at', now()->toDateString())->count();

            $data['qualityIssues'] = [
                'value' => $qualityIssues24h,
                'previous' => $qualityIssuesPrev24h,
                'trend' => $qualityIssues24h > $qualityIssuesPrev24h ? 'up' : ($qualityIssues24h < $qualityIssuesPrev24h ? 'down' : 'same'),
                'sparkline' => $qualitySparkline,
                'isAlert' => $qualityIssuesToday > 0, // Alerta si hay incidencias de calidad hoy
                'todayCount' => $qualityIssuesToday
            ];
        }

        // Órdenes Completadas Hoy y Pedidos Retrasados
        if (auth()->user()->can('productionline-orders')) {
            // Órdenes completadas hoy
            $completedToday = \App\Models\OriginalOrder::whereNotNull('finished_at')
                ->whereDate('finished_at', now()->toDateString())
                ->count();

            $completedYesterday = \App\Models\OriginalOrder::whereNotNull('finished_at')
                ->whereDate('finished_at', now()->subDay()->toDateString())
                ->count();

            // Sparkline
            $completedSparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = \App\Models\OriginalOrder::whereNotNull('finished_at')
                    ->whereDate('finished_at', $date->toDateString())
                    ->count();
                $completedSparkline[] = $count;
            }

            $data['completedToday'] = [
                'value' => $completedToday,
                'previous' => $completedYesterday,
                'trend' => $completedToday > $completedYesterday ? 'up' : ($completedToday < $completedYesterday ? 'down' : 'same'),
                'sparkline' => $completedSparkline,
                'isAlert' => false
            ];

            // Pedidos Retrasados
            $delayedOrders = \App\Models\OriginalOrder::whereNull('finished_at')
                ->where(function($q) {
                    $q->where('delivery_date', '<', now())
                      ->orWhere('actual_delivery_date', '<', now());
                })
                ->count();

            $delayedLastWeek = \App\Models\OriginalOrder::whereNull('finished_at')
                ->where('created_at', '<', now()->subDays(7))
                ->where(function($q) {
                    $q->where('delivery_date', '<', now()->subDays(7))
                      ->orWhere('actual_delivery_date', '<', now()->subDays(7));
                })
                ->count();

            // Sparkline para retrasados
            $delayedSparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = \App\Models\OriginalOrder::where(function($q) use ($date) {
                        $q->whereNull('finished_at')
                          ->orWhere('finished_at', '>', $date->endOfDay());
                    })
                    ->where('created_at', '<=', $date->endOfDay())
                    ->where(function($q) use ($date) {
                        $q->where('delivery_date', '<', $date->startOfDay())
                          ->orWhere('actual_delivery_date', '<', $date->startOfDay());
                    })
                    ->count();
                $delayedSparkline[] = $count;
            }

            // Para retrasados, menor es mejor
            $data['delayedOrders'] = [
                'value' => $delayedOrders,
                'previous' => $delayedLastWeek,
                'trend' => $delayedOrders < $delayedLastWeek ? 'up' : ($delayedOrders > $delayedLastWeek ? 'down' : 'same'),
                'sparkline' => $delayedSparkline,
                'isAlert' => $delayedOrders > 0 // Alerta si hay pedidos retrasados
            ];
        }

        // Lead Time Stats
        if (auth()->user()->can('original-order-list')) {
            $tz = config('app.timezone');
            $dateStart = now()->subDays(7)->startOfDay();
            $dateEnd = now()->endOfDay();

            $orders = \App\Models\OriginalOrder::whereNotNull('finished_at')
                ->whereBetween('finished_at', [$dateStart, $dateEnd])
                ->select('created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
                ->get();

            $createdToDeliveryValues = [];
            $createdToFinishedValues = [];

            foreach ($orders as $order) {
                $createdAt = $order->created_at ? $order->created_at->copy()->timezone($tz) : null;
                $finishedAt = $order->finished_at ? $order->finished_at->copy()->timezone($tz) : null;
                $deliveryDate = $order->actual_delivery_date
                    ? $order->actual_delivery_date->copy()->timezone($tz)->endOfDay()
                    : ($order->delivery_date ? $order->delivery_date->copy()->timezone($tz)->endOfDay() : null);

                if ($createdAt && $deliveryDate) {
                    $seconds = $createdAt->diffInSeconds($deliveryDate, false);
                    if ($seconds > 0) {
                        $createdToDeliveryValues[] = $seconds;
                    }
                }

                if ($createdAt && $finishedAt) {
                    $seconds = $createdAt->diffInSeconds($finishedAt, false);
                    if ($seconds > 0) {
                        $createdToFinishedValues[] = $seconds;
                    }
                }
            }

            $avgToDeliveryDays = count($createdToDeliveryValues) > 0
                ? round(array_sum($createdToDeliveryValues) / count($createdToDeliveryValues) / 86400, 1)
                : 0;
            $avgToFinishedDays = count($createdToFinishedValues) > 0
                ? round(array_sum($createdToFinishedValues) / count($createdToFinishedValues) / 86400, 1)
                : 0;

            // Semana anterior para comparar
            $prevDateStart = now()->subDays(14)->startOfDay();
            $prevDateEnd = now()->subDays(7)->endOfDay();

            $prevOrders = \App\Models\OriginalOrder::whereNotNull('finished_at')
                ->whereBetween('finished_at', [$prevDateStart, $prevDateEnd])
                ->select('created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
                ->get();

            $prevToDeliveryValues = [];
            $prevToFinishedValues = [];

            foreach ($prevOrders as $order) {
                $createdAt = $order->created_at ? $order->created_at->copy()->timezone($tz) : null;
                $finishedAt = $order->finished_at ? $order->finished_at->copy()->timezone($tz) : null;
                $deliveryDate = $order->actual_delivery_date
                    ? $order->actual_delivery_date->copy()->timezone($tz)->endOfDay()
                    : ($order->delivery_date ? $order->delivery_date->copy()->timezone($tz)->endOfDay() : null);

                if ($createdAt && $deliveryDate) {
                    $seconds = $createdAt->diffInSeconds($deliveryDate, false);
                    if ($seconds > 0) {
                        $prevToDeliveryValues[] = $seconds;
                    }
                }

                if ($createdAt && $finishedAt) {
                    $seconds = $createdAt->diffInSeconds($finishedAt, false);
                    if ($seconds > 0) {
                        $prevToFinishedValues[] = $seconds;
                    }
                }
            }

            $prevAvgToDeliveryDays = count($prevToDeliveryValues) > 0
                ? round(array_sum($prevToDeliveryValues) / count($prevToDeliveryValues) / 86400, 1)
                : 0;
            $prevAvgToFinishedDays = count($prevToFinishedValues) > 0
                ? round(array_sum($prevToFinishedValues) / count($prevToFinishedValues) / 86400, 1)
                : 0;

            // Sparklines - promedio diario
            $deliverySparkline = [];
            $finishedSparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayOrders = \App\Models\OriginalOrder::whereNotNull('finished_at')
                    ->whereDate('finished_at', $date->toDateString())
                    ->select('created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
                    ->get();

                $dayDeliveryVals = [];
                $dayFinishedVals = [];
                foreach ($dayOrders as $order) {
                    $createdAt = $order->created_at ? $order->created_at->copy()->timezone($tz) : null;
                    $finishedAt = $order->finished_at ? $order->finished_at->copy()->timezone($tz) : null;
                    $deliveryDate = $order->actual_delivery_date
                        ? $order->actual_delivery_date->copy()->timezone($tz)->endOfDay()
                        : ($order->delivery_date ? $order->delivery_date->copy()->timezone($tz)->endOfDay() : null);

                    if ($createdAt && $deliveryDate) {
                        $seconds = $createdAt->diffInSeconds($deliveryDate, false);
                        if ($seconds > 0) {
                            $dayDeliveryVals[] = $seconds / 86400;
                        }
                    }
                    if ($createdAt && $finishedAt) {
                        $seconds = $createdAt->diffInSeconds($finishedAt, false);
                        if ($seconds > 0) {
                            $dayFinishedVals[] = $seconds / 86400;
                        }
                    }
                }

                $deliverySparkline[] = count($dayDeliveryVals) > 0 ? round(array_sum($dayDeliveryVals) / count($dayDeliveryVals), 1) : 0;
                $finishedSparkline[] = count($dayFinishedVals) > 0 ? round(array_sum($dayFinishedVals) / count($dayFinishedVals), 1) : 0;
            }

            // Para Lead Time, menor es mejor, así que invertimos la tendencia
            $data['leadTimeToDelivery'] = [
                'value' => $avgToDeliveryDays,
                'previous' => $prevAvgToDeliveryDays,
                'trend' => $avgToDeliveryDays < $prevAvgToDeliveryDays ? 'up' : ($avgToDeliveryDays > $prevAvgToDeliveryDays ? 'down' : 'same'),
                'sparkline' => $deliverySparkline,
                'ordersCount' => count($createdToDeliveryValues),
                'isAlert' => $avgToDeliveryDays > 10 // Alerta si más de 10 días
            ];

            $data['leadTimeToFinished'] = [
                'value' => $avgToFinishedDays,
                'previous' => $prevAvgToFinishedDays,
                'trend' => $avgToFinishedDays < $prevAvgToFinishedDays ? 'up' : ($avgToFinishedDays > $prevAvgToFinishedDays ? 'down' : 'same'),
                'sparkline' => $finishedSparkline,
                'ordersCount' => count($createdToFinishedValues),
                'isAlert' => $avgToFinishedDays > 7 // Alerta si más de 7 días
            ];
        }

        // OEE (Production Stats)
        if (auth()->user()->can('productionline-production-stats')) {
            // OEE promedio últimos 7 días
            $oeeData = \App\Models\OrderStat::where('created_at', '>=', now()->subDays(7))
                ->whereNotNull('oee')
                ->where('oee', '>', 0)
                ->selectRaw('AVG(oee) as avg_oee, COUNT(*) as total_records')
                ->first();

            $avgOee = $oeeData->avg_oee ? round($oeeData->avg_oee, 1) : 0;
            $totalRecords = $oeeData->total_records ?? 0;

            // OEE de la semana anterior para comparar
            $oeePrevData = \App\Models\OrderStat::where('created_at', '>=', now()->subDays(14))
                ->where('created_at', '<', now()->subDays(7))
                ->whereNotNull('oee')
                ->where('oee', '>', 0)
                ->selectRaw('AVG(oee) as avg_oee')
                ->first();
            $prevOee = $oeePrevData->avg_oee ? round($oeePrevData->avg_oee, 1) : 0;

            // Sparkline - OEE promedio por día
            $oeeSparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayOee = \App\Models\OrderStat::whereDate('created_at', $date->toDateString())
                    ->whereNotNull('oee')
                    ->where('oee', '>', 0)
                    ->avg('oee');
                $oeeSparkline[] = $dayOee ? round($dayOee, 1) : 0;
            }

            $data['oeeStats'] = [
                'value' => $avgOee,
                'previous' => $prevOee,
                'trend' => $avgOee > $prevOee ? 'up' : ($avgOee < $prevOee ? 'down' : 'same'),
                'sparkline' => $oeeSparkline,
                'totalRecords' => $totalRecords,
                'isAlert' => $avgOee < 60 // Alerta si OEE es menor al 60%
            ];
        }

        // Líneas de producción
        if (auth()->user()->can('shift-show')) {
            $productionLines = \App\Models\ProductionLine::with('lastShiftHistory')->get();
            $stats = ['total' => $productionLines->count(), 'active' => 0, 'paused' => 0, 'stopped' => 0];

            foreach ($productionLines as $line) {
                if ($line->lastShiftHistory) {
                    if ($line->lastShiftHistory->action == 'start') {
                        $stats['active']++;
                    } elseif ($line->lastShiftHistory->type === 'stop' && $line->lastShiftHistory->action === 'end') {
                        $stats['active']++;
                    } elseif ($line->lastShiftHistory->type === 'shift' && $line->lastShiftHistory->action === 'end') {
                        $stats['stopped']++;
                    } elseif ($line->lastShiftHistory->action == 'pause') {
                        $stats['paused']++;
                    } elseif (in_array($line->lastShiftHistory->action, ['stop', 'end'])) {
                        $stats['stopped']++;
                    }
                }
            }

            $data['productionLines'] = [
                'total' => $stats['total'],
                'active' => $stats['active'],
                'pausedStopped' => $stats['paused'] + $stats['stopped'],
                'sparkline' => array_fill(0, 7, $stats['active'])
            ];
        }

        return response()->json($data);
    }
}

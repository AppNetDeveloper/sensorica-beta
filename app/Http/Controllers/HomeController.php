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
                'maintenanceStats', 'customersForMaintenance'
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

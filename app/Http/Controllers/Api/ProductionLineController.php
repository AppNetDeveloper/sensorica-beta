<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductionLine;
use App\Models\ShiftHistory;
use App\Models\LineAvailability;
use App\Models\ShiftList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionLineController extends Controller
{
    /**
     * Get the current status of all production lines for a specific customer
     * 
     * @param \Illuminate\Http\Request $request
     * @param int|null $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatuses(Request $request, $customerId = null)
    {
        // Si el ID no viene en la URL (GET), lo tomamos del body (POST)
        if (!$customerId) {
            $customerId = $request->input('customerId');
        }
        
        // Buscar el cliente por su ID
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado!'], 404);
        }
        
        // Obtener la última entrada de shift_history para cada línea de producción
        $latestShiftHistories = ShiftHistory::select(
                'production_line_id',
                'type',
                'action',
                'created_at',
                DB::raw('MAX(created_at) as latest_date')
            )
            ->groupBy('production_line_id')
            ->get()
            ->keyBy('production_line_id');
        
        // Obtener las líneas de producción asociadas al cliente
        $productionLines = ProductionLine::where('customer_id', $customer->id)->get();
        
        // Obtener día de la semana actual (1-7, donde 1 es lunes y 7 es domingo)
        $currentDayOfWeek = now()->dayOfWeek;
        if ($currentDayOfWeek == 0) {
            $currentDayOfWeek = 7; // Convertir domingo (0) a 7 para coincidir con el formato de la BD
        }
        
        // Obtener hora actual
        $currentTime = now();
        
        // Array para almacenar el estado de planificación de cada línea
        $lineScheduledStatuses = [];
        
        // Para cada línea, determinar su estado de planificación
        foreach ($productionLines as $line) {
            // Buscar disponibilidades para esta línea en el día actual
            $availabilities = LineAvailability::where('production_line_id', $line->id)
                ->where('day_of_week', $currentDayOfWeek)
                ->where('active', true)
                ->get();
            
            if ($availabilities->isEmpty()) {
                // Si no hay registros para este día, la línea no está planificada
                $lineScheduledStatuses[$line->id] = 'unscheduled';
                continue;
            }
            
            // Verificar si alguno de los turnos asociados a las disponibilidades está activo ahora
            $inShift = false;
            foreach ($availabilities as $availability) {
                $shift = ShiftList::find($availability->shift_list_id);
                if (!$shift) continue;
                
                // Determinar si estamos dentro de este turno
                $startTime = null;
                $endTime = null;
                
                if (isset($shift->start_time) && isset($shift->end_time)) {
                    $startTime = Carbon::parse($shift->start_time);
                    $endTime = Carbon::parse($shift->end_time);
                } else if (isset($shift->start) && isset($shift->end)) {
                    $startTime = Carbon::parse($shift->start);
                    $endTime = Carbon::parse($shift->end);
                }
                
                if ($startTime && $endTime) {
                    // Manejar turnos que cruzan la medianoche
                    if ($startTime->greaterThan($endTime)) {
                        if ($currentTime->greaterThanOrEqualTo($startTime) || $currentTime->lessThan($endTime)) {
                            $inShift = true;
                            break;
                        }
                    } else {
                        if ($currentTime->greaterThanOrEqualTo($startTime) && $currentTime->lessThan($endTime)) {
                            $inShift = true;
                            break;
                        }
                    }
                }
            }
            
            // Asignar el estado según si estamos en turno o no
            if ($inShift) {
                $lineScheduledStatuses[$line->id] = 'scheduled'; // Planificada
            } else {
                $lineScheduledStatuses[$line->id] = 'off_shift'; // Fuera de turno
            }
        }
        
        $statuses = [];
        
        foreach ($productionLines as $line) {
            // Verificar si hay un registro de shift_history para esta línea
            if (isset($latestShiftHistories[$line->id])) {
                $latestShift = ShiftHistory::where('production_line_id', $line->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($latestShift) {
                    // Obtener el nombre del operador si existe
                    $operatorName = null;
                    if ($latestShift->operator_id && $latestShift->operator) {
                        $operatorName = $latestShift->operator->name;
                    }
                    
                    // Obtener el estado de planificación calculado previamente
                    $scheduledStatus = $lineScheduledStatuses[$line->id] ?? 'off_shift';

                    $statuses[] = [
                        'production_line_id' => $line->id,
                        'production_line_name' => $line->name,
                        'type' => $latestShift->type,
                        'action' => $latestShift->action,
                        'operator_id' => $latestShift->operator_id,
                        'operator_name' => $operatorName,
                        'created_at' => $latestShift->created_at->format('Y-m-d H:i:s'),
                        'scheduled_status' => $scheduledStatus // Nuevo campo
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'statuses' => $statuses
        ]);
    }

    /**
     * Get schedule status for a production line by its public token.
     * Returns whether we are currently within any defined shift (in_shift)
     * and whether the line is scheduled for the current shift window (scheduled).
     * status values:
     *  - 'scheduled'  -> in shift and line planned for at least one active shift
     *  - 'unscheduled'-> in shift but line not planned for the current shift
     *  - 'off_shift'  -> currently outside of all shift windows
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScheduleStatusByToken($token)
    {
        // Find line by token
        $line = ProductionLine::where('token', $token)->first();
        if (!$line) {
            return response()->json(['success' => false, 'error' => 'Línea no encontrada'], 404);
        }

        // Current day of week: convert 0 (Sun) to 7 to match DB if needed
        $currentDayOfWeek = now()->dayOfWeek;
        if ($currentDayOfWeek == 0) {
            $currentDayOfWeek = 7;
        }

        $currentTime = now();

        // Get active availabilities for the line for current day
        $availabilities = LineAvailability::where('production_line_id', $line->id)
            ->where('day_of_week', $currentDayOfWeek)
            ->where('active', true)
            ->get();

        // Get all shifts for the line (some implementations may keep shifts per line)
        // We will consider a shift "active window" if current time is inside its [start, end) range,
        // handling cross-midnight ranges as in getStatuses().
        $shifts = ShiftList::where('production_line_id', $line->id)->get();

        $inShift = false;
        $scheduled = false;

        foreach ($shifts as $shift) {
            $startTime = null;
            $endTime = null;
            if (isset($shift->start_time) && isset($shift->end_time)) {
                $startTime = Carbon::parse($shift->start_time);
                $endTime = Carbon::parse($shift->end_time);
            } elseif (isset($shift->start) && isset($shift->end)) {
                $startTime = Carbon::parse($shift->start);
                $endTime = Carbon::parse($shift->end);
            }

            if (!$startTime || !$endTime) {
                continue;
            }

            $isNowInThisShift = false;
            if ($startTime->greaterThan($endTime)) {
                // Cross-midnight
                if ($currentTime->greaterThanOrEqualTo($startTime) || $currentTime->lessThan($endTime)) {
                    $isNowInThisShift = true;
                }
            } else {
                if ($currentTime->greaterThanOrEqualTo($startTime) && $currentTime->lessThan($endTime)) {
                    $isNowInThisShift = true;
                }
            }

            if ($isNowInThisShift) {
                $inShift = true;
                // Check if this shift is planned for today for this line
                $isPlanned = $availabilities->first(function ($a) use ($shift) {
                    return (int)$a->shift_list_id === (int)$shift->id;
                }) !== null;
                if ($isPlanned) {
                    $scheduled = true;
                    break; // already scheduled in current shift
                }
                // else keep checking other overlapping shifts if any
            }
        }

        $status = 'off_shift';
        if ($inShift && $scheduled) {
            $status = 'scheduled';
        } elseif ($inShift && !$scheduled) {
            $status = 'unscheduled';
        }

        return response()->json([
            'success' => true,
            'in_shift' => $inShift,
            'scheduled' => $scheduled,
            'status' => $status,
        ]);
    }
}

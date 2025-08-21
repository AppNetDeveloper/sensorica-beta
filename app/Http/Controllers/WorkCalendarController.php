<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkCalendarController extends Controller
{
    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->middleware('permission:workcalendar-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:workcalendar-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:workcalendar-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:workcalendar-delete', ['only' => ['destroy']]);
    }

    /**
     * Muestra el calendario laboral de un cliente específico.
     *
     * @param  int  $customerId
     * @return \Illuminate\Http\Response
     */
    public function index($customerId)
    {
        // Verificar que el cliente existe
        $customer = Customer::findOrFail($customerId);
        
        // Obtener el mes y año actual o los proporcionados en la solicitud
        $month = (int) request('month', Carbon::now()->month);
        $year = (int) request('year', Carbon::now()->year);
        
        // Validar que el mes esté entre 1 y 12
        if ($month < 1 || $month > 12) {
            $month = Carbon::now()->month;
        }
        
        // Crear fechas de inicio y fin del mes
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Obtener todos los días del calendario para el mes seleccionado
        // Importante: clavear por fecha en formato 'Y-m-d' para que coincida con $dateString en la vista
        $calendarDays = WorkCalendar::forCustomer($customerId)
            ->betweenDates($startDate, $endDate)
            ->orderBy('calendar_date')
            ->get()
            ->mapWithKeys(function ($item) {
                $date = $item->calendar_date instanceof \Carbon\Carbon
                    ? $item->calendar_date->format('Y-m-d')
                    : \Carbon\Carbon::parse($item->calendar_date)->format('Y-m-d');
                return [$date => $item];
            });
        
        // Usar únicamente los días existentes en BD; no generar valores por defecto
        $daysInMonth = $calendarDays;
        
        // Obtener meses para navegación
        $previousMonth = Carbon::createFromDate($year, $month, 1)->subMonth();
        $nextMonth = Carbon::createFromDate($year, $month, 1)->addMonth();
        
        return view('work_calendars.index', compact(
            'customer',
            'daysInMonth',
            'month',
            'year',
            'previousMonth',
            'nextMonth'
        ));
    }

    /**
     * Muestra el formulario para crear un nuevo día en el calendario.
     *
     * @param  int  $customerId
     * @return \Illuminate\Http\Response
     */
    public function create($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $date = request('date', Carbon::now()->format('Y-m-d'));
        
        // Tipos de días disponibles
        $dayTypes = [
            'holiday' => __('Holiday'),
            'maintenance' => __('Maintenance'),
            'vacation' => __('Vacation'),
            'workday' => __('Working Day'),
            'weekend' => __('Weekend')
        ];
        
        return view('work_calendars.create', compact('customer', 'date', 'dayTypes'));
    }

    /**
     * Almacena un nuevo día en el calendario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $customerId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $customerId)
    {
        $request->validate([
            'calendar_date' => 'required|date',
            'name' => 'nullable|string|max:255',
            'type' => 'required|string|max:50',
            'is_working_day' => 'required|boolean',
            'description' => 'nullable|string'
        ]);
        
        // Verificar si ya existe un registro para esta fecha y cliente
        $existingDay = WorkCalendar::where('customer_id', $customerId)
            ->where('calendar_date', $request->calendar_date)
            ->first();
            
        if ($existingDay) {
            // Actualizar el registro existente
            $existingDay->update($request->all());
            $message = __('Calendar day updated successfully');
            $model = $existingDay;
        } else {
            // Crear un nuevo registro
            $model = WorkCalendar::create(array_merge(
                $request->all(),
                ['customer_id' => $customerId]
            ));
            $message = __('Calendar day created successfully');
        }
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $model
            ]);
        }
        
        return redirect()->route('customers.work-calendars.index', $customerId)
            ->with('success', $message);
    }

    /**
     * Muestra el formulario para editar un día del calendario.
     *
     * @param  int  $customerId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $calendarDay = WorkCalendar::where('customer_id', $customerId)
            ->findOrFail($id);
        
        // Tipos de días disponibles
        $dayTypes = [
            'holiday' => __('Holiday'),
            'maintenance' => __('Maintenance'),
            'vacation' => __('Vacation'),
            'workday' => __('Working Day'),
            'weekend' => __('Weekend')
        ];
        
        // Pasar ambas variables por compatibilidad con la vista
        return view('work_calendars.edit', [
            'customer' => $customer,
            'calendarDay' => $calendarDay,
            'calendar' => $calendarDay,
            'dayTypes' => $dayTypes,
        ]);
    }

    /**
     * Actualiza un día del calendario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $customerId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $customerId, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'required|string|max:50',
            'is_working_day' => 'required|boolean',
            'description' => 'nullable|string'
        ]);
        
        $calendarDay = WorkCalendar::where('customer_id', $customerId)
            ->findOrFail($id);
        
        $calendarDay->update($request->all());
        $message = __('Calendar day updated successfully');
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $calendarDay
            ]);
        }
        
        return redirect()->route('customers.work-calendars.index', $customerId)
            ->with('success', $message);
    }

    /**
     * Elimina un día del calendario.
     *
     * @param  int  $customerId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($customerId, $id)
    {
        $calendarDay = WorkCalendar::where('customer_id', $customerId)
            ->findOrFail($id);
        
        $calendarDay->delete();
        $message = __('Calendar day deleted successfully');
        
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }
        
        return redirect()->route('customers.work-calendars.index', $customerId)
            ->with('success', $message);
    }
    
    /**
     * Actualiza múltiples días del calendario mediante AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $customerId
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdate(Request $request, $customerId)
    {
        $request->validate([
            'days' => 'required|array',
            'days.*.date' => 'required|date',
            'days.*.is_working_day' => 'required|boolean',
            'days.*.type' => 'required|string'
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($request->days as $day) {
                WorkCalendar::updateOrCreate(
                    [
                        'customer_id' => $customerId,
                        'calendar_date' => $day['date']
                    ],
                    [
                        'name' => $day['name'] ?? null,
                        'type' => $day['type'],
                        'is_working_day' => $day['is_working_day'],
                        'description' => $day['description'] ?? null
                    ]
                );
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => __('Calendar updated successfully')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

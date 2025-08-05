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
        $month = request('month', Carbon::now()->month);
        $year = request('year', Carbon::now()->year);
        
        // Crear fechas de inicio y fin del mes
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Obtener todos los días del calendario para el mes seleccionado
        $calendarDays = WorkCalendar::forCustomer($customerId)
            ->betweenDates($startDate, $endDate)
            ->orderBy('calendar_date')
            ->get()
            ->keyBy('calendar_date');
        
        // Crear array con todos los días del mes
        $daysInMonth = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Si existe en la BD, usar esos datos, sino crear un día por defecto
            if ($calendarDays->has($dateString)) {
                $daysInMonth[$dateString] = $calendarDays->get($dateString);
            } else {
                // Por defecto, los fines de semana no son laborables
                $isWeekend = $currentDate->isWeekend();
                $daysInMonth[$dateString] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day' => $currentDate->day,
                    'is_working_day' => !$isWeekend,
                    'type' => $isWeekend ? 'weekend' : 'workday',
                    'name' => null,
                    'description' => null,
                    'exists_in_db' => false
                ];
            }
            
            $currentDate->addDay();
        }
        
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
            'weekend' => __('Weekend'),
            'special' => __('Special')
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
        } else {
            // Crear un nuevo registro
            WorkCalendar::create(array_merge(
                $request->all(),
                ['customer_id' => $customerId]
            ));
            $message = __('Calendar day created successfully');
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
            'weekend' => __('Weekend'),
            'special' => __('Special')
        ];
        
        return view('work_calendars.edit', compact('customer', 'calendarDay', 'dayTypes'));
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
        
        return redirect()->route('customers.work-calendars.index', $customerId)
            ->with('success', __('Calendar day updated successfully'));
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
        
        return redirect()->route('customers.work-calendars.index', $customerId)
            ->with('success', __('Calendar day deleted successfully'));
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

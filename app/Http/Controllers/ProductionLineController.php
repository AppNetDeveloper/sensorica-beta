<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str; // Importa la clase Str desde Illuminate\Support
use App\Models\Customer; // Importa la clase Customer aquí

class ProductionLineController extends Controller
{
    public function index($customer_id)
    {
        return view('productionlines.index', compact('customer_id'));
    }

    public function getProductionLines(Request $request, $customer_id, DataTables $dataTables)
    {
        $query = ProductionLine::withCount('processes')
            ->where('customer_id', $customer_id)
            ->select(['id', 'name', 'token', 'customer_id']);
    
        return $dataTables->eloquent($query)
            ->addColumn('action', function ($line) {
                return $this->getActionButtons($line);
            })
            ->addColumn('processes_count', function($line) {
                return $line->processes_count;
            })
            ->editColumn('id', function($line) {
                return $line->id;
            })
            ->editColumn('name', function($line) {
                return $line->name;
            })
            ->editColumn('token', function($line) {
                return $line->token;
            })
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('token', function($query, $keyword) {
                $query->where('token', 'like', "%{$keyword}%");
            })
            ->filterColumn('id', function($query, $keyword) {
                $query->where('id', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action']) // Permitir HTML en la columna 'action'
            ->make(true);
    }
    
    /**
     * Genera el HTML para los botones de acción de cada línea de producción usando traducciones.
     *
     * @param  \App\Models\ProductionLine  $line
     * @return string
     */
    private function getActionButtons($line)
{
    // Generación de URLs
    $editUrl        = route('productionlines.edit', $line->id);
    $sensorListUrl  = route('sensors.index', $line->id);
    $deleteUrl      = route('productionlines.destroy', $line->id);
    $ordersUrl      = "/production-order-kanban?token={$line->token}";
    $processesUrl   = route('productionlines.processes.index', $line->id);
    $liveViewUrl    = "/live-production/live.html?token={$line->token}";
    $liveMachineUrl = "/live-production/machine.html?token={$line->token}";
    $csrfToken      = csrf_token();
    
    // Array para almacenar los botones que se mostrarán
    $buttons = [];
    
    // Botón de sensores - requiere permiso productionline-create
    if (auth()->user()->can('productionline-create')) {
        $sensorBtn = "<a href='{$sensorListUrl}' class='btn btn-sm btn-info' title='Sensors'>
            <i class='fa fa-microchip'></i> " . __('Sensors') . "
        </a>";
        $buttons[] = $sensorBtn;
    }
    
    // Botón de procesos - requiere permiso productionline-processes
    if (auth()->user()->can('productionline-processes')) {
        $processesBtn = "<a href='{$processesUrl}' class='btn btn-sm btn-primary' title='Processes'>
            <i class='fa fa-cogs'></i> " . __('Processes') . "
        </a>";
        $buttons[] = $processesBtn;
    }
    
    // Botón de órdenes - requiere permiso productionline-orders-kanban
    if (auth()->user()->can('productionline-orders-kanban')) {
        $ordersBtn = "<a href='{$ordersUrl}' class='btn btn-sm btn-secondary' title='Production Orders'>
            <i class='fa fa-list'></i> " . __('Orders') . "
        </a>";
        $buttons[] = $ordersBtn;
    }
    
    // Botón de vista en vivo - requiere permiso productionline-live-view
    if (auth()->user()->can('productionline-live-view')) {
        $liveViewBtn = "<a href='{$liveViewUrl}' target='_blank' rel='noopener noreferrer' class='btn btn-sm btn-secondary' title='" . __('buttons.live_view') . "'>
            <i class='fa fa-tv'></i> " . __('buttons.live_view') . "
        </a>";
        $buttons[] = $liveViewBtn;
    }
    
    // Botón de máquina en vivo - requiere permiso live-machine
    if (auth()->user()->can('productionline-live-machine')) {
        $liveMachineBtn = "<a href='{$liveMachineUrl}' target='_blank' rel='noopener noreferrer' class='btn btn-sm btn-success' title='" . __('buttons.live_machine') . "'>
            <i class='fa fa-cogs'></i> " . __('buttons.live_machine') . "
        </a>";
        $buttons[] = $liveMachineBtn;
    }
    
    // Botón de editar - requiere permiso productionline-edit
    if (auth()->user()->can('productionline-edit')) {
        $editBtn = "<a href='{$editUrl}' class='btn btn-sm btn-primary' title='" . __('buttons.edit') . "'>
            <i class='fa fa-edit'></i> " . __('buttons.edit') . "
        </a>";
        $buttons[] = $editBtn;
    }
    
    // Botón de eliminar - requiere permiso productionline-delete
    if (auth()->user()->can('productionline-delete')) {
        $deleteForm = "
            <form action='{$deleteUrl}' method='POST' style='display:inline; margin:0;'>
                <input type='hidden' name='_token' value='{$csrfToken}'>
                <input type='hidden' name='_method' value='DELETE'>
                <button type='submit' class='btn btn-sm btn-danger' title='" . __('buttons.delete') . "' onclick='return confirm(\"" . __('buttons.delete_confirm') . "?\")'>\n                    <i class='fa fa-trash'></i> " . __('buttons.delete') . "
                </button>
            </form>";
        $buttons[] = $deleteForm;
    }
    
    // Combina todos los botones en una sola cadena HTML
    return implode(' ', $buttons);
}

    public function edit($id)
    {
        $productionLine = ProductionLine::findOrFail($id);
        return view('productionlines.edit', compact('productionLine'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|max:255',
        ]);

        $line = ProductionLine::findOrFail($id);
        $line->update($request->all());

        return redirect()->route('productionlines.index', $line->customer_id)->with('success', 'Production Line updated successfully.');
    }

    public function destroy($id)
    {
        $line = ProductionLine::findOrFail($id);
        $line->delete();

        return redirect()->route('productionlines.index', $line->customer_id)->with('success', 'Production Line deleted successfully.');
    }

    public function create()
    {
        // Recuperar todos los clientes disponibles
        $customers = Customer::all();

        return view('productionlines.create', compact('customers'));
    }

    public function store(Request $request)
{
    // Validación de los datos del formulario
    $request->validate([
        'customer_id' => 'required|exists:customers,id', // Asegura que exista en la tabla 'customers'
        'name' => 'required|string|max:255',
        // Otros campos que puedas validar aquí
    ]);

    // Generar un token único para la línea de producción
    $token = Str::random(20); // Genera una cadena aleatoria de 20 caracteres

    // Crear una nueva instancia de ProductionLine con los datos del formulario
    ProductionLine::create([
        'customer_id' => $request->customer_id,
        'name' => $request->name,
        'token' => $token,
        // Añadir más campos según sea necesario
    ]);

    // Redirigir a la página de listado de líneas de producción (o a donde desees)
    return redirect()->route('productionlines.index', $request->customer_id)->with('success', 'Nueva línea de producción creada correctamente.');
}

    public function listStats()
    {
        return view('productionlines.liststats');
    }
}

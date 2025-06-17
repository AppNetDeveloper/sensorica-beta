<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\Process;
use Illuminate\Http\Request;

class CustomerOriginalOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:original-order-list|original-order-create|original-order-edit|original-order-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:original-order-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:original-order-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:original-order-delete', ['only' => ['destroy']]);
    }

    public function index(Customer $customer)
    {
        $originalOrders = $customer->originalOrders()->latest()->get();
        return view('customers.original-orders.index', compact('customer', 'originalOrders'));
    }

    public function create(Customer $customer)
    {
        $processes = Process::all();
        return view('customers.original-orders.create', [
            'customer' => $customer,
            'processes' => $processes
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'order_id' => 'required|unique:original_orders,order_id',
            'client_number' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'required|array',
            'processes.*' => 'exists:processes,id',
        ]);

        $originalOrder = $customer->originalOrders()->create([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'order_details' => $validated['order_details'],
            'processed' => false,
        ]);

        // Attach processes con cálculo de tiempo
        $processData = [];
        $orderDetails = json_decode($validated['order_details'], true);
        
        foreach ($validated['processes'] as $processId) {
            $process = Process::findOrFail($processId);
            $time = 0;
            
            // Buscar la cantidad en los detalles del pedido
            if (isset($orderDetails['grupos'])) {
                foreach ($orderDetails['grupos'] as $grupo) {
                    foreach ($grupo['servicios'] ?? [] as $servicio) {
                        if ($servicio['CodigoArticulo'] === $process->code) {
                            $cantidad = (float) $servicio['Cantidad'];
                            $time = $cantidad * $process->factor_correccion;
                            break 2; // Salir de ambos bucles
                        }
                    }
                }
            }
            
            $processData[$processId] = [
                'time' => $time,
                'created' => false,
                'finished' => false,
            ];
        }
        $originalOrder->processes()->sync($processData);

        return redirect()->route('customers.original-orders.index', $customer->id)
            ->with('success', 'Original order created successfully');
    }

    public function show(Customer $customer, OriginalOrder $originalOrder)
    {
        // Cargar procesos con todos los campos pivot explícitamente
        $originalOrder->load(['processes' => function($query) {
            $query->withPivot('id', 'time', 'created', 'finished', 'finished_at');
        }]);
        
        // Depurar los procesos cargados
        \Log::info('Procesos cargados para la orden ' . $originalOrder->id . ':');
        foreach ($originalOrder->processes as $process) {
            \Log::info("Proceso ID: {$process->id}, Código: {$process->code}, finished: " . 
                      ($process->pivot->finished ? 'true' : 'false') . 
                      ", finished_at: " . ($process->pivot->finished_at ?? 'null'));
        }
        
        return view('customers.original-orders.show', compact('customer', 'originalOrder'));
    }

    public function edit(Customer $customer, OriginalOrder $originalOrder)
    {
        $processes = Process::all();
        
        // Cargar procesos con todos los campos pivot explícitamente
        $originalOrder->load(['processes' => function($query) {
            $query->withPivot('id', 'time', 'created', 'finished', 'finished_at');
        }]);
        
        // Depurar los procesos cargados
        \Log::info('Procesos cargados para edición de la orden ' . $originalOrder->id . ':');
        foreach ($originalOrder->processes as $process) {
            \Log::info("Proceso ID: {$process->id}, Código: {$process->code}, finished: " . 
                      ($process->pivot->finished ? 'true' : 'false') . 
                      ", finished_at: " . ($process->pivot->finished_at ?? 'null'));
        }
        
        $selectedProcesses = $originalOrder->processes->pluck('id')->toArray();
        
        return view('customers.original-orders.edit', [
            'customer' => $customer,
            'originalOrder' => $originalOrder,
            'processes' => $processes,
            'selectedProcesses' => $selectedProcesses
        ]);
    }

    public function update(Request $request, Customer $customer, OriginalOrder $originalOrder)
    {
        // 1. Validar la petición.
        $validated = $request->validate([
            'order_id' => 'required|unique:original_orders,order_id,' . $originalOrder->id,
            'client_number' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'sometimes|array', // `sometimes` para permitir eliminar todos los procesos.
            'processes.*' => 'exists:processes,id', // Validar que cada ID de proceso exista.
            'processes_finished' => 'sometimes|array', // Contendrá los checkboxes marcados.
            'processed' => 'nullable|boolean',
        ]);

        // 2. Actualizar los campos principales de la orden.
        $originalOrder->update([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'order_details' => $validated['order_details'],
            'processed' => $request->boolean('processed'),
        ]);

        // 3. Preparar los datos para sincronizar los procesos.
        $syncData = [];
        $selectedProcesses = $request->input('processes', []);
        $finishedProcesses = $request->input('processes_finished', []);
        $orderDetails = json_decode($validated['order_details'], true);

        foreach ($selectedProcesses as $processId) {
            $process = Process::find($processId);
            if (!$process) continue; // Medida de seguridad, aunque la validación ya lo cubre.

            // Calcular el tiempo basado en los detalles de la orden.
            $time = 0;
            if (isset($orderDetails['grupos'])) {
                foreach ($orderDetails['grupos'] as $grupo) {
                    foreach ($grupo['servicios'] ?? [] as $servicio) {
                        if ($servicio['CodigoArticulo'] === $process->code) {
                            $cantidad = (float) $servicio['Cantidad'];
                            $time = $cantidad * $process->factor_correccion;
                            break 2; // Salir de ambos bucles una vez encontrado.
                        }
                    }
                }
            }

            // Determinar si el proceso está marcado como finalizado.
            // El formulario enviará `processes_finished[process_id] = 1` si está marcado.
            $isFinished = isset($finishedProcesses[$processId]);

            // Añadir los datos al array de sincronización.
            $syncData[$processId] = [
                'time' => $time,
                'finished' => $isFinished,
                // 'finished_at' se gestiona automáticamente en el modelo `OriginalOrderProcess`.
            ];
        }

        // 4. Sincronizar los procesos.
        // `sync()` se encarga de añadir, actualizar y eliminar las relaciones necesarias.
        $originalOrder->processes()->sync($syncData);

        // Procesar los artículos para cada instancia de proceso
        // Primero, obtenemos todas las instancias de procesos recién sincronizadas
        $processInstances = $originalOrder->processes()->withPivot('id')->get();
        
        // Recorremos cada instancia de proceso para procesar sus artículos
        foreach ($processInstances as $processInstance) {
            $processId = $processInstance->id;
            $processInstanceId = $processInstance->pivot->id; // Este es el original_order_process_id
            
            // Verificar si hay artículos para este proceso en el request
            $articlesKey = "processes.{$processId}.articles";
            $articles = $request->input($articlesKey);
            
            if ($articles) {
                // Eliminar artículos existentes para esta instancia de proceso
                $processInstance->pivot->articles()->delete();
                
                // Crear nuevos artículos para esta instancia de proceso
                foreach ($articles as $articleData) {
                    $processInstance->pivot->articles()->create([
                        'codigo_articulo' => $articleData['codigo_articulo'] ?? '',
                        'descripcion_articulo' => $articleData['descripcion_articulo'] ?? '',
                        'grupo_articulo' => $articleData['grupo_articulo'] ?? ''
                    ]);
                }
            }
        }
    
    // The OriginalOrderProcess model's 'saved' event now handles updating the OriginalOrder's finished_at status.

        return redirect()->route('customers.original-orders.index', $customer->id)
            ->with('success', 'Original order updated successfully');
    }

    public function destroy(Customer $customer, OriginalOrder $originalOrder)
    {
        $originalOrder->delete();
        return redirect()->route('customers.original-orders.index', $customer->id)
            ->with('success', 'Original order deleted successfully');
    }
}

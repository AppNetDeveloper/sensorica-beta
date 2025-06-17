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
        
        // Preparar los artículos para cada proceso
        $articlesData = [];
        
        foreach ($originalOrder->processes as $process) {
            $pivotId = $process->pivot->id;
            
            // Cargar los artículos para este proceso
            $articles = \App\Models\OriginalOrderArticle::where('original_order_process_id', $pivotId)->get();
            
            // Si hay artículos, los agregamos al array
            if ($articles->count() > 0) {
                $articlesData[$pivotId] = $articles->map(function($article) {
                    return [
                        'id' => $article->id,
                        'code' => $article->codigo_articulo,
                        'description' => $article->descripcion_articulo,
                        'group' => $article->grupo_articulo
                    ];
                })->toArray();
            }
        }
        
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
            'selectedProcesses' => $selectedProcesses,
            'articlesData' => json_encode($articlesData)
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
        
        // Procesamos cada proceso seleccionado
        foreach ($selectedProcesses as $uniqueId => $processId) {
            // Verificamos si es un ID válido (podría ser un ID temporal con prefijo 'new_')
            if (!is_numeric($processId)) {
                continue; // Saltamos IDs no numéricos por seguridad
            }
            
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
            // El formulario enviará `processes_finished[unique_id] = 1` si está marcado.
            $isFinished = isset($finishedProcesses[$uniqueId]);

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
        $processInstances = $originalOrder->processes()->withPivot('id')->get();
        $processInstancesById = $processInstances->keyBy('pivot.id');
        
        // Obtener los artículos enviados desde el formulario
        $articlesData = $request->input('articles', []);
        
        // Recorremos cada instancia de proceso para procesar sus artículos
        foreach ($processInstancesById as $pivotId => $processInstance) {
            // Eliminar artículos existentes para esta instancia de proceso
            $processInstance->pivot->articles()->delete();
            
            // Verificar si hay artículos para esta instancia de proceso
            if (isset($articlesData[$pivotId])) {
                $articles = $articlesData[$pivotId];
                
                // Crear nuevos artículos para esta instancia de proceso
                foreach ($articles as $articleId => $articleData) {
                    // Validar que tengamos al menos un código de artículo
                    if (empty($articleData['code'])) continue;
                    
                    $processInstance->pivot->articles()->create([
                        'codigo_articulo' => $articleData['code'] ?? '',
                        'descripcion_articulo' => $articleData['description'] ?? '',
                        'grupo_articulo' => $articleData['group'] ?? ''
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

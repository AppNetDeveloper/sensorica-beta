<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\Process;
use Illuminate\Http\Request;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderArticle;

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
            'processes' => 'sometimes|array',
            'processes.*' => 'exists:processes,id',
            'finished' => 'sometimes|array',
            'processed' => 'nullable|boolean',
            'articles' => 'sometimes|array',
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

        // 3. Obtener datos del formulario.
        $selectedProcesses = $request->input('processes', []);
        $finishedProcesses = $request->input('finished', []);
        $articlesData = $request->input('articles', []);
        $orderDetails = json_decode($validated['order_details'], true);
        
        // Mapeará los IDs únicos del formulario a los nuevos IDs de la tabla pivote.
        $processedPivotIds = []; 
        
        // 4. Sincronización de procesos: eliminar todos y volver a crearlos.
        $originalOrder->processes()->detach();
        
        foreach ($selectedProcesses as $uniqueId => $processId) {
            if (!is_numeric($processId)) {
                if (strpos($processId, 'new_') === 0) {
                    $processId = substr($processId, 4);
                } else {
                    continue;
                }
            }
            
            $process = \App\Models\Process::find($processId);
            if (!$process) continue;
            
            $time = 0;
            if (isset($orderDetails['grupos'])) {
                foreach ($orderDetails['grupos'] as $grupo) {
                    foreach ($grupo['servicios'] ?? [] as $servicio) {
                        if ($servicio['CodigoArticulo'] === $process->code) {
                            $cantidad = (float) $servicio['Cantidad'];
                            $time = $cantidad * $process->factor_correccion;
                            break 2;
                        }
                    }
                }
            }
            
            $isFinished = isset($finishedProcesses[$uniqueId]);
            
            $pivotData = [
                'time' => $time,
                'created' => true,
                'finished' => $isFinished,
                'finished_at' => $isFinished ? now() : null,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $pivotId = \DB::table('original_order_processes')->insertGetId(
                array_merge(
                    ['original_order_id' => $originalOrder->id, 'process_id' => $processId],
                    $pivotData
                )
            );
            
            // Guardar el mapeo del ID del formulario al nuevo ID de la BD.
            $processedPivotIds[$uniqueId] = $pivotId;
        }
        
        // 5. Cargar las nuevas relaciones de procesos para poder adjuntar artículos.
        $originalOrder->load('processes');

        // 6. Sincronización de artículos usando el mapeo de IDs.
        $remappedArticlesData = [];
        if (is_array($articlesData)) {
            foreach ($articlesData as $formUniqueId => $articles) {
                // Usar el mapeo para encontrar el nuevo ID de pivote.
                if (isset($processedPivotIds[$formUniqueId])) {
                    $newPivotId = $processedPivotIds[$formUniqueId];
                    $remappedArticlesData[$newPivotId] = $articles;
                }
            }
        }
        
        $processInstancesById = $originalOrder->processes->keyBy('pivot.id');

        foreach ($processInstancesById as $pivotId => $processInstance) {
            // Borrar artículos viejos.
            $processInstance->pivot->articles()->delete();
            
            // Crear artículos nuevos si existen en los datos remapeados.
            if (isset($remappedArticlesData[$pivotId])) {
                $articles = $remappedArticlesData[$pivotId];
                
                foreach ($articles as $articleData) {
                    if (empty($articleData['code'])) continue;
                    
                    $processInstance->pivot->articles()->create([
                        'codigo_articulo' => $articleData['code'] ?? '',
                        'descripcion_articulo' => $articleData['description'] ?? '',
                        'grupo_articulo' => $articleData['group'] ?? ''
                    ]);
                }
            }
        }
        
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

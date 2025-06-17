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
        $originalOrder->load('processes');
        return view('customers.original-orders.show', compact('customer', 'originalOrder'));
    }

    public function edit(Customer $customer, OriginalOrder $originalOrder)
    {
        $processes = Process::all();
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
        $validated = $request->validate([
            'order_id' => 'required|unique:original_orders,order_id,' . $originalOrder->id,
            'client_number' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'required|array',
            'processes.*' => 'exists:processes,id',
            'processed' => 'nullable|boolean',
        ]);

        $originalOrder->update([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'order_details' => $validated['order_details'],
            'processed' => $request->boolean('processed'),
        ]);

        // Sync processes
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
            
            // Inicializar con valores por defecto
            $processData[$processId] = [
                'time' => $time,
                'created' => false,
                'finished' => false,
                'finished_at' => null
            ];

            // Si el proceso ya existía, mantener su estado actual
            if ($originalOrder->processes->contains($processId)) {
                $currentPivot = $originalOrder->processes->find($processId)->pivot;
                $processData[$processId] = array_merge($processData[$processId], [
                    'created' => $currentPivot->created,
                    'finished' => $currentPivot->finished,
                    'finished_at' => $currentPivot->finished_at
                ]);
            }

            // Actualizar estado de finalización si se envía en el request
            $isNowFinished = $request->boolean('processes_finished.' . $processId);
            if ($isNowFinished) {
                // Si se marca como finalizado y no lo estaba antes
                if (!$processData[$processId]['finished']) {
                    $processData[$processId]['finished'] = true;
                    $processData[$processId]['finished_at'] = now();
                }
            } else {
                // Si se desmarca como finalizado
                $processData[$processId]['finished'] = false;
                $processData[$processId]['finished_at'] = null;
            }
        }
        $originalOrder->processes()->sync($processData);
    
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

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
            'client_number' => 'nullable|string|max:255', // Añadido
            'order_details' => 'required|json',
            'processes' => 'required|array',
            'processes.*' => 'exists:processes,id',
        ]);

        $originalOrder = $customer->originalOrders()->create([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null, // Añadido
            'order_details' => $validated['order_details'],
            'processed' => false,
        ]);

        // Attach processes
        $processData = [];
        foreach ($validated['processes'] as $processId) {
            $processData[$processId] = [
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
            'client_number' => 'nullable|string|max:255', // Añadido
            'order_details' => 'required|json',
            'processes' => 'required|array',
            'processes.*' => 'exists:processes,id',
        ]);

        $originalOrder->update([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null, // Añadido
            'order_details' => $validated['order_details'],
        ]);

        // Sync processes
        $processData = [];
        foreach ($validated['processes'] as $processId) {
            $wasFinished = $originalOrder->processes->contains($processId) ? 
                $originalOrder->processes->find($processId)->pivot->finished : false;
            $isNowFinished = $request->input('processes_finished.' . $processId, false);
            
            $processData[$processId] = [
                'created' => $originalOrder->processes->contains($processId) ? 
                    $originalOrder->processes->find($processId)->pivot->created : false,
                'finished' => $isNowFinished,
                'finished_at' => $isNowFinished && !$wasFinished ? now() : 
                    ($originalOrder->processes->contains($processId) ? 
                        $originalOrder->processes->find($processId)->pivot->finished_at : null),
            ];
        }
        $originalOrder->processes()->sync($processData);
        
        // Actualizar el estado finished_at de la orden principal si todos los procesos están terminados
        $originalOrder->updateFinishedStatus();

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

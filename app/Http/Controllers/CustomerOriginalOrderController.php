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
            'processed' => 'nullable|boolean', // Añadir esta línea
        ]);

        $originalOrder->update([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null, // Añadido
            'order_details' => $validated['order_details'],
            'processed' => $request->boolean('processed'), // Añadir esta línea
        ]);

        // Sync processes
        $processData = [];
        foreach ($validated['processes'] as $processId) {
            $isNowFinished = $request->boolean('processes_finished.' . $processId); // Usar boolean() para obtener true/false
            $currentPivot = null;

            if ($originalOrder->processes->contains($processId)) {
                $currentPivot = $originalOrder->processes->find($processId)->pivot;
            }

            $newFinishedAt = null;
            if ($isNowFinished) {
                // Si se marca como finalizado:
                // - Si ya tenía una fecha de finalización, se conserva.
                // - Si no la tenía (era null), se establece a now().
                $newFinishedAt = $currentPivot && $currentPivot->finished_at ? $currentPivot->finished_at : now();
            } else {
                // Si se desmarca, finished_at es null.
                $newFinishedAt = null;
            }

            $processData[$processId] = [
                // 'created' no debería estar aquí si usas withTimestamps() en la relación,
                // ya que created_at y updated_at se manejan automáticamente.
                // Si 'created' es un campo booleano tuyo, su lógica debe ser revisada.
                // Por ahora, lo comentaré asumiendo que es el timestamp automático.
                // 'created' => $currentPivot ? $currentPivot->created : false, // Revisa esta lógica si 'created' es un campo tuyo
                'finished_at' => $newFinishedAt,
            ];
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

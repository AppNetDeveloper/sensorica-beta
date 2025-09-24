<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductionOrderCallback;
use Illuminate\Http\Request;

class ProductionOrderCallbackController extends Controller
{
    /**
     * Display a listing of the callbacks for a customer.
     */
    public function index(Customer $customer)
    {
        $this->authorize('viewAny', ProductionOrderCallback::class);

        $callbacks = ProductionOrderCallback::where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->paginate(50);

        return view('customers.callbacks.index', compact('customer', 'callbacks'));
    }

    /**
     * Show the form for editing the specified callback.
     */
    public function edit(Customer $customer, ProductionOrderCallback $callback)
    {
        $this->authorize('update', $callback);
        if ((int)$callback->customer_id !== (int)$customer->id) {
            abort(404);
        }
        return view('customers.callbacks.edit', compact('customer', 'callback'));
    }

    /**
     * Update the specified callback in storage.
     */
    public function update(Request $request, Customer $customer, ProductionOrderCallback $callback)
    {
        $this->authorize('update', $callback);
        if ((int)$callback->customer_id !== (int)$customer->id) {
            abort(404);
        }

        $data = $request->validate([
            'callback_url' => 'required|url',
            'payload' => 'nullable',
            'status' => 'nullable|integer|in:0,1,2',
        ]);

        // If payload provided as string, try to decode JSON; if empty, keep existing
        if (array_key_exists('payload', $data)) {
            $payload = $data['payload'];
            if (is_string($payload) && $payload !== '') {
                $decoded = json_decode($payload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['payload'] = $decoded;
                } else {
                    return back()->withErrors(['payload' => 'El payload debe ser JSON válido'])->withInput();
                }
            } elseif ($payload === '' || is_null($payload)) {
                unset($data['payload']); // do not overwrite if left empty
            }
        }

        $callback->update($data);

        return redirect()->route('customers.callbacks.index', $customer->id)
            ->with('success', 'Callback actualizado correctamente');
    }

    /**
     * Remove the specified callback from storage.
     */
    public function destroy(Customer $customer, ProductionOrderCallback $callback)
    {
        $this->authorize('delete', $callback);
        if ((int)$callback->customer_id !== (int)$customer->id) {
            abort(404);
        }
        $callback->delete();
        return redirect()->route('customers.callbacks.index', $customer->id)
            ->with('success', 'Callback eliminado');
    }

    /**
     * Force a retry attempt: reset status to pending and attempts to 0.
     */
    public function force(Customer $customer, ProductionOrderCallback $callback)
    {
        $this->authorize('force', $callback);
        if ((int)$callback->customer_id !== (int)$customer->id) {
            abort(404);
        }
        $callback->status = 0; // pending
        $callback->attempts = 0;
        $callback->last_attempt_at = null;
        $callback->success_at = null;
        $callback->error_message = null;
        $callback->save();

        return redirect()->route('customers.callbacks.index', $customer->id)
            ->with('success', 'Reintento forzado encolado');
    }
}

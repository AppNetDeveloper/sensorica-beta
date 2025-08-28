<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MaintenanceCause;
use Illuminate\Http\Request;

class MaintenanceCauseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        // Adjust permissions later if needed
        $this->middleware('permission:maintenance-create')->only(['index','create','store']);
    }

    public function index(Customer $customer)
    {
        $causes = MaintenanceCause::where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        return view('customers.maintenance_causes.index', [
            'customer' => $customer,
            'causes' => $causes,
        ]);
    }

    public function create(Customer $customer)
    {
        return view('customers.maintenance_causes.create', [
            'customer' => $customer,
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);
        $data['customer_id'] = $customer->id;
        $data['active'] = (bool)($data['active'] ?? true);

        MaintenanceCause::create($data);

        return redirect()->route('customers.maintenance-causes.index', $customer->id)
            ->with('success', __('Cause created successfully'));
    }

    public function edit(Customer $customer, MaintenanceCause $maintenance_cause)
    {
        if ($maintenance_cause->customer_id !== $customer->id) {
            abort(404);
        }
        return view('customers.maintenance_causes.edit', [
            'customer' => $customer,
            'cause' => $maintenance_cause,
        ]);
    }

    public function update(Request $request, Customer $customer, MaintenanceCause $maintenance_cause)
    {
        if ($maintenance_cause->customer_id !== $customer->id) {
            abort(404);
        }
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);
        $data['active'] = (bool)($data['active'] ?? false);

        $maintenance_cause->update($data);

        return redirect()->route('customers.maintenance-causes.index', $customer->id)
            ->with('success', __('Cause updated successfully'));
    }

    public function destroy(Customer $customer, MaintenanceCause $maintenance_cause)
    {
        if ($maintenance_cause->customer_id !== $customer->id) {
            abort(404);
        }
        $maintenance_cause->delete();

        return redirect()->route('customers.maintenance-causes.index', $customer->id)
            ->with('success', __('Cause deleted successfully'));
    }
}

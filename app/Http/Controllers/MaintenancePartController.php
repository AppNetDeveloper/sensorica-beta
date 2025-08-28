<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MaintenancePart;
use Illuminate\Http\Request;

class MaintenancePartController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        // Adjust permissions later if needed
        $this->middleware('permission:maintenance-create')->only(['index','create','store']);
    }

    public function index(Customer $customer)
    {
        $parts = MaintenancePart::where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        return view('customers.maintenance_parts.index', [
            'customer' => $customer,
            'parts' => $parts,
        ]);
    }

    public function create(Customer $customer)
    {
        return view('customers.maintenance_parts.create', [
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

        MaintenancePart::create($data);

        return redirect()->route('customers.maintenance-parts.index', $customer->id)
            ->with('success', __('Part created successfully'));
    }

    public function edit(Customer $customer, MaintenancePart $maintenance_part)
    {
        if ($maintenance_part->customer_id !== $customer->id) {
            abort(404);
        }
        return view('customers.maintenance_parts.edit', [
            'customer' => $customer,
            'part' => $maintenance_part,
        ]);
    }

    public function update(Request $request, Customer $customer, MaintenancePart $maintenance_part)
    {
        if ($maintenance_part->customer_id !== $customer->id) {
            abort(404);
        }
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);
        $data['active'] = (bool)($data['active'] ?? false);

        $maintenance_part->update($data);

        return redirect()->route('customers.maintenance-parts.index', $customer->id)
            ->with('success', __('Part updated successfully'));
    }

    public function destroy(Customer $customer, MaintenancePart $maintenance_part)
    {
        if ($maintenance_part->customer_id !== $customer->id) {
            abort(404);
        }
        $maintenance_part->delete();

        return redirect()->route('customers.maintenance-parts.index', $customer->id)
            ->with('success', __('Part deleted successfully'));
    }
}

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
        $lineId = request('production_line_id');
        $query = MaintenancePart::where('customer_id', $customer->id);
        if ($lineId === 'global') {
            $query->whereNull('production_line_id');
        } elseif (!empty($lineId)) {
            $query->where('production_line_id', (int)$lineId);
        }
        $parts = $query->orderByDesc('id')->get();

        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);

        return view('customers.maintenance_parts.index', [
            'customer' => $customer,
            'parts' => $parts,
            'lines' => $lines,
            'currentLineId' => $lineId,
        ]);
    }

    public function create(Customer $customer)
    {
        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);
        return view('customers.maintenance_parts.create', [
            'customer' => $customer,
            'lines' => $lines,
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
            'production_line_ids' => 'nullable|array',
            'production_line_ids.*' => 'integer',
        ]);
        $active = (bool)($data['active'] ?? true);
        $base = [
            'customer_id' => $customer->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'active' => $active,
        ];
        $lineIds = collect($data['production_line_ids'] ?? [])->filter()->map(fn($id) => (int)$id)->unique();
        if ($lineIds->isEmpty()) {
            MaintenancePart::create($base + ['production_line_id' => null]);
        } else {
            foreach ($lineIds as $lid) {
                MaintenancePart::create($base + ['production_line_id' => $lid]);
            }
        }

        return redirect()->route('customers.maintenance-parts.index', $customer->id)
            ->with('success', __('Part created successfully'));
    }

    public function edit(Customer $customer, MaintenancePart $maintenance_part)
    {
        if ($maintenance_part->customer_id !== $customer->id) {
            abort(404);
        }
        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);
        return view('customers.maintenance_parts.edit', [
            'customer' => $customer,
            'part' => $maintenance_part,
            'lines' => $lines,
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
            'production_line_id' => 'nullable|integer',
        ]);
        $data['active'] = (bool)($data['active'] ?? false);

        $maintenance_part->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'active' => $data['active'],
            'production_line_id' => $data['production_line_id'] ?? null,
        ]);

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

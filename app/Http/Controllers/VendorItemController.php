<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\VendorItem;
use App\Models\VendorSupplier;
use Illuminate\Http\Request;

class VendorItemController extends Controller
{
    public function index(Customer $customer)
    {
        $items = $customer->vendorItems()->with('supplier')->orderBy('name')->get();

        return view('customers.vendor-items.index', compact('customer', 'items'));
    }

    public function create(Customer $customer)
    {
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->pluck('name', 'id');

        return view('customers.vendor-items.create', compact('customer', 'suppliers'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'vendor_supplier_id' => ['nullable', 'exists:vendor_suppliers,id'],
            'unit_of_measure' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!empty($data['vendor_supplier_id'])) {
            $supplier = VendorSupplier::findOrFail($data['vendor_supplier_id']);
            abort_unless($supplier->customer_id === $customer->id, 404);
        }

        $customer->vendorItems()->create($data);

        return redirect()->route('customers.vendor-items.index', $customer)
            ->with('success', __('Item created successfully.'));
    }

    public function edit(Customer $customer, VendorItem $vendorItem)
    {
        $this->ensureSameCustomer($customer, $vendorItem);
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->pluck('name', 'id');

        return view('customers.vendor-items.edit', [
            'customer' => $customer,
            'item' => $vendorItem,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, Customer $customer, VendorItem $vendorItem)
    {
        $this->ensureSameCustomer($customer, $vendorItem);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'vendor_supplier_id' => ['nullable', 'exists:vendor_suppliers,id'],
            'unit_of_measure' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!empty($data['vendor_supplier_id'])) {
            $supplier = VendorSupplier::findOrFail($data['vendor_supplier_id']);
            abort_unless($supplier->customer_id === $customer->id, 404);
        }

        $vendorItem->update($data);

        return redirect()->route('customers.vendor-items.index', $customer)
            ->with('success', __('Item updated successfully.'));
    }

    public function destroy(Customer $customer, VendorItem $vendorItem)
    {
        $this->ensureSameCustomer($customer, $vendorItem);
        $vendorItem->delete();

        return redirect()->route('customers.vendor-items.index', $customer)
            ->with('success', __('Item deleted successfully.'));
    }

    protected function ensureSameCustomer(Customer $customer, VendorItem $item): void
    {
        abort_unless($item->customer_id === $customer->id, 404);
    }
}

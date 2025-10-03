<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\VendorSupplier;
use Illuminate\Http\Request;

class VendorSupplierController extends Controller
{
    public function index(Customer $customer)
    {
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->get();

        return view('customers.vendor-suppliers.index', compact('customer', 'suppliers'));
    }

    public function create(Customer $customer)
    {
        return view('customers.vendor-suppliers.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->vendorSuppliers()->create($data);

        return redirect()->route('customers.vendor-suppliers.index', $customer)
            ->with('success', __('Supplier created successfully.'));
    }

    public function edit(Customer $customer, VendorSupplier $vendorSupplier)
    {
        $this->ensureSameCustomer($customer, $vendorSupplier);

        return view('customers.vendor-suppliers.edit', [
            'customer' => $customer,
            'supplier' => $vendorSupplier,
        ]);
    }

    public function update(Request $request, Customer $customer, VendorSupplier $vendorSupplier)
    {
        $this->ensureSameCustomer($customer, $vendorSupplier);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
        ]);

        $vendorSupplier->update($data);

        return redirect()->route('customers.vendor-suppliers.index', $customer)
            ->with('success', __('Supplier updated successfully.'));
    }

    public function destroy(Customer $customer, VendorSupplier $vendorSupplier)
    {
        $this->ensureSameCustomer($customer, $vendorSupplier);
        $vendorSupplier->delete();

        return redirect()->route('customers.vendor-suppliers.index', $customer)
            ->with('success', __('Supplier deleted successfully.'));
    }

    protected function ensureSameCustomer(Customer $customer, VendorSupplier $supplier): void
    {
        abort_unless($supplier->customer_id === $customer->id, 404);
    }
}

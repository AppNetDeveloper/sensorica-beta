<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerContactController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        $this->middleware('permission:customer-contacts-view')->only(['edit']);
        $this->middleware('permission:customer-contacts-edit')->only(['update']);
    }

    public function edit(Customer $customer)
    {
        return view('customers.contacts.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
        ]);
        $customer->update($data);
        return redirect()->route('customers.contacts.edit', $customer->id)
            ->with('success', __('Customer updated successfully'));
    }
}

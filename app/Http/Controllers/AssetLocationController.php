<?php

namespace App\Http\Controllers;

use App\Models\AssetLocation;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetLocationController extends Controller
{
    public function index(Customer $customer)
    {
        $this->authorize('viewAny', [AssetLocation::class, $customer]);

        $locations = $customer->assetLocations()->orderBy('name')->get();

        return view('customers.asset-locations.index', compact('customer', 'locations'));
    }

    public function create(Customer $customer)
    {
        $this->authorize('create', [AssetLocation::class, $customer]);

        return view('customers.asset-locations.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorize('create', [AssetLocation::class, $customer]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_locations', 'code')->where('customer_id', $customer->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $customer->assetLocations()->create($data);

        return redirect()->route('customers.asset-locations.index', $customer)
            ->with('success', __('Ubicación creada correctamente.'));
    }

    public function edit(Customer $customer, AssetLocation $assetLocation)
    {
        $this->authorize('update', $assetLocation);
        $this->ensureSameCustomer($customer, $assetLocation);

        return view('customers.asset-locations.edit', compact('customer', 'assetLocation'));
    }

    public function update(Request $request, Customer $customer, AssetLocation $assetLocation)
    {
        $this->authorize('update', $assetLocation);
        $this->ensureSameCustomer($customer, $assetLocation);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_locations', 'code')
                    ->where('customer_id', $customer->id)
                    ->ignore($assetLocation->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $assetLocation->update($data);

        return redirect()->route('customers.asset-locations.index', $customer)
            ->with('success', __('Ubicación actualizada correctamente.'));
    }

    public function destroy(Customer $customer, AssetLocation $assetLocation)
    {
        $this->authorize('delete', $assetLocation);
        $this->ensureSameCustomer($customer, $assetLocation);

        $assetLocation->delete();

        return redirect()->route('customers.asset-locations.index', $customer)
            ->with('success', __('Ubicación eliminada correctamente.'));
    }

    protected function ensureSameCustomer(Customer $customer, AssetLocation $assetLocation): void
    {
        abort_unless($assetLocation->customer_id === $customer->id, 404);
    }
}

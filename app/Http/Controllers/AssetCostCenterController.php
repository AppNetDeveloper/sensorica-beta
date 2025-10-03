<?php

namespace App\Http\Controllers;

use App\Models\AssetCostCenter;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetCostCenterController extends Controller
{
    public function index(Customer $customer)
    {
        $this->authorize('viewAny', [AssetCostCenter::class, $customer]);

        $costCenters = $customer->assetCostCenters()->orderBy('name')->get();

        return view('customers.asset-cost-centers.index', compact('customer', 'costCenters'));
    }

    public function create(Customer $customer)
    {
        $this->authorize('create', [AssetCostCenter::class, $customer]);

        return view('customers.asset-cost-centers.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorize('create', [AssetCostCenter::class, $customer]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_cost_centers', 'code')->where('customer_id', $customer->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $customer->assetCostCenters()->create($data);

        return redirect()->route('customers.asset-cost-centers.index', $customer)
            ->with('success', __('Centro de coste creado correctamente.'));
    }

    public function edit(Customer $customer, AssetCostCenter $assetCostCenter)
    {
        $this->authorize('update', $assetCostCenter);
        $this->ensureSameCustomer($customer, $assetCostCenter);

        return view('customers.asset-cost-centers.edit', compact('customer', 'assetCostCenter'));
    }

    public function update(Request $request, Customer $customer, AssetCostCenter $assetCostCenter)
    {
        $this->authorize('update', $assetCostCenter);
        $this->ensureSameCustomer($customer, $assetCostCenter);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_cost_centers', 'code')
                    ->where('customer_id', $customer->id)
                    ->ignore($assetCostCenter->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $assetCostCenter->update($data);

        return redirect()->route('customers.asset-cost-centers.index', $customer)
            ->with('success', __('Centro de coste actualizado correctamente.'));
    }

    public function destroy(Customer $customer, AssetCostCenter $assetCostCenter)
    {
        $this->authorize('delete', $assetCostCenter);
        $this->ensureSameCustomer($customer, $assetCostCenter);

        $assetCostCenter->delete();

        return redirect()->route('customers.asset-cost-centers.index', $customer)
            ->with('success', __('Centro de coste eliminado correctamente.'));
    }

    protected function ensureSameCustomer(Customer $customer, AssetCostCenter $assetCostCenter): void
    {
        abort_unless($assetCostCenter->customer_id === $customer->id, 404);
    }
}

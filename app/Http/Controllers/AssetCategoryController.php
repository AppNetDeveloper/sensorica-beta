<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    public function index(Customer $customer)
    {
        $this->authorize('viewAny', [AssetCategory::class, $customer]);

        $categories = $customer->assetCategories()->with('parent')->orderBy('name')->get();

        return view('customers.asset-categories.index', compact('customer', 'categories'));
    }

    public function create(Customer $customer)
    {
        $this->authorize('create', [AssetCategory::class, $customer]);

        $parents = $customer->assetCategories()->orderBy('name')->pluck('name', 'id');

        return view('customers.asset-categories.create', compact('customer', 'parents'));
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorize('create', [AssetCategory::class, $customer]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                Rule::exists('asset_categories', 'id')->where(function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                }),
            ],
            'label_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'label_code')->where('customer_id', $customer->id),
            ],
            'rfid_epc' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'rfid_epc')->where('customer_id', $customer->id),
            ],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = $this->uniqueSlug($customer, $data['name']);

        $customer->assetCategories()->create($data);

        return redirect()->route('customers.asset-categories.index', $customer)
            ->with('success', __('Categoría de activos creada correctamente.'));
    }

    public function edit(Customer $customer, AssetCategory $assetCategory)
    {
        $this->authorize('update', $assetCategory);
        $this->ensureSameCustomer($customer, $assetCategory);

        $parents = $customer->assetCategories()
            ->where('id', '<>', $assetCategory->id)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('customers.asset-categories.edit', compact('customer', 'assetCategory', 'parents'));
    }

    public function update(Request $request, Customer $customer, AssetCategory $assetCategory)
    {
        $this->authorize('update', $assetCategory);
        $this->ensureSameCustomer($customer, $assetCategory);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                Rule::exists('asset_categories', 'id')->where(function ($query) use ($customer, $assetCategory) {
                    $query->where('customer_id', $customer->id)
                        ->where('id', '<>', $assetCategory->id);
                }),
            ],
            'label_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'label_code')
                    ->where('customer_id', $customer->id)
                    ->ignore($assetCategory->id),
            ],
            'rfid_epc' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'rfid_epc')
                    ->where('customer_id', $customer->id)
                    ->ignore($assetCategory->id),
            ],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = $this->uniqueSlug($customer, $data['name'], $assetCategory->id);

        if (isset($data['parent_id']) && (int) $data['parent_id'] === $assetCategory->id) {
            return back()->withInput()->withErrors([
                'parent_id' => __('La categoría no puede ser su propio padre.'),
            ]);
        }

        $assetCategory->update($data);

        return redirect()->route('customers.asset-categories.index', $customer)
            ->with('success', __('Categoría de activos actualizada correctamente.'));
    }

    public function destroy(Customer $customer, AssetCategory $assetCategory)
    {
        $this->authorize('delete', $assetCategory);
        $this->ensureSameCustomer($customer, $assetCategory);

        $assetCategory->delete();

        return redirect()->route('customers.asset-categories.index', $customer)
            ->with('success', __('Categoría de activos eliminada correctamente.'));
    }

    protected function ensureSameCustomer(Customer $customer, AssetCategory $assetCategory): void
    {
        abort_unless($assetCategory->customer_id === $customer->id, 404);
    }

    protected function uniqueSlug(Customer $customer, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: Str::slug('categoria-' . Str::random(6));
        $slug = $baseSlug;
        $counter = 2;

        while ($customer->assetCategories()
            ->where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

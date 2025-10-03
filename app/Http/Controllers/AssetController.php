<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    private const STATUSES = [
        'active',
        'inactive',
        'maintenance',
        'retired',
        'lost',
        'sold',
        'consumed',
        'damaged',
        'quality_issue',
    ];

    public function index(Request $request, Customer $customer)
    {
        $this->authorize('viewAny', [Asset::class, $customer]);

        $query = $customer->assets()
            ->with(['category', 'costCenter', 'location', 'supplier']);

        $search = trim((string) $request->query('search'));
        $categoryId = $request->query('category');
        $locationId = $request->query('location');
        $supplierId = $request->query('supplier');
        $statusFilter = $request->query('status');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('label_code', 'like', "%{$search}%")
                    ->orWhere('article_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('asset_category_id', $categoryId);
        }

        if ($locationId) {
            $query->where('asset_location_id', $locationId);
        }

        if ($supplierId) {
            $query->where('vendor_supplier_id', $supplierId);
        }

        $statsQuery = clone $query;

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'maintenance' => (clone $statsQuery)->where('status', 'maintenance')->count(),
            'without_location' => (clone $statsQuery)->whereNull('asset_location_id')->count(),
            'with_rfid' => (clone $statsQuery)->where('has_rfid_tag', true)->count(),
        ];

        $stats['active_percent'] = $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100, 1) : 0.0;
        $stats['maintenance_percent'] = $stats['total'] > 0 ? round(($stats['maintenance'] / $stats['total']) * 100, 1) : 0.0;
        $stats['without_location_percent'] = $stats['total'] > 0 ? round(($stats['without_location'] / $stats['total']) * 100, 1) : 0.0;
        $stats['rfid_percent'] = $stats['total'] > 0 ? round(($stats['with_rfid'] / $stats['total']) * 100, 1) : 0.0;

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $assets = $query->orderBy('label_code')->get();

        $categories = $customer->assetCategories()->orderBy('name')->pluck('name', 'id');
        $locations = $customer->assetLocations()->orderBy('name')->pluck('name', 'id');
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->pluck('name', 'id');
        $statuses = self::STATUSES;

        $filters = [
            'search' => $search,
            'category' => $categoryId,
            'location' => $locationId,
            'supplier' => $supplierId,
            'status' => $statusFilter,
        ];

        return view('customers.assets.index', compact(
            'customer',
            'assets',
            'categories',
            'locations',
            'suppliers',
            'statuses',
            'stats',
            'filters'
        ));
    }

    public function create(Customer $customer)
    {
        $this->authorize('create', [Asset::class, $customer]);

        [$categories, $costCenters, $locations, $suppliers] = $this->formSelections($customer);

        $statuses = self::STATUSES;

        return view('customers.assets.create', compact(
            'customer',
            'categories',
            'costCenters',
            'locations',
            'suppliers',
            'statuses'
        ));
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorize('create', [Asset::class, $customer]);

        $data = $this->validateData($request, $customer);

        $customer->assets()->create($data);

        return redirect()->route('customers.assets.index', $customer)
            ->with('success', __('Activo creado correctamente.'));
    }

    public function show(Customer $customer, Asset $asset)
    {
        $this->authorize('view', $asset);
        $this->ensureSameCustomer($customer, $asset);

        $asset->load(['category.parent', 'costCenter', 'location', 'supplier', 'events.user']);

        $statuses = self::STATUSES;

        return view('customers.assets.show', compact('customer', 'asset', 'statuses'));
    }

    public function edit(Customer $customer, Asset $asset)
    {
        $this->authorize('update', $asset);
        $this->ensureSameCustomer($customer, $asset);

        [$categories, $costCenters, $locations, $suppliers] = $this->formSelections($customer);
        $statuses = self::STATUSES;

        return view('customers.assets.edit', compact(
            'customer',
            'asset',
            'categories',
            'costCenters',
            'locations',
            'suppliers',
            'statuses'
        ));
    }

    public function update(Request $request, Customer $customer, Asset $asset)
    {
        $this->authorize('update', $asset);
        $this->ensureSameCustomer($customer, $asset);

        $data = $this->validateData($request, $customer, $asset->id);

        $asset->update($data);

        return redirect()->route('customers.assets.show', [$customer, $asset])
            ->with('success', __('Activo actualizado correctamente.'));
    }

    public function destroy(Customer $customer, Asset $asset)
    {
        $this->authorize('delete', $asset);
        $this->ensureSameCustomer($customer, $asset);

        $asset->delete();

        return redirect()->route('customers.assets.index', $customer)
            ->with('success', __('Activo eliminado correctamente.'));
    }

    public function printLabel(Customer $customer, Asset $asset)
    {
        $this->authorize('printLabel', $asset);
        $this->ensureSameCustomer($customer, $asset);

        return view('customers.assets.label', compact('customer', 'asset'));
    }

    protected function formSelections(Customer $customer): array
    {
        $categories = $customer->assetCategories()->orderBy('name')->pluck('name', 'id');
        $costCenters = $customer->assetCostCenters()->orderBy('name')->pluck('name', 'id');
        $locations = $customer->assetLocations()->orderBy('name')->pluck('name', 'id');
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->pluck('name', 'id');

        return [$categories, $costCenters, $locations, $suppliers];
    }

    protected function validateData(Request $request, Customer $customer, ?int $assetId = null): array
    {
        $uniqueArticle = 'unique:assets,article_code,NULL,id,customer_id,' . $customer->id;
        $uniqueLabel = 'unique:assets,label_code,NULL,id,customer_id,' . $customer->id;
        $uniqueTid = 'unique:assets,rfid_tid,NULL,id,customer_id,' . $customer->id;

        if ($assetId) {
            $uniqueArticle = 'unique:assets,article_code,' . $assetId . ',id,customer_id,' . $customer->id;
            $uniqueLabel = 'unique:assets,label_code,' . $assetId . ',id,customer_id,' . $customer->id;
            $uniqueTid = 'unique:assets,rfid_tid,' . $assetId . ',id,customer_id,' . $customer->id;
        }

        $data = $request->validate([
            'asset_category_id' => ['required', Rule::exists('asset_categories', 'id')->where('customer_id', $customer->id)],
            'asset_cost_center_id' => ['nullable', Rule::exists('asset_cost_centers', 'id')->where('customer_id', $customer->id)],
            'asset_location_id' => ['nullable', Rule::exists('asset_locations', 'id')->where('customer_id', $customer->id)],
            'vendor_supplier_id' => ['nullable', 'exists:vendor_suppliers,id'],
            'article_code' => ['required', 'string', 'max:255', $uniqueArticle],
            'label_code' => ['required', 'string', 'max:255', $uniqueLabel],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'has_rfid_tag' => ['boolean'],
            'rfid_tid' => ['nullable', 'string', 'max:255', $uniqueTid],
            'rfid_epc' => ['nullable', 'string', 'max:255'],
            'acquired_at' => ['nullable', 'date'],
            'attributes' => ['nullable'],
        ]);

        $data['has_rfid_tag'] = $request->boolean('has_rfid_tag');

        if (!$data['rfid_epc'] && $data['has_rfid_tag']) {
            $data['rfid_epc'] = optional(
                $customer->assetCategories()->find($data['asset_category_id'])
            )->rfid_epc;
        }

        if (!$data['has_rfid_tag']) {
            $data['rfid_tid'] = null;
            $data['rfid_epc'] = null;
        }

        $attributesInput = $request->input('attributes');
        if (is_string($attributesInput) && trim($attributesInput) !== '') {
            $decoded = json_decode($attributesInput, true);
            $data['attributes'] = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $attributesInput];
        } elseif (is_array($attributesInput)) {
            $data['attributes'] = $attributesInput;
        } else {
            $data['attributes'] = [];
        }

        return $data;
    }

    protected function ensureSameCustomer(Customer $customer, Asset $asset): void
    {
        abort_unless($asset->customer_id === $customer->id, 404);
    }
}

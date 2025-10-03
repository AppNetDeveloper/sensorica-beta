<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetCostCenter;
use App\Models\AssetLocation;
use App\Models\AssetReceipt;
use App\Models\AssetReceiptLine;
use App\Models\Customer;
use App\Models\VendorOrder;
use App\Models\VendorOrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetReceiptController extends Controller
{
    public function create(Customer $customer, VendorOrder $vendorOrder)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $this->authorize('create', [AssetReceipt::class, $customer]);

        $vendorOrder->load(['supplier', 'lines.item', 'lines.receiptLines']);

        $pendingLines = $vendorOrder->lines->filter(fn ($line) => $line->quantity_pending > 0);

        if ($pendingLines->isEmpty()) {
            return redirect()->route('customers.vendor-orders.show', [$customer, $vendorOrder])
                ->with('info', __('All lines are fully received.'));
        }

        $categories = $customer->assetCategories()->orderBy('name')->pluck('name', 'id');
        $costCenters = $customer->assetCostCenters()->orderBy('name')->pluck('name', 'id');
        $locations = $customer->assetLocations()->orderBy('name')->pluck('name', 'id');

        return view('customers.vendor-orders.receipts.create', [
            'customer' => $customer,
            'vendorOrder' => $vendorOrder,
            'pendingLines' => $pendingLines,
            'assetCategories' => $categories,
            'assetCostCenters' => $costCenters,
            'assetLocations' => $locations,
        ]);
    }

    public function store(Request $request, Customer $customer, VendorOrder $vendorOrder)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $this->authorize('create', [AssetReceipt::class, $customer]);

        $vendorOrder->load(['lines.item', 'lines.receiptLines']);

        $data = $this->validateRequest($request);

        $linesData = collect($data['lines'] ?? [])
            ->filter(fn ($line) => $line['quantity_received'] > 0);

        if ($linesData->isEmpty()) {
            return back()->withInput()->withErrors(['lines' => __('You must register at least one quantity greater than zero.')]);
        }

        DB::transaction(function () use ($customer, $vendorOrder, $data, $linesData) {
            $receipt = $customer->assetReceipts()->create([
                'vendor_order_id' => $vendorOrder->id,
                'reference' => $data['reference'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'received_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($linesData as $index => $lineInput) {
                /** @var VendorOrderLine $orderLine */
                $orderLine = $vendorOrder->lines->firstWhere('id', (int)$lineInput['vendor_order_line_id']);
                abort_unless($orderLine, 404);

                $pending = (float)$orderLine->quantity_pending;
                $quantity = (float)$lineInput['quantity_received'];
                abort_if($quantity > $pending + 1e-6, 400, __('Received quantity exceeds pending amount.'));

                /** @var AssetReceiptLine $receiptLine */
                $receiptLine = $receipt->lines()->create([
                    'vendor_order_line_id' => $orderLine->id,
                    'quantity_received' => $quantity,
                    'unit_cost' => $lineInput['unit_cost'] ?? $orderLine->unit_price,
                    'metadata' => $lineInput['metadata'] ?? null,
                ]);

                $orderLine->quantity_received = $orderLine->receiptLines()->sum('quantity_received');
                $orderLine->save();

                $createAsset = !empty($lineInput['create_asset']);
                if ($createAsset) {
                    $asset = $this->createAssetFromReceiptLine(
                        $customer,
                        $vendorOrder,
                        $receipt,
                        $orderLine,
                        $lineInput,
                        $quantity,
                        $data
                    );

                    $receiptLine->asset_id = $asset->id;
                    $receiptLine->save();
                }
            }

            $this->refreshOrderStatus($vendorOrder);
        });

        return redirect()->route('customers.vendor-orders.show', [$customer, $vendorOrder])
            ->with('success', __('Receipt registered successfully.'));
    }

    public function show(Customer $customer, VendorOrder $vendorOrder, AssetReceipt $receipt)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $this->authorize('view', $receipt);
        abort_unless($receipt->vendor_order_id === $vendorOrder->id, 404);

        $receipt->load(['lines.vendorOrderLine.item', 'lines.asset', 'receiver']);

        return view('customers.vendor-orders.receipts.show', compact('customer', 'vendorOrder', 'receipt'));
    }

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vendor_order_line_id' => ['required', 'exists:vendor_order_lines,id'],
            'lines.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.metadata' => ['nullable', 'array'],
            'lines.*.create_asset' => ['nullable', 'boolean'],
            'lines.*.asset_category_id' => ['nullable', 'integer'],
            'lines.*.asset_location_id' => ['nullable', 'integer'],
            'lines.*.asset_cost_center_id' => ['nullable', 'integer'],
            'lines.*.article_code' => ['nullable', 'string', 'max:255'],
            'lines.*.asset_description' => ['nullable', 'string'],
        ]);
    }

    protected function refreshOrderStatus(VendorOrder $vendorOrder): void
    {
        $vendorOrder->load('lines.receiptLines');
        $hasPending = $vendorOrder->lines->contains(fn ($line) => $line->quantity_pending > 0);

        $vendorOrder->status = $hasPending ? 'partially_received' : 'received';
        $vendorOrder->save();
    }

    protected function ensureSameCustomer(Customer $customer, VendorOrder $vendorOrder): void
    {
        abort_unless($vendorOrder->customer_id === $customer->id, 404);
    }

    protected function createAssetFromReceiptLine(
        Customer $customer,
        VendorOrder $vendorOrder,
        AssetReceipt $receipt,
        VendorOrderLine $orderLine,
        array $lineInput,
        float $quantity,
        array $receiptData
    ): Asset {
        $categoryId = $lineInput['asset_category_id'] ?? null;
        if (!$categoryId) {
            throw ValidationException::withMessages([
                "lines.{$orderLine->id}.asset_category_id" => __('Please select a category to create the asset.'),
            ]);
        }

        $category = AssetCategory::where('customer_id', $customer->id)->find($categoryId);
        if (!$category) {
            throw ValidationException::withMessages([
                "lines.{$orderLine->id}.asset_category_id" => __('Invalid category selected.'),
            ]);
        }

        $locationId = $lineInput['asset_location_id'] ?? null;
        if ($locationId) {
            $locationExists = AssetLocation::where('customer_id', $customer->id)->where('id', $locationId)->exists();
            if (!$locationExists) {
                throw ValidationException::withMessages([
                    "lines.{$orderLine->id}.asset_location_id" => __('Invalid location selected.'),
                ]);
            }
        }

        $costCenterId = $lineInput['asset_cost_center_id'] ?? null;
        if ($costCenterId) {
            $costCenterExists = AssetCostCenter::where('customer_id', $customer->id)->where('id', $costCenterId)->exists();
            if (!$costCenterExists) {
                throw ValidationException::withMessages([
                    "lines.{$orderLine->id}.asset_cost_center_id" => __('Invalid cost center selected.'),
                ]);
            }
        }

        $articleCode = $lineInput['article_code'] ?? $this->generateArticleCode($customer, $vendorOrder, $orderLine);
        if ($customer->assets()->where('article_code', $articleCode)->exists()) {
            throw ValidationException::withMessages([
                "lines.{$orderLine->id}.article_code" => __('Article code already exists for this customer.'),
            ]);
        }

        $labelCode = $this->generateLabelCode($customer, $vendorOrder, $orderLine);

        $description = $lineInput['asset_description']
            ?? $orderLine->description
            ?? __('Asset generated from order :reference', ['reference' => $vendorOrder->reference]);

        $asset = $customer->assets()->create([
            'asset_category_id' => $category->id,
            'asset_cost_center_id' => $costCenterId,
            'asset_location_id' => $locationId,
            'vendor_supplier_id' => $vendorOrder->vendor_supplier_id,
            'article_code' => $articleCode,
            'label_code' => $labelCode,
            'description' => $description,
            'status' => 'active',
            'has_rfid_tag' => false,
            'rfid_tid' => null,
            'rfid_epc' => null,
            'acquired_at' => $receiptData['received_at'] ?? now(),
            'attributes' => [
                'quantity_received' => $quantity,
            ],
            'metadata' => [
                'source' => 'vendor_order_receipt',
                'vendor_order_id' => $vendorOrder->id,
                'vendor_order_line_id' => $orderLine->id,
                'asset_receipt_id' => $receipt->id,
            ],
        ]);

        return $asset;
    }

    protected function generateLabelCode(Customer $customer, VendorOrder $vendorOrder, VendorOrderLine $orderLine): string
    {
        do {
            $code = 'LBL-' . strtoupper(Str::random(10));
        } while ($customer->assets()->where('label_code', $code)->exists());

        return $code;
    }

    protected function generateArticleCode(Customer $customer, VendorOrder $vendorOrder, VendorOrderLine $orderLine): string
    {
        $prefix = strtoupper(Str::slug($vendorOrder->reference ?: 'order', '-'));
        $base = $prefix . '-' . $orderLine->id;
        $code = $base;
        $counter = 1;

        while ($customer->assets()->where('article_code', $code)->exists()) {
            $code = $base . '-' . $counter;
            $counter++;
        }

        return $code;
    }
}

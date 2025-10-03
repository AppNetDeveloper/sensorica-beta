<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\VendorItem;
use App\Models\VendorOrder;
use App\Models\VendorOrderLine;
use App\Models\VendorSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorOrderController extends Controller
{
    private const STATUSES = [
        'draft',
        'pending_approval',
        'approved',
        'sent',
        'partially_received',
        'received',
        'cancelled',
    ];

    public function index(Customer $customer)
    {
        $orders = $customer->vendorOrders()
            ->with(['supplier', 'lines'])
            ->latest('requested_at')
            ->get();

        return view('customers.vendor-orders.index', compact('customer', 'orders'));
    }

    public function create(Customer $customer)
    {
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->pluck('name', 'id');
        $items = $customer->vendorItems()->orderBy('name')->get();
        $statuses = self::STATUSES;

        return view('customers.vendor-orders.create', compact('customer', 'suppliers', 'items', 'statuses'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $this->validateRequest($request, $customer);
        $data['requested_by'] = Auth::id();
        $data['requested_at'] = now();

        DB::transaction(function () use ($customer, $data) {
            $lines = $data['lines'];
            unset($data['lines']);

            $order = $customer->vendorOrders()->create($data);

            $this->storeLines($order, $lines);
        });

        return redirect()->route('customers.vendor-orders.index', $customer)
            ->with('success', __('Vendor order created successfully.'));
    }

    public function show(Customer $customer, VendorOrder $vendorOrder)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $vendorOrder->load([
            'supplier',
            'lines.item',
            'lines.receiptLines',
            'requester',
            'approver',
            'assetReceipts.lines.vendorOrderLine.item',
            'assetReceipts.receiver',
        ]);

        return view('customers.vendor-orders.show', compact('customer', 'vendorOrder'));
    }

    public function edit(Customer $customer, VendorOrder $vendorOrder)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $vendorOrder->load('lines');
        $suppliers = $customer->vendorSuppliers()->orderBy('name')->pluck('name', 'id');
        $items = $customer->vendorItems()->orderBy('name')->get();
        $statuses = self::STATUSES;

        return view('customers.vendor-orders.edit', compact('customer', 'vendorOrder', 'suppliers', 'items', 'statuses'));
    }

    public function update(Request $request, Customer $customer, VendorOrder $vendorOrder)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $data = $this->validateRequest($request, $customer, $vendorOrder->id);

        DB::transaction(function () use ($vendorOrder, $data) {
            $lines = $data['lines'];
            unset($data['lines']);

            $vendorOrder->update($data);
            $vendorOrder->lines()->delete();
            $this->storeLines($vendorOrder, $lines);
        });

        return redirect()->route('customers.vendor-orders.show', [$customer, $vendorOrder])
            ->with('success', __('Vendor order updated successfully.'));
    }

    public function destroy(Customer $customer, VendorOrder $vendorOrder)
    {
        $this->ensureSameCustomer($customer, $vendorOrder);
        $vendorOrder->delete();

        return redirect()->route('customers.vendor-orders.index', $customer)
            ->with('success', __('Vendor order deleted successfully.'));
    }

    private function validateRequest(Request $request, Customer $customer, ?int $orderId = null): array
    {
        $uniqueRule = 'unique:vendor_orders,reference';
        if ($orderId) {
            $uniqueRule .= ',' . $orderId;
        }

        $validated = $request->validate([
            'vendor_supplier_id' => ['required', 'exists:vendor_suppliers,id'],
            'reference' => ['required', 'string', 'max:255', $uniqueRule],
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'currency' => ['required', 'string', 'max:3'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vendor_item_id' => ['nullable', 'exists:vendor_items,id'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity_ordered' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $supplier = VendorSupplier::findOrFail($validated['vendor_supplier_id']);
        abort_unless($supplier->customer_id === $customer->id, 404);

        foreach ($validated['lines'] as &$line) {
            if (!empty($line['vendor_item_id'])) {
                $item = VendorItem::findOrFail($line['vendor_item_id']);
                abort_unless($item->customer_id === $customer->id, 404);
            }
            $line['quantity_received'] = $line['quantity_received'] ?? 0;
            $line['unit_price'] = $line['unit_price'] ?? 0;
            $line['tax_rate'] = $line['tax_rate'] ?? 0;
        }

        return $validated;
    }

    private function storeLines(VendorOrder $order, array $lines): void
    {
        $total = 0;
        foreach ($lines as $line) {
            $createdLine = $order->lines()->create([
                'vendor_item_id' => $line['vendor_item_id'] ?? null,
                'description' => $line['description'],
                'quantity_ordered' => $line['quantity_ordered'],
                'quantity_received' => $line['quantity_received'] ?? 0,
                'unit_price' => $line['unit_price'] ?? 0,
                'tax_rate' => $line['tax_rate'] ?? 0,
                'status' => $line['status'] ?? 'open',
                'metadata' => $line['metadata'] ?? null,
            ]);

            $total += $this->computeLineTotal($createdLine);
        }

        $order->update(['total_amount' => $total]);
    }

    private function computeLineTotal(VendorOrderLine $line): float
    {
        $base = (float)$line->quantity_ordered * (float)$line->unit_price;
        $taxMultiplier = 1 + ((float)$line->tax_rate / 100);

        return round($base * $taxMultiplier, 2);
    }

    private function ensureSameCustomer(Customer $customer, VendorOrder $order): void
    {
        abort_unless($order->customer_id === $customer->id, 404);
    }
}

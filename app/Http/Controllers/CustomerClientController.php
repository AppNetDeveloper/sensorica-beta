<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerClient;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CustomerClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        $this->middleware('permission:customer-clients-view')->only(['index']);
        $this->middleware('permission:customer-clients-create')->only(['create','store']);
        $this->middleware('permission:customer-clients-edit')->only(['edit','update']);
        $this->middleware('permission:customer-clients-delete')->only(['destroy']);
        // Export requires view, import requires create
        $this->middleware('permission:customer-clients-view')->only(['export']);
        $this->middleware('permission:customer-clients-create')->only(['import']);
    }

    public function index(Request $request, Customer $customer)
    {
        if ($request->ajax()) {
            $query = CustomerClient::where('customer_id', $customer->id)->orderByDesc('id');
            return DataTables::of($query)
                ->editColumn('active', function ($c) {
                    return $c->active ? '<span class="badge bg-success">' . __('Active') . '</span>' : '<span class="badge bg-secondary">' . __('Inactive') . '</span>';
                })
                ->addColumn('actions', function ($c) use ($customer) {
                    $editUrl = route('customers.clients.edit', [$customer->id, $c->id]);
                    $deleteUrl = route('customers.clients.destroy', [$customer->id, $c->id]);
                    $csrf = csrf_token();
                    $btns = '';
                    if (auth()->user()->can('customer-clients-edit')) {
                        $btns .= "<a href='{$editUrl}' class='btn btn-sm btn-outline-primary me-1'>" . __('Edit') . "</a>";
                    }
                    if (auth()->user()->can('customer-clients-delete')) {
                        $btns .= "<form action='{$deleteUrl}' method='POST' style='display:inline' onsubmit=\"return confirm('" . __('Are you sure?') . "')\">"
                            . "<input type='hidden' name='_token' value='{$csrf}'>"
                            . "<input type='hidden' name='_method' value='DELETE'>"
                            . "<button type='submit' class='btn btn-sm btn-outline-danger'>" . __('Delete') . "</button></form>";
                    }
                    return $btns ?: '-';
                })
                ->rawColumns(['active','actions'])
                ->make(true);
        }
        return view('customers.clients.index', compact('customer'));
    }

    // GET: export CSV of clients
    public function export(Customer $customer)
    {
        $filename = 'customer_clients_' . $customer->id . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $columns = ['name','address','phone','email','tax_id','active'];
        $callback = function() use ($customer, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $columns);
            CustomerClient::where('customer_id', $customer->id)->orderBy('id')->chunk(500, function($chunk) use ($handle, $columns){
                foreach ($chunk as $c) {
                    fputcsv($handle, [
                        $c->name,
                        $c->address,
                        $c->phone,
                        $c->email,
                        $c->tax_id,
                        $c->active ? 1 : 0,
                    ]);
                }
            });
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // POST: import CSV file with columns: name,address,phone,email,tax_id,active
    public function import(Request $request, Customer $customer)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', __('Unable to read file'));
        }
        // Detect header (case-insensitive, partial match) to avoid importing it as data
        $header = fgetcsv($handle);
        $expected = ['name','address','phone','email','tax_id','active'];
        $map = array_map(fn($h)=> strtolower(trim((string)$h)), $header ?: []);
        // Consider header if at least 3 expected columns are present in the first row
        $isLikelyHeader = !empty($map) && count(array_intersect($map, $expected)) >= 3;
        if (!$isLikelyHeader) {
            // First row seems to be data; rewind so we don't lose it
            rewind($handle);
        }
        $created = 0; $updated = 0; $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            // skip empty lines
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue;
            $name = trim((string)($row[0] ?? ''));
            if ($name === '') { $skipped++; continue; }
            $payload = [
                'address' => trim((string)($row[1] ?? '')) ?: null,
                'phone' => trim((string)($row[2] ?? '')) ?: null,
                'email' => trim((string)($row[3] ?? '')) ?: null,
                'tax_id' => trim((string)($row[4] ?? '')) ?: null,
                'active' => (int) (isset($row[5]) ? (in_array(strtolower((string)$row[5]), ['1','true','yes','si']) ? 1 : 0) : 1),
            ];
            // Upsert by (customer_id, name)
            $existing = CustomerClient::where('customer_id', $customer->id)->where('name', $name)->first();
            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                CustomerClient::create(array_merge($payload, [
                    'customer_id' => $customer->id,
                    'name' => $name,
                ]));
                $created++;
            }
        }
        fclose($handle);
        return back()->with('success', __('Import completed') . ": +{$created} / ~{$updated} / -{$skipped}");
    }

    public function create(Customer $customer)
    {
        $routeNames = \App\Models\RouteName::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('name')->get(['id','name']);
        return view('customers.clients.create', compact('customer','routeNames'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'route_name_id' => 'nullable|exists:route_names,id',
            'active' => 'nullable|boolean',
        ]);
        $data['customer_id'] = $customer->id;
        $data['active'] = (int)($data['active'] ?? 1);
        CustomerClient::create($data);
        return redirect()->route('customers.clients.index', $customer->id)->with('success', __('Client created successfully'));
    }

    public function edit(Customer $customer, CustomerClient $client)
    {
        abort_unless($client->customer_id === $customer->id, 404);
        $routeNames = \App\Models\RouteName::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('name')->get(['id','name']);
        return view('customers.clients.edit', ['customer' => $customer, 'client' => $client, 'routeNames' => $routeNames]);
    }

    public function update(Request $request, Customer $customer, CustomerClient $client)
    {
        abort_unless($client->customer_id === $customer->id, 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'route_name_id' => 'nullable|exists:route_names,id',
            'active' => 'nullable|boolean',
        ]);
        $data['active'] = (int)($data['active'] ?? 0);
        $client->update($data);
        return redirect()->route('customers.clients.index', $customer->id)->with('success', __('Client updated successfully'));
    }

    public function destroy(Customer $customer, CustomerClient $client)
    {
        abort_unless($client->customer_id === $customer->id, 404);
        $client->delete();
        return redirect()->route('customers.clients.index', $customer->id)->with('success', __('Client deleted successfully'));
    }
}

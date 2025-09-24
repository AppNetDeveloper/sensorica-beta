<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FleetVehicle;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FleetVehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        // Permisos específicos del módulo Flota
        $this->middleware('permission:fleet-view')->only(['index']);
        $this->middleware('permission:fleet-create')->only(['create', 'store']);
        $this->middleware('permission:fleet-edit')->only(['edit', 'update']);
        $this->middleware('permission:fleet-delete')->only(['destroy']);
        // Export/Import
        $this->middleware('permission:fleet-view')->only(['export']);
        $this->middleware('permission:fleet-create')->only(['import']);
    }

    public function index(Request $request, Customer $customer)
    {
        if ($request->ajax()) {
            $query = FleetVehicle::where('customer_id', $customer->id);

            // Server-side filters
            $today = now()->startOfDay();
            $itvFilter = $request->get('itv_filter'); // expired | next30 | next60
            $insFilter = $request->get('insurance_filter'); // expired | next30 | next60
            $activeFilter = $request->get('active_filter'); // active | inactive

            if ($activeFilter === 'active') {
                $query->where('active', 1);
            } elseif ($activeFilter === 'inactive') {
                $query->where('active', 0);
            }

            if ($itvFilter === 'expired') {
                $query->whereNotNull('itv_expires_at')->where('itv_expires_at', '<', $today);
            } elseif ($itvFilter === 'next30') {
                $query->whereNotNull('itv_expires_at')->whereBetween('itv_expires_at', [$today, (clone $today)->addDays(30)]);
            } elseif ($itvFilter === 'next60') {
                $query->whereNotNull('itv_expires_at')->whereBetween('itv_expires_at', [$today, (clone $today)->addDays(60)]);
            }

            if ($insFilter === 'expired') {
                $query->whereNotNull('insurance_expires_at')->where('insurance_expires_at', '<', $today);
            } elseif ($insFilter === 'next30') {
                $query->whereNotNull('insurance_expires_at')->whereBetween('insurance_expires_at', [$today, (clone $today)->addDays(30)]);
            } elseif ($insFilter === 'next60') {
                $query->whereNotNull('insurance_expires_at')->whereBetween('insurance_expires_at', [$today, (clone $today)->addDays(60)]);
            }

            $query->orderByDesc('id');
            return DataTables::of($query)
                ->addColumn('volume_m3', function ($v) {
                    return $v->volume_m3 !== null ? number_format($v->volume_m3, 3, ',', '.') : '-';
                })
                ->editColumn('itv_expires_at', function ($v) use ($today) {
                    if (!$v->itv_expires_at) return '-';
                    $date = $v->itv_expires_at->format('Y-m-d');
                    $badge = 'bg-success';
                    if ($v->itv_expires_at->lt($today)) {
                        $badge = 'bg-danger';
                    } elseif ($v->itv_expires_at->lte((clone $today)->addDays(30))) {
                        $badge = 'bg-warning text-dark';
                    }
                    return "<span class='badge {$badge}'>{$date}</span>";
                })
                ->editColumn('insurance_expires_at', function ($v) use ($today) {
                    if (!$v->insurance_expires_at) return '-';
                    $date = $v->insurance_expires_at->format('Y-m-d');
                    $badge = 'bg-success';
                    if ($v->insurance_expires_at->lt($today)) {
                        $badge = 'bg-danger';
                    } elseif ($v->insurance_expires_at->lte((clone $today)->addDays(30))) {
                        $badge = 'bg-warning text-dark';
                    }
                    return "<span class='badge {$badge}'>{$date}</span>";
                })
                ->addColumn('actions', function ($v) use ($customer) {
                    $editUrl = route('customers.fleet-vehicles.edit', [$customer->id, $v->id]);
                    $deleteUrl = route('customers.fleet-vehicles.destroy', [$customer->id, $v->id]);
                    $csrf = csrf_token();
                    $btns = '';
                    if (auth()->user()->can('fleet-edit')) {
                        $btns .= "<a href='{$editUrl}' class='btn btn-sm btn-outline-primary me-1'>" . __('Edit') . "</a>";
                    }
                    if (auth()->user()->can('fleet-delete')) {
                        $btns .= "<form action='{$deleteUrl}' method='POST' style='display:inline' onsubmit=\"return confirm('" . __('Are you sure?') . "')\">"
                           . "<input type='hidden' name='_token' value='{$csrf}'>"
                           . "<input type='hidden' name='_method' value='DELETE'>"
                           . "<button type='submit' class='btn btn-sm btn-outline-danger'>" . __('Delete') . "</button></form>";
                    }
                    return $btns ?: '-';
                })
                ->editColumn('active', function ($v) {
                    return $v->active ? '<span class="badge bg-success">' . __('Active') . '</span>' : '<span class="badge bg-secondary">' . __('Inactive') . '</span>';
                })
                ->rawColumns(['actions','active','itv_expires_at','insurance_expires_at'])
                ->make(true);
        }

        return view('customers.fleet.index', compact('customer'));
    }

    public function create(Customer $customer)
    {
        $routeNames = \App\Models\RouteName::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('name')->get(['id','name']);
        return view('customers.fleet.create', compact('customer','routeNames'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'vehicle_type' => 'nullable|string|max:50',
            'plate' => 'required|string|max:50',
            'weight_kg' => 'nullable|numeric',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'capacity_kg' => 'nullable|numeric',
            'fuel_type' => 'nullable|string|max:50',
            'itv_expires_at' => 'nullable|date',
            'insurance_expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'default_route_name_id' => 'nullable|exists:route_names,id',
            'active' => 'nullable|boolean',
        ]);
        // Normalizar select vacío a NULL para evitar violación de FK (MySQL convierte '' a 0)
        if (array_key_exists('default_route_name_id', $data) && $data['default_route_name_id'] === '') {
            $data['default_route_name_id'] = null;
        }
        $data['customer_id'] = $customer->id;
        $data['active'] = (int)($data['active'] ?? 1);
        FleetVehicle::create($data);

        return redirect()->route('customers.fleet-vehicles.index', $customer->id)
            ->with('success', __('Vehicle created successfully'));
    }

    public function edit(Customer $customer, FleetVehicle $fleet_vehicle)
    {
        abort_unless($fleet_vehicle->customer_id === $customer->id, 404);
        $routeNames = \App\Models\RouteName::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('name')->get(['id','name']);
        return view('customers.fleet.edit', [
            'customer' => $customer,
            'vehicle' => $fleet_vehicle,
            'routeNames' => $routeNames,
        ]);
    }

    public function update(Request $request, Customer $customer, FleetVehicle $fleet_vehicle)
    {
        abort_unless($fleet_vehicle->customer_id === $customer->id, 404);
        $data = $request->validate([
            'vehicle_type' => 'nullable|string|max:50',
            'plate' => 'required|string|max:50',
            'weight_kg' => 'nullable|numeric',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'default_route_name_id' => 'nullable|exists:route_names,id',
            'active' => 'nullable|boolean',
        ]);
        // Normalizar select vacío a NULL para evitar violación de FK (MySQL convierte '' a 0)
        if (array_key_exists('default_route_name_id', $data) && $data['default_route_name_id'] === '') {
            $data['default_route_name_id'] = null;
        }
        $data['active'] = (int)($data['active'] ?? 0);
        $fleet_vehicle->update($data);

        return redirect()->route('customers.fleet-vehicles.index', $customer->id)
            ->with('success', __('Vehicle updated successfully'));
    }

    public function destroy(Customer $customer, FleetVehicle $fleet_vehicle)
    {
        abort_unless($fleet_vehicle->customer_id === $customer->id, 404);
        $fleet_vehicle->delete();
        return redirect()->route('customers.fleet-vehicles.index', $customer->id)
            ->with('success', __('Vehicle deleted successfully'));
    }

    // GET: export CSV of fleet vehicles
    public function export(Customer $customer)
    {
        $filename = 'fleet_vehicles_' . $customer->id . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $columns = [
            'vehicle_type','plate','weight_kg','length_cm','width_cm','height_cm',
            'capacity_kg','fuel_type','itv_expires_at','insurance_expires_at','notes','active'
        ];
        $callback = function() use ($customer, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $columns);
            FleetVehicle::where('customer_id', $customer->id)->orderBy('id')->chunk(500, function($chunk) use ($handle) {
                foreach ($chunk as $v) {
                    fputcsv($handle, [
                        $v->vehicle_type,
                        $v->plate,
                        $v->weight_kg,
                        $v->length_cm,
                        $v->width_cm,
                        $v->height_cm,
                        $v->capacity_kg,
                        $v->fuel_type,
                        optional($v->itv_expires_at)->format('Y-m-d'),
                        optional($v->insurance_expires_at)->format('Y-m-d'),
                        $v->notes,
                        $v->active ? 1 : 0,
                    ]);
                }
            });
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // POST: import CSV file with columns: vehicle_type,plate,weight_kg,length_cm,width_cm,height_cm,capacity_kg,fuel_type,itv_expires_at,insurance_expires_at,notes,active
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
        // Detect header row (case-insensitive, partial match) to avoid importing it as data
        $header = fgetcsv($handle);
        $expected = ['vehicle_type','plate','weight_kg','length_cm','width_cm','height_cm','capacity_kg','fuel_type','itv_expires_at','insurance_expires_at','notes','active'];
        $map = array_map(fn($h)=> strtolower(trim((string)$h)), $header ?: []);
        $isLikelyHeader = !empty($map) && count(array_intersect($map, $expected)) >= 4;
        if (!$isLikelyHeader) { rewind($handle); }
        $created = 0; $updated = 0; $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue;
            $plate = trim((string)($row[1] ?? ''));
            if ($plate === '') { $skipped++; continue; }
            $payload = [
                'vehicle_type' => trim((string)($row[0] ?? '')) ?: null,
                'weight_kg' => is_numeric($row[2] ?? null) ? (float)$row[2] : null,
                'length_cm' => is_numeric($row[3] ?? null) ? (float)$row[3] : null,
                'width_cm' => is_numeric($row[4] ?? null) ? (float)$row[4] : null,
                'height_cm' => is_numeric($row[5] ?? null) ? (float)$row[5] : null,
                'capacity_kg' => is_numeric($row[6] ?? null) ? (float)$row[6] : null,
                'fuel_type' => trim((string)($row[7] ?? '')) ?: null,
                'itv_expires_at' => ($row[8] ?? '') ? date('Y-m-d', strtotime((string)$row[8])) : null,
                'insurance_expires_at' => ($row[9] ?? '') ? date('Y-m-d', strtotime((string)$row[9])) : null,
                'notes' => trim((string)($row[10] ?? '')) ?: null,
                'active' => (int) (isset($row[11]) ? (in_array(strtolower((string)$row[11]), ['1','true','yes','si']) ? 1 : 0) : 1),
            ];
            $existing = FleetVehicle::where('customer_id', $customer->id)->where('plate', $plate)->first();
            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                FleetVehicle::create(array_merge($payload, [
                    'customer_id' => $customer->id,
                    'plate' => $plate,
                ]));
                $created++;
            }
        }
        fclose($handle);
        return back()->with('success', __('Import completed') . ": +{$created} / ~{$updated} / -{$skipped}");
    }
}

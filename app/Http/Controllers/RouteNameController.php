<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RouteName;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RouteNameController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        $this->middleware('permission:route-names-view')->only(['index']);
        $this->middleware('permission:route-names-create')->only(['create','store']);
        $this->middleware('permission:route-names-edit')->only(['edit','update']);
        $this->middleware('permission:route-names-delete')->only(['destroy']);
    }

    public function index(Request $request, Customer $customer)
    {
        if ($request->ajax()) {
            $query = RouteName::where('customer_id', $customer->id)->orderByDesc('id');
            return DataTables::of($query)
                ->editColumn('active', function ($r) {
                    return $r->active
                        ? '<span class="badge bg-success">' . __('Active') . '</span>'
                        : '<span class="badge bg-secondary">' . __('Inactive') . '</span>';
                })
                ->addColumn('actions', function ($r) use ($customer) {
                    $editUrl = route('customers.route-names.edit', [$customer->id, $r->id]);
                    $deleteUrl = route('customers.route-names.destroy', [$customer->id, $r->id]);
                    $csrf = csrf_token();
                    $btns = '';
                    if (auth()->user()->can('route-names-edit')) {
                        $btns .= "<a href='{$editUrl}' class='btn btn-sm btn-outline-primary me-1'>" . __('Edit') . "</a>";
                    }
                    if (auth()->user()->can('route-names-delete')) {
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
        return view('customers.route-names.index', compact('customer'));
    }

    public function create(Customer $customer)
    {
        return view('customers.route-names.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'days' => 'nullable|array',
            'days.*' => 'integer|in:1,2,4,8,16,32,64',
        ]);
        $data['customer_id'] = $customer->id;
        $data['active'] = (int)($data['active'] ?? 1);
        // Compute days_mask from days[] checkboxes
        $days = $data['days'] ?? [];
        unset($data['days']);
        $data['days_mask'] = array_reduce($days, function($carry, $v){ return $carry + (int)$v; }, 0);
        RouteName::create($data);
        return redirect()->route('customers.route-names.index', $customer->id)->with('success', __('Route name created successfully'));
    }

    public function edit(Customer $customer, RouteName $route_name)
    {
        abort_unless($route_name->customer_id === $customer->id, 404);
        return view('customers.route-names.edit', ['customer' => $customer, 'routeName' => $route_name]);
    }

    public function update(Request $request, Customer $customer, RouteName $route_name)
    {
        abort_unless($route_name->customer_id === $customer->id, 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'days' => 'nullable|array',
            'days.*' => 'integer|in:1,2,4,8,16,32,64',
        ]);
        $data['active'] = (int)($data['active'] ?? 0);
        $days = $data['days'] ?? [];
        unset($data['days']);
        $data['days_mask'] = array_reduce($days, function($carry, $v){ return $carry + (int)$v; }, 0);
        $route_name->update($data);
        return redirect()->route('customers.route-names.index', $customer->id)->with('success', __('Route name updated successfully'));
    }

    public function destroy(Customer $customer, RouteName $route_name)
    {
        abort_unless($route_name->customer_id === $customer->id, 404);
        $route_name->delete();
        return redirect()->route('customers.route-names.index', $customer->id)->with('success', __('Route name deleted successfully'));
    }
}

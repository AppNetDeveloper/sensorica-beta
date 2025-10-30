<?php

namespace App\Http\Controllers;

use App\Facades\UtilityFacades;
use App\Models\OptimalSensorTime;
use App\Models\Customer;
use App\Models\Sensor;
use App\Models\ProductList;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OptimalSensorTimeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:productionline-kanban')->only(['index']);
        $this->middleware('permission:optimal-sensor-times-edit')->only(['edit', 'update']);
        $this->middleware('permission:optimal-sensor-times-apply')->only(['apply', 'applyStore']);
        $this->middleware('permission:optimal-sensor-times-delete')->only(['destroy']);
        $this->middleware('permission:optimal-sensor-times-settings')->only(['updateSettings']);
    }

    /**
     * Display a listing of optimal sensor times for a specific customer.
     */
    public function index(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        if ($request->ajax()) {
            // Obtener todos los optimal_sensor_times relacionados con el customer
            // a través de production_lines del customer
            $productionLineIds = $customer->productionLines()->pluck('id');
            
            $query = OptimalSensorTime::with(['sensor', 'productionLine', 'productList'])
                ->whereIn('production_line_id', $productionLineIds);
            
            return DataTables::of($query)
                ->addColumn('sensor_name', function($row) {
                    return $row->sensor ? $row->sensor->name : '-';
                })
                ->addColumn('production_line_name', function($row) {
                    return $row->productionLine ? $row->productionLine->name : '-';
                })
                ->addColumn('product_name', function($row) {
                    return $row->productList ? $row->productList->name : $row->model_product;
                })
                ->addColumn('sensor_optimal_time', function($row) {
                    if ($row->sensor) {
                        return number_format($row->sensor->optimal_production_time ?? 0, 2) . ' s';
                    }
                    return '-';
                })
                ->addColumn('sensor_multiplier', function($row) {
                    if ($row->sensor) {
                        return 'x' . ($row->sensor->reduced_speed_time_multiplier ?? 2);
                    }
                    return '-';
                })
                ->addColumn('sensor_type_formatted', function($row) {
                    switch($row->sensor_type) {
                        case 0:
                            return '<span class="badge bg-primary">Sensor de conteo</span>';
                        case 1:
                            return '<span class="badge bg-info">Sensor materia prima</span>';
                        case 2:
                            return '<span class="badge bg-warning">Raw</span>';
                        case 3:
                            return '<span class="badge bg-danger">Incidente</span>';
                        default:
                            return '<span class="badge bg-secondary">N/A</span>';
                    }
                })
                ->addColumn('optimal_time_formatted', function($row) {
                    return number_format($row->optimal_time, 2) . ' s';
                })
                ->addColumn('action', function ($row) use ($customerId) {
                    $buttons = '';

                    if (auth()->user()->can('optimal-sensor-times-edit')) {
                        $editUrl = route('customers.optimal-sensor-times.edit', [$customerId, $row->id]);
                        $buttons .= "<a href='{$editUrl}' class='btn btn-sm btn-info me-1' title='" . __('Edit') . "'>
                            <i class='fas fa-edit'></i>
                        </a>";
                    }

                    if (auth()->user()->can('optimal-sensor-times-apply')) {
                        $applyUrl = route('customers.optimal-sensor-times.apply', [$customerId, $row->id]);
                        $buttons .= "<a href='{$applyUrl}' class='btn btn-sm btn-success me-1' title='" . __('Apply to Sensor') . "'>
                            <i class='fas fa-arrow-right'></i>
                        </a>";
                    }

                    if (auth()->user()->can('optimal-sensor-times-delete')) {
                        $deleteUrl = route('customers.optimal-sensor-times.destroy', [$customerId, $row->id]);
                        $buttons .= "<button class='btn btn-sm btn-danger delete-btn' data-id='{$row->id}' data-url='{$deleteUrl}' title='" . __('Delete') . "'>
                            <i class='fas fa-trash'></i>
                        </button>";
                    }

                    return $buttons;
                })
                ->rawColumns(['action', 'sensor_type_formatted'])
                ->make(true);
        }
        
        $minSampleSize = (int) env('MIN_SAMPLE_SIZE_FOR_OPTIMAL_TIME', 400);
        $canEditSettings = auth()->user()->can('optimal-sensor-times-settings');

        return view('customers.optimal-sensor-times.index', compact('customer', 'minSampleSize', 'canEditSettings'));
    }

    /**
     * Update optimal sensor time settings such as minimum sample size.
     */
    public function updateSettings(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $request->validate([
            'min_sample_size' => 'required|integer|min:1|max:100000',
        ]);

        UtilityFacades::setEnvironmentValue([
            'MIN_SAMPLE_SIZE_FOR_OPTIMAL_TIME' => $request->min_sample_size,
        ]);

        return redirect()->route('customers.optimal-sensor-times.index', $customer->id)
            ->with('success', __('Settings updated successfully.'));
    }

    /**
     * Show the form for editing the specified optimal sensor time.
     */
    public function edit($customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $optimalSensorTime = OptimalSensorTime::with(['sensor', 'productionLine', 'productList'])->findOrFail($id);
        
        // Verificar que el optimal_sensor_time pertenece a una línea de producción del customer
        $productionLineIds = $customer->productionLines()->pluck('id');
        if (!$productionLineIds->contains($optimalSensorTime->production_line_id)) {
            abort(403, 'Unauthorized access');
        }
        
        return view('customers.optimal-sensor-times.edit', compact('customer', 'optimalSensorTime'));
    }

    /**
     * Update the specified optimal sensor time in storage.
     */
    public function update(Request $request, $customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $optimalSensorTime = OptimalSensorTime::findOrFail($id);
        
        // Verificar que el optimal_sensor_time pertenece a una línea de producción del customer
        $productionLineIds = $customer->productionLines()->pluck('id');
        if (!$productionLineIds->contains($optimalSensorTime->production_line_id)) {
            abort(403, 'Unauthorized access');
        }
        
        $request->validate([
            'optimal_time' => 'required|numeric|min:0.01',
            'sensor_optimal_production_time' => 'nullable|numeric|min:0',
            'sensor_reduced_speed_multiplier' => 'nullable|numeric|min:1|max:10',
            'min_correction_percentage' => 'nullable|numeric|min:0|max:100',
            'max_correction_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        
        $optimalSensorTime->update([
            'optimal_time' => $request->optimal_time,
        ]);
        
        // Actualizar también el sensor si existe
        if ($optimalSensorTime->sensor) {
            $sensorData = [];
            
            if ($request->has('sensor_optimal_production_time')) {
                $sensorData['optimal_production_time'] = $request->sensor_optimal_production_time;
            }
            
            if ($request->has('sensor_reduced_speed_multiplier')) {
                $sensorData['reduced_speed_time_multiplier'] = $request->sensor_reduced_speed_multiplier;
            }
            
            if ($request->has('min_correction_percentage')) {
                $sensorData['min_correction_percentage'] = $request->min_correction_percentage;
            }
            
            if ($request->has('max_correction_percentage')) {
                $sensorData['max_correction_percentage'] = $request->max_correction_percentage;
            }
            
            // Actualizar los flags de configuración automática
            $sensorData['auto_optimal_time_enabled'] = $request->has('auto_optimal_time_enabled') ? 1 : 0;
            $sensorData['auto_update_sensor_optimal_time'] = $request->has('auto_update_sensor_optimal_time') ? 1 : 0;
            
            if (!empty($sensorData)) {
                $optimalSensorTime->sensor->update($sensorData);
            }
        }
        
        return redirect()->route('customers.optimal-sensor-times.index', $customerId)
            ->with('success', __('Optimal time updated successfully.'));
    }

    /**
     * Show the form for applying the optimal time to the sensor.
     */
    public function apply($customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $optimalSensorTime = OptimalSensorTime::with(['sensor', 'productionLine', 'productList'])->findOrFail($id);
        
        // Verificar que el optimal_sensor_time pertenece a una línea de producción del customer
        $productionLineIds = $customer->productionLines()->pluck('id');
        if (!$productionLineIds->contains($optimalSensorTime->production_line_id)) {
            abort(403, 'Unauthorized access');
        }
        
        return view('customers.optimal-sensor-times.apply', compact('customer', 'optimalSensorTime'));
    }

    /**
     * Apply the optimal time to the sensor and update its configuration.
     */
    public function applyStore(Request $request, $customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $optimalSensorTime = OptimalSensorTime::with('sensor')->findOrFail($id);
        
        // Verificar que el optimal_sensor_time pertenece a una línea de producción del customer
        $productionLineIds = $customer->productionLines()->pluck('id');
        if (!$productionLineIds->contains($optimalSensorTime->production_line_id)) {
            abort(403, 'Unauthorized access');
        }
        
        $request->validate([
            'auto_optimal_time_enabled' => 'nullable|boolean',
            'auto_update_sensor_optimal_time' => 'nullable|boolean',
        ]);
        
        // Aplicar el tiempo óptimo al sensor
        if ($optimalSensorTime->sensor) {
            $optimalSensorTime->sensor->update([
                'optimal_production_time' => $optimalSensorTime->optimal_time,
                'auto_optimal_time_enabled' => $request->has('auto_optimal_time_enabled') ? 1 : 0,
                'auto_update_sensor_optimal_time' => $request->has('auto_update_sensor_optimal_time') ? 1 : 0,
            ]);
        }
        
        return redirect()->route('customers.optimal-sensor-times.index', $customerId)
            ->with('success', __('Optimal time applied to sensor successfully.'));
    }

    /**
     * Remove the specified optimal sensor time from storage.
     */
    public function destroy($customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $optimalSensorTime = OptimalSensorTime::findOrFail($id);
        
        // Verificar que el optimal_sensor_time pertenece a una línea de producción del customer
        $productionLineIds = $customer->productionLines()->pluck('id');
        if (!$productionLineIds->contains($optimalSensorTime->production_line_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }
        
        $optimalSensorTime->delete();
        
        return response()->json(['success' => true, 'message' => __('Optimal time deleted successfully.')]);
    }
}

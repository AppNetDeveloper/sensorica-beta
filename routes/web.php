<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProductionLineProcessController;
use App\Http\Controllers\ProductionLineArticleController;
use App\Http\Controllers\ModualController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginSecurityController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerOriginalOrderController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ModbusController;
use Arcanedev\LogViewer\Facades\LogViewer;
use App\Http\Controllers\MonitorOeeController;
use App\Http\Controllers\SensorTransformationController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\RfidController;
use App\Http\Controllers\RfidCategoryController;
use App\Http\Controllers\RfidDeviceController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\ConfectionController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\RoleManageController;
use App\Http\Controllers\PermissionManageController;
use App\Http\Controllers\ScadaOrderController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\ProductionOrderIncidentController;
use App\Http\Controllers\QualityIncidentController;
use App\Http\Controllers\QcConfirmationWebController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ShiftManagementController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OperatorPostController;
use App\Http\Controllers\RfidPostController;
use App\Http\Controllers\RfidColorController;
use App\Http\Controllers\ScanPostController;
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\RfidBlockedController;
use App\Http\Controllers\IaPromptAdminController;
use App\Http\Controllers\AiConfigController;
use App\Http\Controllers\Api\ProductionLineInfoController;
use App\Http\Controllers\WorkCalendarController;
use App\Http\Controllers\OriginalOrderProcessFileController;
use App\Http\Controllers\ProductionOrderCallbackController;
use App\Http\Controllers\VendorSupplierController;
use App\Http\Controllers\VendorItemController;
use App\Http\Controllers\VendorOrderController;
use App\Http\Controllers\AssetCostCenterController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetLocationController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetReceiptController;
use App\Http\Controllers\ArticleFamilyController;
use App\Http\Controllers\ArticleController;

// Rutas para transportistas/conductores (fuera del grupo de customers)
Route::middleware(['auth', 'XSS'])->group(function () {
    Route::get('/my-deliveries', [\App\Http\Controllers\DeliveryController::class, 'myDeliveries'])->name('deliveries.my-deliveries');
    Route::post('/deliveries/mark-delivered', [\App\Http\Controllers\DeliveryController::class, 'markAsDelivered'])->name('deliveries.mark-delivered');
    Route::get('/deliveries/order-details/{orderId}', [\App\Http\Controllers\DeliveryController::class, 'getOrderDetails'])->name('deliveries.order-details');
});

// Maintenance Causes & Parts (by customer)
Route::middleware(['auth', 'XSS'])->group(function () {
    Route::prefix('customers/{customer}')->group(function () {
        // Causes
        Route::get('maintenance-causes', [\App\Http\Controllers\MaintenanceCauseController::class, 'index'])->name('customers.maintenance-causes.index');
        Route::get('maintenance-causes/create', [\App\Http\Controllers\MaintenanceCauseController::class, 'create'])->name('customers.maintenance-causes.create');
        Route::post('maintenance-causes', [\App\Http\Controllers\MaintenanceCauseController::class, 'store'])->name('customers.maintenance-causes.store');
        Route::get('maintenance-causes/{maintenance_cause}/edit', [\App\Http\Controllers\MaintenanceCauseController::class, 'edit'])->name('customers.maintenance-causes.edit');
        Route::put('maintenance-causes/{maintenance_cause}', [\App\Http\Controllers\MaintenanceCauseController::class, 'update'])->name('customers.maintenance-causes.update');
        Route::delete('maintenance-causes/{maintenance_cause}', [\App\Http\Controllers\MaintenanceCauseController::class, 'destroy'])->name('customers.maintenance-causes.destroy');

        // Parts
        Route::get('maintenance-parts', [\App\Http\Controllers\MaintenancePartController::class, 'index'])->name('customers.maintenance-parts.index');
        Route::get('maintenance-parts/create', [\App\Http\Controllers\MaintenancePartController::class, 'create'])->name('customers.maintenance-parts.create');
        Route::post('maintenance-parts', [\App\Http\Controllers\MaintenancePartController::class, 'store'])->name('customers.maintenance-parts.store');
        Route::get('maintenance-parts/{maintenance_part}/edit', [\App\Http\Controllers\MaintenancePartController::class, 'edit'])->name('customers.maintenance-parts.edit');
        Route::put('maintenance-parts/{maintenance_part}', [\App\Http\Controllers\MaintenancePartController::class, 'update'])->name('customers.maintenance-parts.update');
        Route::delete('maintenance-parts/{maintenance_part}', [\App\Http\Controllers\MaintenancePartController::class, 'destroy'])->name('customers.maintenance-parts.destroy');
    });
});
// Rutas para el Kanban Board
Route::post('production-orders/update-batch', [ProductionOrderController::class, 'updateBatch'])->name('production-orders.update-batch')->middleware(['auth', 'XSS']);
Route::post('production-orders/toggle-priority', [ProductionOrderController::class, 'togglePriority'])->name('production-orders.toggle-priority')->middleware(['auth', 'XSS']);
Route::post('production-orders/update-note', [ProductionOrderController::class, 'updateNote'])->name('production-orders.update-note')->middleware(['auth', 'XSS']);

// API para obtener información de la línea de producción y hora del servidor
Route::get('api/production-line-info', [ProductionLineInfoController::class, 'getInfo'])->name('api.production-line-info');


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
//LogViewer::routes();

Route::get('/debug', [DebugController::class, 'index']);

// Ruta para eliminación masiva de clientes
Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulk-delete')->middleware(['auth', 'XSS']);

// Ruta para obtener los datos de los clientes (para DataTables)
Route::get('customers/getCustomers', [CustomerController::class, 'getCustomers'])->name('customers.getCustomers');

// Ruta para mostrar el formulario de edición
Route::get('customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');

// Ruta para actualizar el cliente
Route::put('customers/{id}', [CustomerController::class, 'update'])->name('customers.update');

// Ruta para eliminar el cliente
Route::delete('customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

// Ruta para la página principal de clientes
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::resource('customers', CustomerController::class)->except(['edit', 'update', 'destroy']);

// Customer Original Orders
// Rutas específicas deben declararse ANTES del resource para evitar colisiones con {originalOrder}
Route::get('customers/{customer}/original-orders/finished-processes', [CustomerOriginalOrderController::class, 'finishedProcessesView'])
    ->name('customers.original-orders.finished-processes.view');
Route::get('customers/{customer}/original-orders/finished-processes/data', [CustomerOriginalOrderController::class, 'finishedProcessesData'])
    ->name('customers.original-orders.finished-processes.data');
Route::get('customers/{customer}/production-times', [CustomerOriginalOrderController::class, 'productionTimesView'])
    ->name('customers.production-times.view');
Route::get('customers/{customer}/production-times/data', [CustomerOriginalOrderController::class, 'productionTimesData'])
    ->name('customers.production-times.data');
Route::get('customers/{customer}/production-times/summary', [CustomerOriginalOrderController::class, 'productionTimesSummary'])
    ->name('customers.production-times.summary');
Route::get('customers/{customer}/production-times/{originalOrder}', [CustomerOriginalOrderController::class, 'productionTimesOrderDetail'])
    ->name('customers.production-times.order');

Route::post('customers/{customer}/original-orders/import', [CustomerOriginalOrderController::class, 'import'])->name('customers.original-orders.import');
Route::post('customers/{customer}/original-orders/create-cards', [CustomerOriginalOrderController::class, 'createCards'])->name('customers.original-orders.create-cards');
Route::post('customers/{customer}/original-orders/bulk-delete', [CustomerOriginalOrderController::class, 'bulkDelete'])->name('customers.original-orders.bulk-delete')->middleware(['auth', 'XSS']);
// Route resource movido al grupo de customers más abajo (líneas 166-168) para evitar duplicación

// Ruta para obtener el HTML de una fila de mapeo de campos
Route::get('customers/{customer}/field-mapping-row', [CustomerController::class, 'fieldMappingRow'])
    ->name('customers.field-mapping-row');

Route::prefix('customers')->name('customers.')->group(function () {
    Route::prefix('{customer}')->group(function () {
        Route::resource('original-orders', CustomerOriginalOrderController::class)
            ->names('original-orders')
            ->parameters(['original-orders' => 'originalOrder']);
            
        // Rutas para el organizador de órdenes y tablero Kanban
        Route::get('order-organizer', [CustomerController::class, 'showOrderOrganizer'])
            ->name('order-organizer');
            
        Route::post('kanban-filter-toggle', [CustomerController::class, 'toggleKanbanFilter'])
            ->name('kanban-filter-toggle')
            ->middleware('permission:kanban-filter-toggle');
            
        Route::get('order-kanban/{process}', [CustomerController::class, 'showOrderKanban'])
            ->name('order-kanban')
            ->where('process', '[0-9]+');

        Route::get('hourly-totals', [CustomerController::class, 'hourlyTotals'])
            ->name('hourly-totals')
            ->middleware('permission:hourly-totals-view');

        Route::get('hourly-totals/data', [CustomerController::class, 'hourlyTotalsData'])
            ->name('hourly-totals.data')
            ->middleware('permission:hourly-totals-view');

        Route::get('wait-time-history/data', [CustomerController::class, 'waitTimeHistoryData'])
            ->name('wait-time-history.data')
            ->middleware('permission:hourly-totals-view');
            
        // Rutas para mantenimientos
        Route::resource('maintenances', MaintenanceController::class)
            ->names('maintenances')
            ->parameters(['maintenances' => 'maintenance']);

        // Proveedores, productos y pedidos de compra
        Route::resource('vendor-suppliers', VendorSupplierController::class)
            ->except(['show'])
            ->names('vendor-suppliers')
            ->parameters(['vendor-suppliers' => 'vendorSupplier']);

        Route::resource('vendor-items', VendorItemController::class)
            ->except(['show'])
            ->names('vendor-items')
            ->parameters(['vendor-items' => 'vendorItem']);

        Route::resource('vendor-orders', VendorOrderController::class)
            ->names('vendor-orders')
            ->parameters(['vendor-orders' => 'vendorOrder']);

        // Gestión de activos
        Route::resource('asset-cost-centers', AssetCostCenterController::class)
            ->except(['show'])
            ->names('asset-cost-centers')
            ->parameters(['asset-cost-centers' => 'assetCostCenter']);

        Route::resource('asset-categories', AssetCategoryController::class)
            ->except(['show'])
            ->names('asset-categories')
            ->parameters(['asset-categories' => 'assetCategory']);

        Route::resource('asset-locations', AssetLocationController::class)
            ->except(['show'])
            ->names('asset-locations')
            ->parameters(['asset-locations' => 'assetLocation']);

        Route::get('assets/inventory', [AssetController::class, 'inventory'])
            ->name('assets.inventory');
        Route::get('assets/{asset}/label', [AssetController::class, 'printLabel'])
            ->name('assets.print-label');
        Route::resource('assets', AssetController::class)
            ->names('assets')
            ->parameters(['assets' => 'asset']);

        Route::prefix('vendor-orders/{vendorOrder}')
            ->name('vendor-orders.')
            ->group(function () {
                Route::get('receipts/create', [AssetReceiptController::class, 'create'])->name('receipts.create');
                Route::post('receipts', [AssetReceiptController::class, 'store'])->name('receipts.store');
                Route::get('receipts/{receipt}', [AssetReceiptController::class, 'show'])->name('receipts.show');
            });

        // Rutas para Clientes del Customer (export/import primero para evitar colisiones con resource)
        Route::get('clients/export', [\App\Http\Controllers\CustomerClientController::class, 'export'])->name('clients.export');
        Route::post('clients/import', [\App\Http\Controllers\CustomerClientController::class, 'import'])->name('clients.import');
        Route::resource('clients', \App\Http\Controllers\CustomerClientController::class)
            ->names('clients')
            ->parameters(['clients' => 'client']);

        // Rutas para Flota (export/import primero para evitar colisiones con resource)
        Route::get('fleet-vehicles/export', [\App\Http\Controllers\FleetVehicleController::class, 'export'])->name('fleet-vehicles.export');
        Route::post('fleet-vehicles/import', [\App\Http\Controllers\FleetVehicleController::class, 'import'])->name('fleet-vehicles.import');
        Route::resource('fleet-vehicles', \App\Http\Controllers\FleetVehicleController::class)
            ->names('fleet-vehicles')
            ->parameters(['fleet-vehicles' => 'fleet_vehicle']);

        // Rutas para Listado de Rutas (RoutePlan) - Kanban semanal (solo index)
        Route::get('routes', [\App\Http\Controllers\RoutePlanController::class, 'index'])->name('routes.index');
        Route::post('routes/assign-vehicle', [\App\Http\Controllers\RoutePlanController::class, 'assignVehicle'])->name('routes.assign-vehicle');
        Route::delete('routes/remove-vehicle', [\App\Http\Controllers\RoutePlanController::class, 'removeVehicle'])->name('routes.remove-vehicle');
        Route::post('routes/assign-client-vehicle', [\App\Http\Controllers\RoutePlanController::class, 'assignClientToVehicle'])->name('routes.assign-client-vehicle');
        Route::get('routes/client-details/{client}', [\App\Http\Controllers\RoutePlanController::class, 'clientDetails'])->name('routes.client-details');
        Route::post('routes/reorder-clients', [\App\Http\Controllers\RoutePlanController::class, 'reorderClients'])->name('routes.reorder-clients');
        Route::post('routes/move-client', [\App\Http\Controllers\RoutePlanController::class, 'moveClientAssignment'])->name('routes.move-client');
        Route::delete('routes/remove-client-vehicle', [\App\Http\Controllers\RoutePlanController::class, 'removeClientFromVehicle'])->name('routes.remove-client-vehicle');
        Route::post('routes/toggle-order-active', [\App\Http\Controllers\RoutePlanController::class, 'toggleOrderActive'])->name('routes.toggle-order-active');
        Route::post('routes/reorder-orders', [\App\Http\Controllers\RoutePlanController::class, 'reorderOrders'])->name('routes.reorder-orders');
        Route::post('routes/copy-previous-week', [\App\Http\Controllers\RoutePlanController::class, 'copyFromPreviousWeek'])->name('routes.copy-previous-week');
        Route::post('routes/copy-entire-route-previous-week', [\App\Http\Controllers\RoutePlanController::class, 'copyEntireRouteFromPreviousWeek'])->name('routes.copy-entire-route-previous-week');
        Route::get('routes/print-sheet', [\App\Http\Controllers\RoutePlanController::class, 'printRouteSheet'])->name('routes.print-sheet');
        Route::get('routes/print-entire-route', [\App\Http\Controllers\RoutePlanController::class, 'printEntireRoute'])->name('routes.print-entire-route');
        Route::get('routes/export-excel', [\App\Http\Controllers\RoutePlanController::class, 'exportToExcel'])->name('routes.export-excel');
        Route::get('routes/export-entire-route-excel', [\App\Http\Controllers\RoutePlanController::class, 'exportEntireRouteToExcel'])->name('routes.export-entire-route-excel');
    });

    Route::prefix('customers/{customer}')->middleware(['auth'])->group(function () {
        // Nombres de Rutas (RouteName)
        Route::resource('route-names', \App\Http\Controllers\RouteNameController::class)
            ->names('route-names')
            ->parameters(['route-names' => 'route_name']);

        // Finalizar mantenimiento (formulario y acción)
        Route::get('maintenances/{maintenance}/finish', [MaintenanceController::class, 'finishForm'])
            ->name('maintenances.finish.form');
        Route::post('maintenances/{maintenance}/finish', [MaintenanceController::class, 'finishStore'])
            ->name('maintenances.finish.store');

        // Iniciar mantenimiento (start)
        Route::post('maintenances/{maintenance}/start', [MaintenanceController::class, 'start'])
            ->name('maintenances.start');

        // Exportar mantenimientos
        Route::get('maintenances-export/excel', [MaintenanceController::class, 'exportExcel'])
            ->name('maintenances.export.excel');
        Route::get('maintenances-export/pdf', [MaintenanceController::class, 'exportPdf'])
            ->name('maintenances.export.pdf');
        
        // Historial de auditoría
        Route::get('maintenances/{maintenance}/audit', [MaintenanceController::class, 'auditHistory'])
            ->name('maintenances.audit');
        
        // Dashboard de métricas
        Route::get('maintenances-dashboard', [MaintenanceController::class, 'dashboard'])
            ->name('maintenances.dashboard');

        // Rutas para las incidencias de órdenes de producción
        Route::get('production-order-incidents', [ProductionOrderIncidentController::class, 'index'])
            ->name('production-order-incidents.index');
            
        Route::get('production-order-incidents/{incident}', [ProductionOrderIncidentController::class, 'show'])
            ->name('production-order-incidents.show');
            
        Route::delete('production-order-incidents/{incident}', [ProductionOrderIncidentController::class, 'destroy'])
            ->name('production-order-incidents.destroy');

        // Rutas para las incidencias de Calidad (QC)
        Route::get('quality-incidents', [QualityIncidentController::class, 'index'])
            ->name('quality-incidents.index');
        // Rutas para las confirmaciones de Calidad (QC)
        Route::get('qc-confirmations', [QcConfirmationWebController::class, 'index'])
            ->name('qc-confirmations.index');
            
        // Rutas para el calendario laboral
        Route::get('work-calendars', [WorkCalendarController::class, 'index'])
            ->name('work-calendars.index');
            
        Route::get('work-calendars/create', [WorkCalendarController::class, 'create'])
            ->name('work-calendars.create');
            
        Route::post('work-calendars', [WorkCalendarController::class, 'store'])
            ->name('work-calendars.store');
            
        Route::get('work-calendars/{calendar}/edit', [WorkCalendarController::class, 'edit'])
            ->name('work-calendars.edit');
            
        Route::put('work-calendars/{calendar}', [WorkCalendarController::class, 'update'])
            ->name('work-calendars.update');
            
        Route::delete('work-calendars/{calendar}', [WorkCalendarController::class, 'destroy'])
            ->name('work-calendars.destroy');
            
        Route::post('work-calendars/bulk-update', [WorkCalendarController::class, 'bulkUpdate'])
            ->name('work-calendars.bulk-update');
        Route::post('work-calendars/import-holidays', [WorkCalendarController::class, 'importHolidays'])->name('work-calendars.import-holidays');

        // Callbacks history (by customer)
        Route::prefix('callbacks')->name('callbacks.')->group(function(){
            Route::get('/', [ProductionOrderCallbackController::class, 'index'])->name('index');
            Route::get('{callback}/edit', [ProductionOrderCallbackController::class, 'edit'])->name('edit');
            Route::put('{callback}', [ProductionOrderCallbackController::class, 'update'])->name('update');
            Route::delete('{callback}', [ProductionOrderCallbackController::class, 'destroy'])->name('destroy');
            Route::post('{callback}/force', [ProductionOrderCallbackController::class, 'force'])->name('force');
        });


        // Archivos públicos por proceso de orden original
        Route::prefix('original-orders/{originalOrder}/processes/{originalOrderProcess}/files')
            ->name('original-orders.processes.files.')
            ->group(function () {
                Route::get('/', [OriginalOrderProcessFileController::class, 'index'])
                    ->name('index');
                Route::post('/', [OriginalOrderProcessFileController::class, 'store'])
                    ->name('store');
                Route::delete('{file}', [OriginalOrderProcessFileController::class, 'destroy'])
                    ->name('destroy');
            });

        // Listado de sensores por cliente
        Route::get('sensors', [CustomerController::class, 'sensorsIndex'])
            ->name('sensors.index');

        // Optimal Sensor Times
        Route::get('optimal-sensor-times', [\App\Http\Controllers\OptimalSensorTimeController::class, 'index'])
            ->name('optimal-sensor-times.index')
            ->middleware('permission:productionline-kanban');
        Route::get('optimal-sensor-times/{optimalSensorTime}/edit', [\App\Http\Controllers\OptimalSensorTimeController::class, 'edit'])
            ->name('optimal-sensor-times.edit')
            ->middleware('permission:optimal-sensor-times-edit');
        Route::put('optimal-sensor-times/{optimalSensorTime}', [\App\Http\Controllers\OptimalSensorTimeController::class, 'update'])
            ->name('optimal-sensor-times.update')
            ->middleware('permission:optimal-sensor-times-edit');
        Route::get('optimal-sensor-times/{optimalSensorTime}/apply', [\App\Http\Controllers\OptimalSensorTimeController::class, 'apply'])
            ->name('optimal-sensor-times.apply')
            ->middleware('permission:optimal-sensor-times-apply');
        Route::post('optimal-sensor-times/{optimalSensorTime}/apply', [\App\Http\Controllers\OptimalSensorTimeController::class, 'applyStore'])
            ->name('optimal-sensor-times.apply.store')
            ->middleware('permission:optimal-sensor-times-apply');
        Route::post('optimal-sensor-times/settings', [\App\Http\Controllers\OptimalSensorTimeController::class, 'updateSettings'])
            ->name('optimal-sensor-times.settings')
            ->middleware('permission:optimal-sensor-times-settings');
        Route::delete('optimal-sensor-times/{optimalSensorTime}', [\App\Http\Controllers\OptimalSensorTimeController::class, 'destroy'])
            ->name('optimal-sensor-times.destroy')
            ->middleware('permission:optimal-sensor-times-delete');
    });
});


// Ruta para obtener datos del Kanban mediante AJAX (para actualización automática)
Route::get('kanban-data', [CustomerController::class, 'getKanbanData'])
    ->name('kanban.data');

// Rutas para colores RFID
Route::get('production-lines/{production_line_id}/rfid/colors', [RfidColorController::class, 'index'])
    ->name('rfid.colors.index');

Route::get('production-lines/{production_line_id}/rfid/colors/create', [RfidColorController::class, 'create'])
    ->name('rfid.colors.create');

Route::post('production-lines/{production_line_id}/rfid/colors', [RfidColorController::class, 'store'])
    ->name('rfid.colors.store');

Route::get('production-lines/{production_line_id}/rfid/colors/{rfidColor}/edit', [RfidColorController::class, 'edit'])
    ->name('rfid.colors.edit');

Route::put('production-lines/{production_line_id}/rfid/colors/{rfidColor}', [RfidColorController::class, 'update'])
    ->name('rfid.colors.update');

Route::delete('production-lines/{production_line_id}/rfid/colors/{rfidColor}', [RfidColorController::class, 'destroy'])
    ->name('rfid.colors.destroy');


//limpiar el listado de epc bloqueados
Route::delete('/rfid-blocked/destroy-all', [RfidBlockedController::class, 'destroyAll'])->name('rfid-blocked.destroyAll');

// Ruta para la página principal de sensores
Route::get('sensors/{id}', [SensorController::class, 'listSensors'])->name('sensors.index');
// Rutas para ProductionLineProcess
Route::prefix('productionlines/{production_line}/processes')->name('productionlines.processes.')->group(function () {
    Route::get('/', [ProductionLineProcessController::class, 'index'])->name('index');
    Route::get('/create', [ProductionLineProcessController::class, 'create'])->name('create');
    Route::post('/', [ProductionLineProcessController::class, 'store'])->name('store');
    Route::get('/{process}/edit', [ProductionLineProcessController::class, 'edit'])->name('edit');
    Route::put('/{process}', [ProductionLineProcessController::class, 'update'])->name('update');
    Route::delete('/{process}', [ProductionLineProcessController::class, 'destroy'])->name('destroy');
});

// Rutas para ProductionLineArticle
Route::prefix('productionlines/{production_line}/articles')->name('productionlines.articles.')->group(function () {
    Route::get('/', [ProductionLineArticleController::class, 'index'])->name('index');
    Route::get('/create', [ProductionLineArticleController::class, 'create'])->name('create');
    Route::post('/', [ProductionLineArticleController::class, 'store'])->name('store');
    Route::get('/{article}/edit', [ProductionLineArticleController::class, 'edit'])->name('edit');
    Route::put('/{article}', [ProductionLineArticleController::class, 'update'])->name('update');
    Route::delete('/{article}', [ProductionLineArticleController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete', [ProductionLineArticleController::class, 'bulkDelete'])->name('bulk-delete')->middleware(['auth', 'XSS']);
});

// Mostrar el listado de sensores para una línea de producción
Route::get('smartsensors/{production_line_id}', [SensorController::class, 'index'])->name('smartsensors.index');

// Mostrar el formulario para crear un nuevo sensor en una línea de producción
Route::get('smartsensors/{production_line_id}/create', [SensorController::class, 'create'])->name('smartsensors.create');

// Almacenar un nuevo sensor
Route::post('smartsensors/{production_line_id}', [SensorController::class, 'store'])->name('smartsensors.store');

// Mostrar el formulario para editar un sensor existente
Route::get('smartsensors/{sensor}/edit', [SensorController::class, 'edit'])->name('smartsensors.edit');

// Actualizar un sensor existente
Route::put('smartsensors/{sensor}', [SensorController::class, 'update'])->name('smartsensors.update');

// Eliminar un sensor existente
Route::delete('smartsensors/{sensor}', [SensorController::class, 'destroy'])->name('smartsensors.destroy');

// Mostrar la vista en tiempo real de un sensor
Route::get('smartsensors/{sensor}/live', [SensorController::class, 'liveView'])->name('smartsensors.live-view');

// Mostrar la vista de historial de un sensor
Route::get('smartsensors/{sensor}/history', [SensorController::class, 'historyView'])->name('smartsensors.history');




Route::get('/shift-lists', [ShiftManagementController::class, 'index'])->name('shift.index');
Route::get('/shift-lists/api', [ShiftManagementController::class, 'getShiftsData'])->name('shift.api');
Route::post('/shift-lists', [ShiftManagementController::class, 'store'])->name('shift.store');
Route::put('/shift-lists/{id}', [ShiftManagementController::class, 'update'])->name('shift.update');
Route::delete('/shift-lists/{id}', [ShiftManagementController::class, 'destroy'])->name('shift.destroy');
Route::get('/shift-history/{productionLineId}', [ShiftManagementController::class, 'showShiftHistory'])->name('shift.history');
Route::post('/shift-event', [ShiftManagementController::class, 'publishShiftEvent'])->name('shift.publishEvent');



//rfid select confection
Route::get('/rfid/post', [RfidPostController::class, 'index'])->name('rfid.post.index');


// En routes/web.php
Route::get('/roles/list', function () {
    return response()->json(Spatie\Permission\Models\Role::all());
});

Route::prefix('worker-post')->group(function () {
    // GET que carga la VISTA
    Route::get('/', [OperatorPostController::class, 'index'])->name('worker-post.index');

    // GET que retorna JSON para DataTables
    Route::get('/api', [OperatorPostController::class, 'apiIndex'])->name('worker-post.api');

    // Rutas para las operaciones CRUD
    Route::post('/', [OperatorPostController::class, 'store'])->name('worker-post.store');
    Route::get('/create', [OperatorPostController::class, 'create'])->name('worker-post.create');
    Route::get('/{id}/edit', [OperatorPostController::class, 'edit'])->name('worker-post.edit');
    Route::put('/{id}', [OperatorPostController::class, 'update'])->name('worker-post.update');
    Route::delete('/{id}', [OperatorPostController::class, 'destroy'])->name('worker-post.destroy');
});

//usar el qr para asignar puesto operario confeccion
Route::get('/scan-post', [ScanPostController::class, 'index'])->name('scan-post.index');

//kanban scada
Route::get('/scada-order', [ScadaOrderController::class, 'index'])->name('scada.order');

//kanban production linea de production

Route::get('/production-order-kanban', [ProductionOrderController::class, 'index'])->name('production.order');

//server controller
Route::get('/server', [ServerController::class, 'index'])->name('server.index');

// Rutas para las líneas de producción
Route::get('customers/{customer_id}/productionlines', [ProductionLineController::class, 'index'])->name('productionlines.index');
Route::get('productionlines/{id}/edit', [ProductionLineController::class, 'edit'])->name('productionlines.edit');
Route::delete('productionlines/{id}', [ProductionLineController::class, 'destroy'])->name('productionlines.destroy');
Route::get('customers/{customer_id}/productionlinesjson', [ProductionLineController::class, 'getProductionLines'])->name('productionlinesjson.index');
Route::put('productionlines/{id}', [ProductionLineController::class, 'update'])->name('productionlines.update');
Route::get('customers/{customer_id}/productionlinescreate', [ProductionLineController::class, 'create'])->name('productionlines.create');


Route::post('productionlines', [ProductionLineController::class, 'store'])->name('productionlines.store');


//Route::get('barcoders/{production_line_id}', [BarcodeController::class, 'index'])->name('barcoders.index');

// Ruta para listar los barcodes de una línea de producción
Route::get('productionlines/{production_line_id}/barcodes', [BarcodeController::class, 'index'])->name('barcodes.index');

// Ruta para obtener los barcodes en formato JSON
Route::get('productionlines/{production_line_id}/barcodesjson', [BarcodeController::class, 'getBarcodes'])->name('barcodes.json');

// Ruta para mostrar el formulario de creación
Route::get('productionlines/{production_line_id}/barcodes/create', [BarcodeController::class, 'create'])->name('barcodes.create');

// Ruta para almacenar el nuevo barcode
Route::post('productionlines/{production_line_id}/barcodes', [BarcodeController::class, 'store'])->name('barcodes.store');

// Ruta para mostrar el formulario de edición
Route::get('barcodes/{id}/edit', [BarcodeController::class, 'edit'])->name('barcodes.edit');

// Ruta para actualizar el barcode
Route::put('barcodes/{id}', [BarcodeController::class, 'update'])->name('barcodes.update');

// Ruta para eliminar el barcode
Route::delete('barcodes/{id}', [BarcodeController::class, 'destroy'])->name('barcodes.destroy');

//ruta para impresoras
Route::resource('printers', PrinterController::class);

// Ruta para listar los Modbuses de una línea de producción
Route::get('productionlines/{production_line_id}/modbuses', [ModbusController::class, 'index'])->name('modbuses.index');

// Ruta para obtener los Modbuses en formato JSON (opcional, si usas DataTables)
Route::get('productionlines/{production_line_id}/modbusesjson', [ModbusController::class, 'getModbuses'])->name('modbuses.json');

// Ruta para mostrar el formulario de creación
Route::get('productionlines/{production_line_id}/modbuses/create', [ModbusController::class, 'create'])->name('modbuses.create');

// Ruta para almacenar el nuevo Modbus
Route::post('productionlines/{production_line_id}/modbuses', [ModbusController::class, 'store'])->name('modbuses.store');

Route::delete('productionlines/{production_line_id}/modbuses/{modbus}', [ModbusController::class, 'destroy'])->name('modbuses.destroy');

// Ruta para mostrar el formulario de edición
Route::get('modbuses/{id}/edit', [ModbusController::class, 'edit'])->name('modbuses.edit');

// Ruta para actualizar el Modbus
Route::put('modbuses/{id}', [ModbusController::class, 'update'])->name('modbuses.update');

Route::get('modbuses/queue-print', [ModbusController::class, 'queuePrint'])->name('modbuses.queueprint');

// Ruta para mostrar la vista de estadísticas de Modbus
Route::get('/modbuses/liststats/weight', [ModbusController::class, 'listStats'])->name('modbuses.liststats');

// Ruta para eliminar el Modbus
Route::resource('oee', MonitorOeeController::class);
Route::resource('sensor-transformations', SensorTransformationController::class);

Route::get('logs', [LogController::class, 'view'])->name('logs.view');


//ruta para estadistica de productionline
Route::get('/productionlines/liststats', [ProductionLineController::class, 'listStats'])->name('productionlines.liststats');


Route::get('/', [HomeController::class, 'index'])->name('home')->middleware(['auth', 'XSS', '2fa']);

Route::post('/chart', [HomeController::class, 'chart'])->name('get.chart.data')->middleware(['auth', 'XSS']);

Route::get('notification', [HomeController::class, 'notification']);

Route::group(['middleware' => ['auth', 'XSS']], function () {
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('permission', PermissionController::class);
    Route::resource('modules', ModualController::class);
});

// Route::delete para users.destroy eliminada - ya está incluida en el Route::resource de arriba

Route::post('/role/{id}', [RoleController::class, 'assignPermission'])->name('roles_permit')->middleware(['auth', 'XSS']);

Route::group(['middleware' => ['auth', 'XSS']], function () {
    Route::get('setting/email-setting', [SettingController::class, 'getmail'])->name('settings.getmail');
    Route::post('setting/email-settings_store', [SettingController::class, 'saveEmailSettings'])->name('settings.emails');

    Route::get('setting/datetime', [SettingController::class, 'getdate'])->name('datetime');
    Route::post('setting/datetime-settings_store', [SettingController::class, 'saveSystemSettings'])->name('settings.datetime');

    Route::get('setting/logo', [SettingController::class, 'getlogo'])->name('getlogo');
    Route::post('setting/logo_store', [SettingController::class, 'store'])->name('settings.logo');
    Route::resource('settings', SettingController::class);

    Route::get('test-mail', [SettingController::class, 'testMail'])->name('test.mail');
    Route::post('test-mail', [SettingController::class, 'testSendMail'])->name('test.send.mail');

    Route::post('settings/finish-shift-emails',[SettingController::class, 'saveFinishShiftEmailsSettings'])->name('settings.finishshiftemails');
    Route::get('settings/action/test-finish-shift-emails', [SettingController::class, 'testFinishShiftEmails'])->name('settings.testFinishShifts');
    
    // Configuración del lector RFID
    Route::post('settings/rfid', [SettingController::class, 'saveRfidSettings'])->name('settings.rfid');
    Route::post('settings/redis', [SettingController::class, 'saveRedisSettings'])->name('settings.redis');
    Route::post('settings/upload-stats', [SettingController::class, 'saveUploadStatsSettings'])->name('settings.upload-stats');
    
    // Configuración de la base de datos de réplica
    Route::post('settings/replica-db', [SettingController::class, 'saveReplicaDbSettings'])->name('settings.replica-db');
    Route::post('settings/test-replica-db-connection', [SettingController::class, 'testReplicaDbConnection'])->name('settings.test-replica-db-connection');
    Route::post('settings/create-replica-database', [SettingController::class, 'createReplicaDatabase'])->name('settings.create-replica-database');
});

Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(['auth', 'XSS']);

Route::post('edit-profile', [UserController::class, 'editprofile'])->name('update.profile')->middleware(['auth', 'XSS']);

Route::group(['middleware' => ['auth', 'XSS']], function () {
    Route::get('change-language/{lang}', [LanguageController::class, 'changeLanquage'])->name('change.language');
    Route::get('manage-language/{lang}', [LanguageController::class, 'manageLanguage'])->name('manage.language');
    Route::post('store-language-data/{lang}', [LanguageController::class, 'storeLanguageData'])->name('store.language.data');
    Route::get('create-language', [LanguageController::class, 'createLanguage'])->name('create.language');
    Route::post('store-language', [LanguageController::class, 'storeLanguage'])->name('store.language');
    Route::delete('/lang/{lang}', [LanguageController::class, 'destroyLang'])->name('lang.destroy');
    Route::get('language', [LanguageController::class, 'index'])->name('index');
});

Route::get('generator_builder', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@builder')->name('io_generator_builder')->middleware(['auth', 'XSS']);

Route::get('field_template', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@fieldTemplate')->name('io_field_template')->middleware(['auth', 'XSS']);

Route::get('relation_field_template', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@relationFieldTemplate')->name('io_relation_field_template')->middleware(['auth', 'XSS']);

Route::post('generator_builder/generate', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@generate')->name('io_generator_builder_generate')->middleware(['auth', 'XSS']);

Route::post('generator_builder/rollback', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@rollback')->name('io_generator_builder_rollback')->middleware(['auth', 'XSS']);

Route::post('generator_builder/generate-from-file', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@generateFromFile')->name('io_generator_builder_generate_from_file')->middleware(['auth', 'XSS']);


Route::get('whatsapp/notifications', [App\Http\Controllers\WhatsAppController::class, 'sendNotification'])->name('whatsapp.notifications');
Route::post('whatsapp/disconnect', [App\Http\Controllers\WhatsAppController::class, 'disconnect'])->name('whatsapp.disconnect');
Route::post('whatsapp/update-phone', [App\Http\Controllers\WhatsAppController::class, 'updatePhoneNumber'])->name('whatsapp.updatePhone');
Route::post('whatsapp/update-maintenance-phones', [App\Http\Controllers\WhatsAppController::class, 'updateMaintenancePhones'])->name('whatsapp.updateMaintenancePhones');
Route::post('whatsapp/update-incident-phones', [App\Http\Controllers\WhatsAppController::class, 'updateIncidentPhones'])->name('whatsapp.updateIncidentPhones');
Route::post('whatsapp/send-test-message', [App\Http\Controllers\WhatsAppController::class, 'sendTestMessage'])->name('whatsapp.sendTestMessage');
Route::get('/whatsapp-status', [WhatsAppController::class, 'getStatus'])->name('whatsapp.status');




Route::get('rfid/{production_line_id}', [RfidController::class, 'index'])->name('rfid.index');
Route::get('rfid/create/{production_line_id}', [RfidController::class, 'create'])->name('rfid.create');
Route::post('rfid', [RfidController::class, 'store'])->name('rfid.store');
Route::get('rfid/{id}/edit', [RfidController::class, 'edit'])->name('rfid.edit');
Route::put('rfid/{id}', [RfidController::class, 'update'])->name('rfid.update');
Route::delete('rfid/{id}', [RfidController::class, 'destroy'])->name('rfid.destroy');

// Ruta para el visualizador MQTT original (basado en WebSocket)
Route::get('/rfid-mqtt/visualizador-mqtt', [RfidController::class, 'showMqttVisualizer'])->name('rfid.visualizer');

// --- NUEVAS RUTAS ---
// Ruta para mostrar la nueva vista Blade que usará AJAX
Route::get('/rfid/visualizador-ajax', [RfidController::class, 'showAjaxVisualizer'])->name('rfid.ajaxVisualizer');

// Ruta que será llamada por AJAX para obtener los datos del gateway Node.js
// Esta ruta llama al método getGatewayMessages en RfidController
Route::get('/rfid-mqtt/api/gateway-data', [RfidController::class, 'getGatewayMessages'])->name('rfid.gatewayData');

Route::get('workers-admin', [WorkerController::class, 'index'])->name('workers-admin.index');



// rutas para editar prompts
Route::middleware(['auth', 'XSS'])->prefix('ia-prompts')->name('ia_prompts.')->group(function () {
    Route::get('/', [IaPromptAdminController::class, 'index'])->name('index');
    Route::get('/{iaPrompt}/edit', [IaPromptAdminController::class, 'edit'])->name('edit');
    Route::put('/{iaPrompt}', [IaPromptAdminController::class, 'update'])->name('update');
    // Ejecutar Artisan para regenerar plantillas desde la UI
    Route::post('/regenerate', [IaPromptAdminController::class, 'regenerate'])->name('regenerate');
    // Rutas para configuración de AI
    Route::get('/config', [AiConfigController::class, 'index'])->name('config');
    Route::put('/config', [AiConfigController::class, 'update'])->name('config.update');
});

// Rutas alternativas de configuración de IA (sin permisos específicos, solo autenticación)
Route::middleware(['auth', 'XSS'])->group(function () {
    Route::get('/ai-config', [AiConfigController::class, 'index'])->name('ai_config.index');
    Route::put('/ai-config', [AiConfigController::class, 'update'])->name('ai_config.update');
});

// Vista principal de usuarios (Blade con DataTables manual)
Route::get('users', [UserController::class, 'index'])->name('users.index');

// AJAX para listar todos
Route::get('users/list-all/json', [UserController::class, 'listAllJson'])->name('users.listAllJson');

// AJAX para crear / actualizar
Route::post('users/store-or-update/ajax', [UserController::class, 'storeOrUpdateAjax'])->name('users.storeOrUpdateAjax');

// AJAX para eliminar
Route::delete('users/delete/ajax/{id}', [UserController::class, 'deleteAjax'])->name('users.deleteAjax');

// Rutas para la gestión de procesos
Route::post('processes/bulk-update', [ProcessController::class, 'bulkUpdate'])
    ->name('processes.bulk-update');
Route::resource('processes', ProcessController::class);

// Rutas para familias de artículos y artículos
Route::resource('article-families', ArticleFamilyController::class)->middleware(['auth', 'XSS']);
Route::resource('article-families.articles', ArticleController::class)->middleware(['auth', 'XSS']);

Route::get('confections', [ConfectionController::class, 'index'])->name('confections.index');

//Server monitor externo
Route::get('/servermonitor', [ServerMonitorController::class, 'index'])->name('servermonitor.index');
Route::get('/servermonitor/create', [ServerMonitorController::class, 'create'])->name('hosts.create');
Route::post('/servermonitor', [ServerMonitorController::class, 'store'])->name('hosts.store');
Route::get('/servermonitor/{host}/edit', [ServerMonitorController::class, 'edit'])->name('hosts.edit');
Route::patch('/servermonitor/{host}', [ServerMonitorController::class, 'update'])->name('hosts.update');
Route::delete('/servermonitor/{host}', [ServerMonitorController::class, 'destroy'])->name('hosts.destroy');

Route::get('/servermonitor/latest/{host}', [ServerMonitorController::class, 'getLatest'])->name('servermonitor.latest');
Route::get('/servermonitor/history/{host}', [ServerMonitorController::class, 'getHistory'])->name('servermonitor.history');



// Mostrar la vista con DataTables
Route::get('manage-role', [RoleManageController::class, 'index'])->name('manage-role.index');

// Opcionalmente, AJAX:
Route::get('manage-role/list-all', [RoleManageController::class, 'listAll'])->name('manage-role.listAll');
Route::post('manage-role/store-or-update', [RoleManageController::class, 'storeOrUpdate'])->name('manage-role.storeOrUpdate');
Route::delete('manage-role/delete/{id}', [RoleManageController::class, 'delete'])->name('manage-role.delete');
Route::post('manage-role/update-permissions/{id}', [RoleManageController::class, 'updatePermissions'])->name('manage-role.updatePermissions');

Route::post('rfid/categories/{production_line_id}/import', [RfidCategoryController::class, 'import'])->name('rfid.categories.import');

Route::post('rfid/devices/{production_line_id}/import', [RfidDeviceController::class, 'import'])->name('rfid.devices.import');

Route::get('manage-permission', [PermissionManageController::class, 'index'])->name('manage-permission.index');

// AJAX
Route::get('manage-permission/list-all', [PermissionManageController::class, 'listAll'])->name('manage-permission.listAll');
Route::post('manage-permission/store-or-update', [PermissionManageController::class, 'storeOrUpdate'])->name('manage-permission.storeOrUpdate');
Route::delete('manage-permission/delete/{id}', [PermissionManageController::class, 'delete'])->name('manage-permission.delete');

Route::prefix('rfid-categories')->group(function () {
    Route::get('/{production_line_id}', [RfidCategoryController::class, 'index'])->name('rfid.categories.index');
    Route::get('/create/{production_line_id}', [RfidCategoryController::class, 'create'])->name('rfid.categories.create');
    Route::post('/', [RfidCategoryController::class, 'store'])->name('rfid.categories.store');
    Route::get('/{id}/edit', [RfidCategoryController::class, 'edit'])->name('rfid.categories.edit');
    Route::put('/{id}', [RfidCategoryController::class, 'update'])->name('rfid.categories.update');
    Route::delete('/{id}', [RfidCategoryController::class, 'destroy'])->name('rfid.categories.destroy');
});

Route::prefix('rfid-devices')->group(function () {
    Route::get('/{production_line_id}', [RfidDeviceController::class, 'index'])->name('rfid.devices.index');
    Route::get('/create/{production_line_id}', [RfidDeviceController::class, 'create'])->name('rfid.devices.create');
    Route::post('/', [RfidDeviceController::class, 'store'])->name('rfid.devices.store');
    Route::get('/{id}/edit', [RfidDeviceController::class, 'edit'])->name('rfid.devices.edit');
    Route::put('/{id}', [RfidDeviceController::class, 'update'])->name('rfid.devices.update');
    Route::delete('/{id}', [RfidDeviceController::class, 'destroy'])->name('rfid.devices.destroy');
});

Route::prefix('telegram')->group(function () {
    Route::get('/', [TelegramController::class, 'index'])->name('telegram.index'); // La vista ahora es telegram/index.blade.php
    Route::post('/request-code', [TelegramController::class, 'requestCode'])->name('telegram.requestCode');
    Route::post('/verify-code', [TelegramController::class, 'verifyCode'])->name('telegram.verifyCode');
    Route::post('/logout', [TelegramController::class, 'logout'])->name('telegram.logout');
    Route::post('/send-message', [TelegramController::class, 'sendMessage'])->name('telegram.sendMessage');
    Route::post('/update-maintenance-peers', [TelegramController::class, 'updateMaintenancePeers'])->name('telegram.updateMaintenancePeers');
    Route::get('/status', [TelegramController::class, 'status'])->name('telegram.status');
});

Route::group(['prefix' => '2fa', 'middleware' => ['auth', 'XSS']], function () {
    Route::get('/', [UserController::class, 'profile'])->name('2fa');
    Route::post('/generateSecret', [LoginSecurityController::class, 'generate2faSecret'])->name('generate2faSecret');
    Route::post('/enable2fa', [LoginSecurityController::class, 'enable2fa'])->name('enable2fa');
    Route::post('/disable2fa', [LoginSecurityController::class, 'disable2fa'])->name('disable2fa');

    // Middleware 2fa
    Route::post('/2faVerify', function () {
        return redirect()->back();
    })->name('2faVerify')->middleware('2fa');
});

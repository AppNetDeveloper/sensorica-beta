<?php

use App\Http\Controllers\Api\GetTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiBarcoderController;
use App\Http\Controllers\Api\ControlWeightController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\ModbusController;
use App\Http\Controllers\Api\StoreQueueController;
use App\Http\Controllers\Api\ZerotierIpBarcoderController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\OrderStatsController;
use App\Http\Controllers\Api\ScadaController;
use App\Http\Controllers\Api\ScadaMaterialTypeController;
use App\Http\Controllers\Api\RfidDetailController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\BluetoothDetailController;
use App\Http\Controllers\Api\ModbusProcessController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\OperatorController;
use App\Http\Controllers\Api\ProductListController;
use App\Http\Controllers\Api\ProductionLineStatusController;
use App\Http\Controllers\Api\ScadaOrderController;
use App\Http\Controllers\Api\ProductionOrderController;
use App\Http\Controllers\Api\ProductListSelectedsController;
use App\Http\Controllers\Api\RfidReadingController;
use App\Http\Controllers\Api\OperatorPostController;
use App\Http\Controllers\Api\TcpPublishController;
use App\Http\Controllers\Api\TransferExternalDbController;
use App\Http\Controllers\Api\OrderNoticeController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\ProductionOrderTopflowApiController;
use App\Http\Controllers\Api\SupplierOrderController;
use App\Http\Controllers\Api\ServerMonitorController;
use App\Http\Controllers\Api\CalculateProductionDowntimeController;
use App\Http\Controllers\Api\ShiftHistoryController;
use App\Http\Controllers\Api\ShiftEventController;
use App\Http\Controllers\Api\ShiftListController;
use App\Http\Controllers\Api\WorkerController; 
use App\Http\Controllers\Api\ShiftProcessEventController;
use App\Http\Controllers\Api\RfidErrorPointController;
use App\Http\Controllers\Api\IaPromptController; // Importa tu controlador
use App\Http\Controllers\Api\BarcodeScansController;
use App\Http\Controllers\Api\ProductionOrderArticlesController;
use App\Http\Controllers\Api\MaintenanceApiController;
use App\Http\Controllers\Api\ProductionLineController;
use App\Http\Controllers\Api\LineAvailabilityController;
use App\Http\Controllers\Api\OriginalOrderProcessFileApiController;
use App\Http\Controllers\Api\QualityIssueController;
use App\Http\Controllers\Api\QcConfirmationController;





/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/server-monitor-store', [ServerMonitorController::class, 'store']);
Route::post('/register-server', [ServerMonitorController::class, 'index']);


Route::match(['get', 'post'], '/barcode', [ApibarcoderController::class, 'barcode']);
Route::match(['get', 'post'], '/ip-zerotier', [ZerotierIpBarcoderController::class, 'ipZerotier']);



Route::match(['get', 'post'], '/queue-print', [StoreQueueController::class, 'storeQueuePrint']);
Route::match(['get', 'post'], '/queue-print-list', [StoreQueueController::class, 'getQueuePrints']);

Route::get('/modbuses', [ModbusController::class, 'getModbuses']);
Route::post('/tolvas/{id}/dosificacion/recalcular-automatico', [ModbusController::class, 'recalculateDosingProcess']);

Route::get('/control-weights/{token}/all', [ControlWeightController::class, 'getAllDataByToken']);

Route::middleware(['throttle:90000,1'])->group(function () {
    Route::get('/control-weight/{token}', [ControlWeightController::class, 'getDataByToken']);
});
// Nueva ruta para el consolidado por supplierOrderId
Route::get('/control_weight/{supplierOrderId}', [ControlWeightController::class, 'show']);

// Ruta para GET request
Route::get('/order-notice/{token?}', [ApibarcoderController::class, 'getOrderNotice']);

// Ruta para POST request
Route::post('/order-notice', [ApibarcoderController::class, 'getOrderNotice']);


// Ruta para GET request (con el token en la URL)
Route::get('/barcode-info/{token}', [ApiBarcoderController::class, 'getBarcodeInfo']);

// Ruta para POST request (con el token en el cuerpo de la solicitud)
Route::post('/barcode-info', [ApiBarcoderController::class, 'getBarcodeInfo']);

Route::match(['get', 'post'], '/production-lines/{customerToken}', [GetTokenController::class, 'getProductionLinesByCustomerToken']);
Route::match(['get', 'post'], '/modbus-info/{token}', [GetTokenController::class, 'getModbusInfo']);
Route::match(['get', 'post'], '/barcode-info-by-customer/{customerToken}', [GetTokenController::class, 'getBarcodeInfoByCustomer']);

// Ruta para obtener estados de líneas de producción
Route::get('/production-lines/statuses/{customerId?}', [ProductionLineController::class, 'getStatuses'])->name('api.production-lines.statuses');
// Ruta para obtener el estado de planificación actual por token de línea
Route::get('/production-lines/schedule-status/{token}', [ProductionLineController::class, 'getScheduleStatusByToken'])->name('api.production-lines.schedule-status');

// Rutas para la disponibilidad de líneas de producción
Route::get('/production-lines/{id}/availability', [LineAvailabilityController::class, 'getAvailability']);
Route::post('/production-lines/{id}/availability', [LineAvailabilityController::class, 'saveAvailability']);
// Mantenemos la ruta anterior por compatibilidad
Route::post('/production-lines/availability', [LineAvailabilityController::class, 'saveAvailability']);

Route::match(['get', 'post'], '/sensors/{token}', [SensorController::class, 'getByToken']);
Route::match(['get', 'post'], '/sensors', [SensorController::class, 'getAllSensors']);

Route::middleware(['throttle:90000,1'])->group(function () {
    Route::post('/sensor-insert', [SensorController::class, 'sensorInsert']);
});


Route::match(['get', 'post'], '/order-stats', [OrderStatsController::class, 'getLastOrderStat']);

Route::match(['get', 'post'], '/order-stats-all', [OrderStatsController::class, 'getOrderStatsBetweenDates']);


Route::get('scada/{token}', [ScadaController::class, 'getModbusesByScadaToken']);

Route::get('scada-material/{token}', [ScadaMaterialTypeController::class, 'getScadaMaterialByToken']);

Route::put('/modbus/{modbusId}/material', [ScadaController::class, 'updateMaterialForModbus']);

Route::post('/modbus/send', [ModbusController::class, 'sendDosage']);

Route::post('/modbus/zero', [ModbusController::class, 'setZero']);

Route::post('/modbus/tara', [ModbusController::class, 'setTara']);
Route::post('/modbus/tara/reset', [ModbusController::class, 'resetTara']);
Route::post('/modbus/cancel', [ModbusController::class, 'sendCancel']);

// historial de shift
Route::get('/shift-history/production-line/{token}/last', [ShiftHistoryController::class, 'getLastByProductionLineToken']);

//shift publicar mesajes en mqtt

Route::post('shift-event', [ShiftEventController::class, 'publishShiftEvent']);

//listdao de turnos por production_line_id
Route::get('/shift-lists', [ShiftListController::class, 'index']);


//api rfid
Route::post('/rfid-insert', [RfidDetailController::class, 'store']);
Route::get('/rfid-history', [RfidDetailController::class, 'getHistoryRfid']);
Route::get('/get-filters', [RfidDetailController::class, 'getFilters']);

//whatsapp api


Route::post('/whatsapp-credentials', [WhatsAppController::class, 'storeCredentials']);
Route::match(['get', 'post'], '/send-message', [WhatsAppController::class, 'sendMessage']);
Route::match(['get', 'post'], '/whatsapp/logout', [WhatsAppController::class, 'logout']);
Route::get('/whatsapp-qr', [WhatsAppController::class, 'getQR']);
// Retorna el QR como SVG
Route::get('/whatsapp-qr/svg', [WhatsAppController::class, 'getQRSvg']);
// Retorna el QR como base64
Route::get('/whatsapp-qr/base64', [WhatsAppController::class, 'getQRBase64']);

//ruta de ordernotice por json api
Route::post('/order-notice/store', [OrderNoticeController::class, 'store']);

// ruta scanner
Route::prefix('bluetooth')->group(function () {
    // Ruta para insertar un nuevo registro de lectura de Bluetooth
    Route::post('/insert', [BluetoothDetailController::class, 'store'])->name('bluetooth.insert');

    // Ruta para obtener el historial de lecturas de Bluetooth con filtros
    Route::get('/history', [BluetoothDetailController::class, 'getHistoryBluetooth'])->name('bluetooth.history');

    // Ruta para obtener los filtros disponibles para Bluetooth (antenas y MACs)
    Route::get('/filters', [BluetoothDetailController::class, 'getFilters'])->name('bluetooth.filters');
});


//system control

Route::post('/reboot', [SystemController::class, 'rebootSystem']);
Route::post('/poweroff', [SystemController::class, 'powerOffSystem']);
Route::post('/restart-mysql', [SystemController::class, 'restartMysql']);
Route::get('/server-stats', [SystemController::class, 'getServerStats']);
Route::post('/restart-supervisor', [SystemController::class, 'restartSupervisor']);
Route::post('/stop-supervisor', [SystemController::class, 'stopSupervisor']);
Route::post('/start-supervisor', [SystemController::class, 'startSupervisor']);
Route::post('/restart-485-Swift', [SystemController::class, 'restart485Swift']);
Route::get('/supervisor-status', [SystemController::class, 'getSupervisorStatus']);
Route::get('/check-485-service', [SystemController::class, 'check485Service']);
Route::post('/install-485-service', [SystemController::class, 'install485Service']);
Route::post('/app-update', [SystemController::class, 'appUpdate']);
Route::post('/verne-update', [SystemController::class, 'verneUpdate']);
Route::get('/server-ips', [SystemController::class, 'getServerIps']);
Route::post('/update-env', [SystemController::class, 'updateEnv']);
Route::post('/check-db-connection', [SystemController::class, 'checkDbConnection']);
Route::post('/verify-and-sync-database', [SystemController::class, 'verifyAndSyncDatabase']);
Route::post('/run-update', [SystemController::class, 'runUpdateScript']);
Route::post('/fix-logs', [SystemController::class, 'fixLogs']);

// Routes for Operators
Route::post('/workers/update-or-insert', [OperatorController::class, 'updateOrInsertSingle']);
Route::post('/workers/replace-all', [OperatorController::class, 'replaceAll']);
Route::get('/workers/list-all', [OperatorController::class, 'listAll']);
Route::get('/workers/list-all2', [OperatorController::class, 'listAll2']);
Route::get('/operators', [OperatorController::class, 'listAll']); // Nueva ruta para compatibilidad con el filtro de operadores
Route::get('/operators/internal', [OperatorController::class, 'listInternalIds']); // Nueva ruta para obtener operadores con IDs internos
// Ruta para mostrar un solo operador por ID
Route::get('/workers/{id}', [OperatorController::class, 'show']);
// Nuevas rutas para reset de contraseña
Route::post('/workers/reset-password-email', [OperatorController::class, 'resetPasswordByEmail']);
Route::post('/workers/reset-password-whatsapp', [OperatorController::class, 'resetPasswordByWhatsapp']);
Route::post('/workers/verify-password', [OperatorController::class, 'verifyPassword']);
// Ruta para eliminar un operador por ID
Route::delete('/workers/{id}', [OperatorController::class, 'destroy']);
Route::post('/scada/log-access', [OperatorController::class, 'logScadaAccess']);
//mostrar los login de scada
Route::post('/scada/get-logins', [OperatorController::class, 'getLoginsByScadaToken']);

//obtener todos los empleados con todo fabricado al dia.
Route::get('/workers/all-list/completed', [OperatorController::class, 'completeList']);





// Routes for Product Lists
Route::post('/product-lists/update-or-insert', [ProductListController::class, 'updateOrInsertSingle']);
Route::post('/product-lists/replace-all', [ProductListController::class, 'replaceAll']);
Route::get('/product-lists/list-all', [ProductListController::class, 'listAll']);
Route::delete('/product-lists/{id}', [ProductListController::class, 'destroy']);

//ver el ultimo status de comunicacion

Route::get('production-line/status/{token}', [ProductionLineStatusController::class, 'getStatusByToken']);

// Maintenance by Production Line Token
Route::post('/maintenances/{token}', [MaintenanceApiController::class, 'storeByToken']);
Route::get('/maintenances/status/{token}', [MaintenanceApiController::class, 'getStatusByToken']);

// Modbus API
Route::middleware(['throttle:90000,1'])->group(function () {
    Route::post('/modbus-process-data-mqtt', [ModbusProcessController::class, 'processMqttData']);
});



//scada orders api show
Route::get('/scada-orders/{token}', [ScadaOrderController::class, 'getOrdersByToken']);
Route::post('/scada-orders/update', [ScadaOrderController::class, 'updateOrderStatus']);
Route::delete('/scada-orders/delete', [ScadaOrderController::class, 'deleteOrder']);
Route::get('/scada-orders/{scadaOrderId}/lines', [ScadaOrderController::class, 'getLinesStatusByScadaOrderId']);
Route::post('/scada-orders/process/update-used', [ScadaOrderController::class, 'updateProcessUsed']);



//scada material api
Route::prefix('scada')->group(function () {
    Route::get('/{token}/material-types', [ScadaMaterialTypeController::class, 'index']);
    Route::post('/{token}/material-types', [ScadaMaterialTypeController::class, 'store']);
    Route::get('/{token}/material-types/{id}', [ScadaMaterialTypeController::class, 'show']);
    Route::put('/{token}/material-types/{id}', [ScadaMaterialTypeController::class, 'update']);
    Route::delete('/{token}/material-types/{id}', [ScadaMaterialTypeController::class, 'destroy']);
});
Route::get('scada-material/{token}', [ScadaMaterialTypeController::class, 'getScadaMaterialByToken']);

// API optimizada para el tablero Kanban
Route::get('/kanban/orders', [\App\Http\Controllers\Api\ProductionOrderController::class, 'getKanbanOrders']);

// API de control de calidad (recibe token, id, texto) -> solo log por ahora
Route::post('/quality-issues', [QualityIssueController::class, 'store'])->name('api.quality-issues.store');

// API de confirmaciones de QC (recibe token, production_order_id|id, operator_id?, notes?)
Route::post('/qc-confirmations', [QcConfirmationController::class, 'store'])->name('api.qc-confirmations.store');

// Subida remota de archivos de proceso usando token de cliente
Route::post('/process-files/upload', [OriginalOrderProcessFileApiController::class, 'store']);
// Borrado de archivos de proceso por ID con autenticación por token
Route::delete('/process-files/{id}', [OriginalOrderProcessFileApiController::class, 'destroy']);

// Orders API
Route::prefix('production-orders')->group(function () {
    Route::get('/', [ProductionOrderController::class, 'index']); // Obtener todas las órdenes
    Route::get('/active-note', [ProductionOrderController::class, 'getActiveOrderNote']); // Obtener anotación de orden activa
    Route::get('/{id}', [ProductionOrderController::class, 'show']); // Obtener una orden específica
    Route::patch('/{id}', [ProductionOrderController::class, 'updateOrder']); // Actualizar una orden
    Route::post('/', [ProductionOrderController::class, 'store']); // Crear una nueva orden
    Route::delete('/{id}', [ProductionOrderController::class, 'destroy']); // Eliminar una orden
});



Route::prefix('/product-list-selecteds')->group(function() {
    Route::get('/', [ProductListSelectedsController::class, 'index']);
    Route::post('/', [ProductListSelectedsController::class, 'store']);
    Route::get('/modbuses', [ProductListSelectedsController::class, 'listModbuses']);
    Route::get('/sensors', [ProductListSelectedsController::class, 'listSensors']);
    Route::get('/{id}', [ProductListSelectedsController::class, 'show']);
    Route::put('/{id}', [ProductListSelectedsController::class, 'update']);
    Route::delete('/{id}', [ProductListSelectedsController::class, 'destroy']);
});




// Listar todos los RFID Readings
Route::get('rfid-readings', [RfidReadingController::class, 'index']);

// Crear un nuevo RFID Reading
Route::post('rfid-readings', [RfidReadingController::class, 'store']);

// Obtener un RFID Reading específico
Route::get('rfid-readings/{id}', [RfidReadingController::class, 'show']);

// Actualizar un RFID Reading existente
Route::put('rfid-readings/{id}', [RfidReadingController::class, 'update']);

// Eliminar un RFID Reading
Route::delete('rfid-readings/{id}', [RfidReadingController::class, 'destroy']);


//Transfer to external DB
Route::post('/transfer-external-db', [TransferExternalDbController::class, 'transferDataToExternal']);




// Rutas para las relaciones entre operadores y sus puestos
Route::get('operator-post', [OperatorPostController::class, 'index']);
Route::post('operator-post', [OperatorPostController::class, 'store']);
Route::get('operator-post/{id}', [OperatorPostController::class, 'show']);
Route::put('operator-post/{id}', [OperatorPostController::class, 'update']);
Route::delete('operator-post/{id}', [OperatorPostController::class, 'destroy']);
Route::post('/operator-post/update-count', [OperatorPostController::class, 'updateCount'])->name('operator-post.update-count');

//publicar tcp mesaje

Route::post('/publish-message', [TcpPublishController::class, 'publishMessage']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


//TOPFLOW API ENDPOINT
//para recivir las referencias por api

// Rutas para el manejo de incidencias en órdenes de producción
Route::prefix('production-orders/{order}/incidents')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProductionOrderIncidentController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ProductionOrderIncidentController::class, 'store']);
});

Route::prefix('reference-Topflow')->group(function () {
    Route::get('/', [ReferenceController::class, 'index']);
    Route::post('/', [ReferenceController::class, 'store']);
    Route::get('/{id}', [ReferenceController::class, 'show']);
});

//api para topflow  a recivir para topflow y despues pasarselos

Route::prefix('topflow-production-order')->group(function () {
    Route::post('/', [ProductionOrderTopflowApiController::class, 'store']);
    Route::get('/{_id}', [ProductionOrderTopflowApiController::class, 'show']);
});


//pasar el pedido de proveedor

Route::post('/supplier-order/store', [SupplierOrderController::class, 'store']);

//api para downtime calculate
Route::match(['get', 'post'], '/calculate-production-downtime', [CalculateProductionDowntimeController::class, 'calculateDowntime']);

// Ruta para generar Excel (existente)
// (Actualizado para coincidir con tu último comentario)
Route::get('/workers-export/generate-excel', [WorkerController::class, 'generateExcelStandalone'])->name('workers-export.generate-excel');

// Ruta para generar PDF (existente)
// (Actualizado para coincidir con tu último comentario)
Route::get('/workers-export/generate-pdf', [WorkerController::class, 'generatePdfStandalone'])->name('workers-export.generate-pdf');

// NUEVA RUTA para enviar informes por correo
Route::get('/workers-export/send-email', [WorkerController::class, 'sendReportsByEmail'])->name('workers-export.send-email');

    // Ruta para enviar el Listado de Asignación por email
Route::get('workers-export/send-assignment-list',[WorkerController::class, 'sendAssignmentListByEmail'])->name('workers.sendAssignmentList');

Route::get('/workers-export/complete-list', [WorkerController::class, 'completeList'])->name('workers.completeListExportstandalone');

Route::post('/shift-process-events', [ShiftProcessEventController::class, 'store'])->name('shift-events.store');

Route::get('rfid-error-points', [RfidErrorPointController::class, 'byDate']);

// Rutas para los Prompts de IA
Route::get('/ia-prompts/{key}', [IaPromptController::class, 'showByKey'])->name('api.ia_prompts.showByKey');

// Ruta opcional para listar todos los prompts activos
Route::get('/ia-prompts', [IaPromptController::class, 'index'])->name('api.ia_prompts.index');

// Rutas para el historial de turnos
Route::get('/shift-history', [\App\Http\Controllers\Api\ShiftHistoryController::class, 'index']);
Route::get('/shift-history/production-line/{id}', [\App\Http\Controllers\Api\ShiftHistoryController::class, 'getByProductionLine']);

// Ruta para obtener los estados actuales de los turnos
Route::get('/shift/statuses', [\App\Http\Controllers\Api\ShiftStatusController::class, 'getStatuses'])->name('api.shift.statuses');

// Rutas para la API de escaneos de códigos de barras
Route::get('/barcode-scans', [BarcodeScansController::class, 'getLastBarcode']);
Route::post('/barcode-scans', [BarcodeScansController::class, 'store']);

// Ruta para obtener los artículos asociados a una orden de producción
Route::get('/production-orders/{id}/articles', [ProductionOrderArticlesController::class, 'getArticles']);
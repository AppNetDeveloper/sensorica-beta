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
use App\Http\Controllers\Api\ProductListRfidController;
use App\Http\Controllers\Api\RfidReadingController;
use App\Http\Controllers\Api\OperatorRfidController;

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
Route::match(['get', 'post'], '/barcode', [ApibarcoderController::class, 'barcode']);
Route::match(['get', 'post'], '/ip-zerotier', [ZerotierIpBarcoderController::class, 'ipZerotier']);



Route::match(['get', 'post'], '/queue-print', [StoreQueueController::class, 'storeQueuePrint']);
Route::match(['get', 'post'], '/queue-print-list', [StoreQueueController::class, 'getQueuePrints']);

Route::get('/modbuses', [ModbusController::class, 'getModbuses']);

Route::get('/control-weights/{token}/all', [ControlWeightController::class, 'getAllDataByToken']);

Route::middleware(['throttle:1000,1'])->group(function () {
    Route::get('/control-weight/{token}', [ControlWeightController::class, 'getDataByToken']);
});


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
Route::match(['get', 'post'], '/sensors/{token}', [SensorController::class, 'getByToken']);
Route::match(['get', 'post'], '/sensors', [SensorController::class, 'getAllSensors']);

Route::post('/sensor-insert', [SensorController::class, 'sensorInsert']);

Route::match(['get', 'post'], '/order-stats', [OrderStatsController::class, 'getLastOrderStat']);

Route::match(['get', 'post'], '/order-stats-all', [OrderStatsController::class, 'getOrderStatsBetweenDates']);


Route::get('scada/{token}', [ScadaController::class, 'getModbusesByScadaToken']);

Route::get('scada-material/{token}', [ScadaMaterialTypeController::class, 'getScadaMaterialByToken']);

Route::put('/modbus/{modbusId}/material', [ScadaController::class, 'updateMaterialForModbus']);

Route::post('/modbus/send', [ModbusController::class, 'sendDosage']);

Route::post('/modbus/zero', [ModbusController::class, 'setZero']);

Route::post('/modbus/tara', [ModbusController::class, 'setTara']);
Route::post('/modbus/tara/reset', [ModbusController::class, 'resetTara']);



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
Route::get('/server-stats', [SystemController::class, 'getServerStats']);
Route::post('/restart-supervisor', [SystemController::class, 'restartSupervisor']);
Route::post('/stop-supervisor', [SystemController::class, 'stopSupervisor']);
Route::post('/start-supervisor', [SystemController::class, 'startSupervisor']);
Route::post('/restart-485-Swift', [SystemController::class, 'restart485Swift']);
Route::post('/run-update', [SystemController::class, 'runUpdateScript']);

// Routes for Operators
Route::post('/workers/update-or-insert', [OperatorController::class, 'updateOrInsertSingle']);
Route::post('/workers/replace-all', [OperatorController::class, 'replaceAll']);
Route::get('/workers/list-all', [OperatorController::class, 'listAll']);
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




// Routes for Product Lists
Route::post('/product-lists/update-or-insert', [ProductListController::class, 'updateOrInsertSingle']);
Route::post('/product-lists/replace-all', [ProductListController::class, 'replaceAll']);
Route::get('/product-lists/list-all', [ProductListController::class, 'listAll']);
Route::delete('/product-lists/{id}', [ProductListController::class, 'destroy']);




//ver el ultimo status de comunicacion

Route::get('production-line/status/{token}', [ProductionLineStatusController::class, 'getStatusByToken']);

//mosbus api
Route::middleware(['throttle:3000,1'])->group(function () {
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


// Orders API
Route::prefix('production-orders')->group(function () {
    Route::get('/', [ProductionOrderController::class, 'index']); // Obtener todas las órdenes
    Route::get('/{id}', [ProductionOrderController::class, 'show']); // Obtener una orden específica
    Route::patch('/{id}', [ProductionOrderController::class, 'updateOrder']); // Actualizar una orden
    Route::post('/', [ProductionOrderController::class, 'store']); // Crear una nueva orden
    Route::delete('/{id}', [ProductionOrderController::class, 'destroy']); // Eliminar una orden
});


// ProductListRfid API

// Obtener todas las relaciones entre ProductList y RFID
Route::get('product-list-rfids', [ProductListRfidController::class, 'index']);

// Crear una nueva relación entre ProductList y RFID
Route::post('product-list-rfids', [ProductListRfidController::class, 'store']);

// Obtener una relación específica entre ProductList y RFID
Route::get('product-list-rfids/{id}', [ProductListRfidController::class, 'show']);

// Actualizar una relación específica entre ProductList y RFID
Route::put('product-list-rfids/{id}', [ProductListRfidController::class, 'update']);

// Eliminar una relación específica entre ProductList y RFID
Route::delete('product-list-rfids/{id}', [ProductListRfidController::class, 'destroy']);





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





// Rutas para las relaciones entre operadores y RFID readings
Route::get('operator-rfid', [OperatorRfidController::class, 'index']);
Route::post('operator-rfid', [OperatorRfidController::class, 'store']);
Route::get('operator-rfid/{id}', [OperatorRfidController::class, 'show']);
Route::put('operator-rfid/{id}', [OperatorRfidController::class, 'update']);
Route::delete('operator-rfid/{id}', [OperatorRfidController::class, 'destroy']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


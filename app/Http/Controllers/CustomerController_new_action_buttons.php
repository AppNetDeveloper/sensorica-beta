// FRAGMENTO PARA REEMPLAZAR EN CustomerController.php líneas 51-207
// Construir botones organizados por categorías temáticas
$allButtons = '';

// 🏭 FÁBRICA: Producción y planificación
$factoryActions = [];
if (auth()->user()->can('productionline-kanban')) {
    $orderOrganizerUrl = route('customers.order-organizer', $customer->id);
    $factoryActions[] = "<a href='{$orderOrganizerUrl}' class='btn btn-sm btn-primary me-1 mb-1'><i class='fas fa-tasks'></i> " . __('Kanban') . "</a>";
}
if (auth()->user()->can('productionline-show')) {
    $factoryActions[] = "<a href='{$productionLinesUrl}' class='btn btn-sm btn-secondary me-1 mb-1'><i class='fas fa-sitemap'></i> " . __('Líneas') . "</a>";
}
if (auth()->user()->can('productionline-orders')) {
    $originalOrdersUrl = route('customers.original-orders.index', $customer->id);
    $factoryActions[] = "<a href='{$originalOrdersUrl}' class='btn btn-sm btn-dark me-1 mb-1'><i class='fas fa-clipboard-list'></i> " . __('Pedidos') . "</a>";
}
if (auth()->user()->can('original-order-list')) {
    $finishedProcessesUrl = route('customers.original-orders.finished-processes.view', $customer->id);
    $factoryActions[] = "<a href='{$finishedProcessesUrl}' class='btn btn-sm btn-outline-dark me-1 mb-1'><i class='fas fa-chart-line'></i> " . __('Procesos finalizados') . "</a>";
}
if (auth()->user()->can('workcalendar-list')) {
    $workCalendarUrl = route('customers.work-calendars.index', $customer->id);
    $factoryActions[] = "<a href='{$workCalendarUrl}' class='btn btn-sm btn-info me-1 mb-1'><i class='fas fa-calendar-alt'></i> " . __('Calendario') . "</a>";
}
if (!empty($factoryActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-industry me-1'></i>" . __('Fábrica') . "</div>" . implode('', $factoryActions) . "</div>";
}

// 📦 ALMACÉN: Activos, inventario y compras
$warehouseActions = [];
if (auth()->user()->can('assets-view')) {
    $assetsUrl = route('customers.assets.index', $customer->id);
    $inventoryUrl = route('customers.assets.inventory', $customer->id);
    $warehouseActions[] = "<a href='{$assetsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-box'></i> " . __('Activos') . "</a>";
    $warehouseActions[] = "<a href='{$inventoryUrl}' class='btn btn-sm btn-outline-primary me-1 mb-1'><i class='fas fa-chart-column'></i> " . __('Inventario') . "</a>";
}
if (auth()->user()->can('asset-categories-view')) {
    $assetCategoriesUrl = route('customers.asset-categories.index', $customer->id);
    $warehouseActions[] = "<a href='{$assetCategoriesUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-layer-group'></i> " . __('Categorías') . "</a>";
}
if (auth()->user()->can('asset-cost-centers-view')) {
    $assetCostCentersUrl = route('customers.asset-cost-centers.index', $customer->id);
    $warehouseActions[] = "<a href='{$assetCostCentersUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-coins'></i> " . __('Centros coste') . "</a>";
}
if (auth()->user()->can('asset-locations-view')) {
    $assetLocationsUrl = route('customers.asset-locations.index', $customer->id);
    $warehouseActions[] = "<a href='{$assetLocationsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-warehouse'></i> " . __('Ubicaciones') . "</a>";
}
if (auth()->user()->can('vendor-suppliers-view')) {
    $supplierUrl = route('customers.vendor-suppliers.index', $customer->id);
    $warehouseActions[] = "<a href='{$supplierUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-industry'></i> " . __('Proveedores') . "</a>";
}
if (auth()->user()->can('vendor-items-view')) {
    $itemsUrl = route('customers.vendor-items.index', $customer->id);
    $warehouseActions[] = "<a href='{$itemsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-boxes-stacked'></i> " . __('Productos') . "</a>";
}
if (auth()->user()->can('vendor-orders-view')) {
    $ordersUrl = route('customers.vendor-orders.index', $customer->id);
    $warehouseActions[] = "<a href='{$ordersUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-file-invoice-dollar'></i> " . __('Pedidos proveedor') . "</a>";
}
if (!empty($warehouseActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-warehouse me-1'></i>" . __('Almacén') . "</div>" . implode('', $warehouseActions) . "</div>";
}

// ⚙️ AJUSTES: Configuración
$settingsActions = [];
if (auth()->user()->can('productionline-edit')) {
    $settingsActions[] = "<a href='{$editUrl}' class='btn btn-sm btn-info me-1 mb-1'><i class='fas fa-edit'></i> " . __('Editar') . "</a>";
}
if (!empty($settingsActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-sliders-h me-1'></i>" . __('Ajustes') . "</div>" . implode('', $settingsActions) . "</div>";
}

// 🔧 MANTENIMIENTO
if (auth()->user()->can('maintenance-show')) {
    $maintenancesUrl = route('customers.maintenances.index', $customer->id);
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-wrench me-1'></i>" . __('Mantenimiento') . "</div><a href='{$maintenancesUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-wrench'></i> " . __('Mantenimiento') . "</a></div>";
}

// 🚚 LOGÍSTICA: Flota, clientes y rutas
$logisticsActions = [];
if (auth()->user()->can('fleet-view')) {
    $fleetUrl = route('customers.fleet-vehicles.index', $customer->id);
    $logisticsActions[] = "<a href='{$fleetUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-truck'></i> " . __('Flota') . "</a>";
}
if (auth()->user()->can('customer-clients-view')) {
    $clientsUrl = route('customers.clients.index', $customer->id);
    $logisticsActions[] = "<a href='{$clientsUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-user-friends'></i> " . __('Clientes') . "</a>";
}
if (auth()->user()->can('routes-view')) {
    $routesUrl = route('customers.routes.index', $customer->id);
    $logisticsActions[] = "<a href='{$routesUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-route'></i> " . __('Rutas') . "</a>";
}
if (auth()->user()->can('route-names-view')) {
    $routeNamesUrl = route('customers.route-names.index', $customer->id);
    $logisticsActions[] = "<a href='{$routeNamesUrl}' class='btn btn-sm btn-outline-secondary me-1 mb-1'><i class='fas fa-list'></i> " . __('Diccionario rutas') . "</a>";
}
if (!empty($logisticsActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-truck-moving me-1'></i>" . __('Logística') . "</div>" . implode('', $logisticsActions) . "</div>";
}

// 📊 ESTADÍSTICAS: Monitorización
$statsActions = [];
if (auth()->user()->can('productionline-weight-stats')) {
    $statsActions[] = "<a href='{$liveViewUrl}' target='_blank' class='btn btn-sm btn-success me-1 mb-1'><i class='fas fa-weight-hanging'></i> " . __('Weight Stats') . "</a>";
}
if (auth()->user()->can('productionline-production-stats')) {
    $statsActions[] = "<a href='{$liveViewUrlProd}' target='_blank' class='btn btn-sm btn-warning me-1 mb-1'><i class='fas fa-chart-line'></i> " . __('Production Stats') . "</a>";
    $statsActions[] = "<a href='{$customerSensorsUrl}' class='btn btn-sm btn-outline-success me-1 mb-1'><i class='fas fa-microchip'></i> " . __('Sensors') . "</a>";
}
if (!empty($statsActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-chart-bar me-1'></i>" . __('Estadísticas') . "</div>" . implode('', $statsActions) . "</div>";
}

// ⚠️ INCIDENCIAS: Calidad
$qualityActions = [];
if (auth()->user()->can('productionline-incidents')) {
    $incidentsUrl = route('customers.production-order-incidents.index', $customer->id);
    $qualityActions[] = "<a href='{$incidentsUrl}' class='btn btn-sm btn-danger me-1 mb-1'><i class='fas fa-exclamation-triangle'></i> " . __('Incidencias') . "</a>";
    $qcIncidentsUrl = route('customers.quality-incidents.index', $customer->id);
    $qualityActions[] = "<a href='{$qcIncidentsUrl}' class='btn btn-sm btn-outline-danger me-1 mb-1'><i class='fas fa-vial'></i> " . __('QC') . "</a>";
    $qcConfirmationsUrl = route('customers.qc-confirmations.index', $customer->id);
    $qualityActions[] = "<a href='{$qcConfirmationsUrl}' class='btn btn-sm btn-outline-primary me-1 mb-1'><i class='fas fa-clipboard-check'></i> " . __('QC Confirmations') . "</a>";
}
if (!empty($qualityActions)) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-exclamation-circle me-1'></i>" . __('Incidencias') . "</div>" . implode('', $qualityActions) . "</div>";
}

// 🔌 INTEGRACIONES
if (auth()->user()->can('callbacks.view')) {
    $callbacksUrl = route('customers.callbacks.index', $customer->id);
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label'><i class='fas fa-plug me-1'></i>" . __('Integraciones') . "</div><a href='{$callbacksUrl}' class='btn btn-sm btn-outline-dark me-1 mb-1'><i class='fas fa-plug'></i> " . __('Callbacks') . "</a></div>";
}

// ☠️ CRÍTICO
if (auth()->user()->can('productionline-delete')) {
    $allButtons .= "<div class='btn-group-section'><div class='btn-group-label text-danger'><i class='fas fa-skull-crossbones me-1'></i>" . __('Crítico') . "</div><form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit='return confirm(\"" . __('Are you sure?') . "\");'><input type='hidden' name='_token' value='{$csrfToken}'><input type='hidden' name='_method' value='DELETE'><button type='submit' class='btn btn-sm btn-outline-danger me-1 mb-1'><i class='fas fa-trash'></i> " . __('Delete') . "</button></form></div>";
}

return "<div class='action-buttons-row d-flex flex-wrap' style='display: none; gap: 12px; padding: 12px; background-color: #f8f9fa; border-radius: 8px; margin-top: 8px;'>" . $allButtons . "</div>";

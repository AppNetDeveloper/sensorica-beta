# Módulo de Control de Activos e Inventario

## Resumen

Este módulo permite registrar, clasificar y localizar activos físicos por cliente (`customer_id`). Incluye soporte para códigos de barras/QR y etiquetas RFID (EPC/TID), así como centros de coste y ubicaciones.

## Tablas principales

- **asset_cost_centers**: centros de coste por cliente (`code`, `name`).
- **asset_categories**: jerarquía de categorías con `label_code` (para QR/Barcode) y `rfid_epc` opcional.
- **asset_locations**: almacenes/puntos físicos.
- **assets**: activos individuales con `article_code`, `label_code`, `status`, `rfid_tid`, `rfid_epc`, atributos JSON y relaciones con proveedor.
- **asset_receipts**: cabecera de recepciones de mercancía vinculadas a `vendor_orders`, con referencia, usuario receptor y notas.
- **asset_receipt_lines**: detalle por línea de pedido recibido. Permite asociar la recepción con un activo generado automáticamente.
- **asset_events**: historial de movimientos/acciones sobre cada activo.

## Permisos y policies

Seeder: `AssetManagementPermissionsSeeder` genera:
- `asset-categories-view/create/edit/delete`
- `asset-cost-centers-view/create/edit/delete`
- `asset-locations-view/create/edit/delete`
- `assets-view/create/edit/delete/print-label`
- `asset-receipts-view/create/edit/delete`

Policies registradas en `AuthServiceProvider`:
- `AssetCostCenterPolicy`
- `AssetCategoryPolicy`
- `AssetLocationPolicy`
- `AssetPolicy`
- `AssetReceiptPolicy`

## Controladores y vistas

- `AssetCostCenterController` → vistas en `resources/views/customers/asset-cost-centers/`
- `AssetCategoryController` → vistas en `resources/views/customers/asset-categories/`
- `AssetLocationController` → vistas en `resources/views/customers/asset-locations/`
- `AssetController` → vistas en `resources/views/customers/assets/`
  - Incluye impresión de etiquetas (`assets/label.blade.php`) con JsBarcode + QRCode.

Todas las tablas (`index.blade.php`) utilizan DataTables con exportes Excel/PDF/Print y traducción `assets/vendor/datatables/i18n/es_es.json`.

## Flujos básicos

1. Configurar centros de coste y ubicaciones por cliente.
2. Crear categorías de activos (con `label_code` y, opcionalmente, `rfid_epc`).
3. Registrar pedidos a proveedor y, desde la pestaña **Recepciones**, capturar las entradas de almacén (`asset_receipts`).
   - Opcional: marcar "Crear activo automáticamente" para generar `assets` con códigos únicos y vínculos a la recepción.
4. Revisar en **Activos** que los registros creados disponen de etiqueta QR/barcode y metadatos `vendor_order_id` / `asset_receipt_id`.
5. Generar etiquetas (barcode + QR) y, si aplica, programar RFID (EPC/TID).
6. Consultar detalle (`show`) con historial de eventos (`asset_events`) y atributos JSON.

## Estados de activo

Valores soportados (`AssetController::STATUSES`):
- `active`
- `inactive`
- `maintenance`
- `retired`

## Rutas

Ubicadas en `routes/web.php` bajo `customers/{customer}`:
```
Route::resource('asset-cost-centers', AssetCostCenterController::class)->except(['show']);
Route::resource('asset-categories', AssetCategoryController::class)->except(['show']);
Route::resource('asset-locations', AssetLocationController::class)->except(['show']);
Route::get('assets/{asset}/label', [AssetController::class, 'printLabel'])->name('assets.print-label');
Route::resource('assets', AssetController::class);
```

## Botones en panel de clientes

`CustomerController@getCustomers()` agrega accesos directos condicionados por permisos a:
- `Activos`
- `Categorías de activos`
- `Centros de coste`
- `Ubicaciones de activos`

## Próximos pasos sugeridos

- Añadir import/export masivo de activos.
- Integrar lectores RFID para inventario cíclico (`POST /api/rfid/scans`).
- Registrar eventos automáticamente al cambiar estado/ubicación.
- Generar reportes de depreciación y consumo por centro de coste.

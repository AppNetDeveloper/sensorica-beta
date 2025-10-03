# Módulo de Pedidos a Proveedor

## Resumen

El módulo de **Pedidos a Proveedor** amplía la plataforma para gestionar compras de materiales y servicios por fábrica (`customer_id`). Proporciona catálogos de proveedores, productos de compra y pedidos, con permisos y políticas específicos.

## Componentes principales

- **Proveedores** (`vendor_suppliers`)
  - Campos clave: `name`, `tax_id`, `email`, `phone`, `contact_name`, `payment_terms`, `metadata`
  - CRUD vía `VendorSupplierController`
  - Vista: `resources/views/customers/vendor-suppliers/`

- **Productos de compra** (`vendor_items`)
  - Campos clave: `name`, `sku`, `vendor_supplier_id`, `unit_of_measure`, `unit_price`, `lead_time_days`, `metadata`
  - CRUD vía `VendorItemController`
  - Vista: `resources/views/customers/vendor-items/`

- **Pedidos a proveedor** (`vendor_orders` y `vendor_order_lines`)
  - Campos clave pedido: `reference`, `status`, `currency`, `expected_at`, `total_amount`
  - Líneas con cantidades, precios, impuestos y referencia opcional a `vendor_items`
  - Controlador: `VendorOrderController`
  - Vista: `resources/views/customers/vendor-orders/`

## Permisos y políticas

Seeder: `VendorProcurementPermissionsSeeder`

Permisos generados:
- `vendor-suppliers-view/create/edit/delete`
- `vendor-items-view/create/edit/delete`
- `vendor-orders-view/create/edit/delete`

Policies registradas en `AuthServiceProvider`:
- `VendorSupplierPolicy`
- `VendorItemPolicy`
- `VendorOrderPolicy`

## Enlaces desde el panel de clientes

En `CustomerController@getCustomers` se añaden botones en las acciones del cliente:
- "Proveedores"
- "Productos de compra"
- "Pedidos proveedor"

Cada botón respeta los permisos correspondientes.

## UI

Las vistas utilizan DataTables con búsquedas, orden y diseño responsive. Los archivos JS y estilos se cargan desde CDNs y apuntan al archivo de traducción `assets/vendor/datatables/i18n/es_es.json`.

## Migraciones

`database/migrations/2025_10_02_171000_create_vendor_procurement_tables.php` crea las tablas necesarias y llaves foráneas.

## Rutas

Recursos anidados bajo `customers/{customer}` en `routes/web.php`:
```
Route::resource('vendor-suppliers', VendorSupplierController::class)->except(['show']);
Route::resource('vendor-items', VendorItemController::class)->except(['show']);
Route::resource('vendor-orders', VendorOrderController::class);
```

## Próximos pasos sugeridos

- Añadir tests feature (`tests/Feature/VendorProcurement/...`).
- Evaluar adjuntos para pedidos (`vendor_order_documents`).
- Configurar prefecto de usuarios por fábrica (`customer_id`) si no está en el modelo `User`.

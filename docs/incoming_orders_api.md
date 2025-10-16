# API de Webhooks para Órdenes Entrantes

Esta API permite a sistemas externos crear, actualizar y eliminar órdenes originales (`original_orders`) y sus procesos/artículos asociados directamente, sin necesidad de mapeos personalizados por cliente.

## Autenticación

Todas las peticiones deben incluir el token del cliente en uno de estos formatos:

- Header `Authorization: Bearer <token>`
- Header `X-Customer-Token: <token>`
- Query param `?token=<token>`

El token se corresponde con el campo `token` de la tabla `customers`.

## Endpoints

### Crear o Actualizar Orden

**POST** `/api/incoming/original-orders`

Crea una nueva orden o actualiza una existente (upsert por `order_id`).

#### Parámetros de Query

- `reprocess` (opcional): Si es `true` y la orden ya existe, la borra completamente y la recrea desde cero. Por defecto es `false`.

#### Payload de Ejemplo

```json
{
  "order_id": "A-12345",
  "client_number": "C-777",
  "route_name": "Ruta Centro",
  "ref_order": "PED-98765",
  "delivery_date": "2025-09-30",
  "fecha_pedido_erp": "2025-09-20",
  "in_stock": 1,
  "address": "Av. Industria 42, Nave 3",
  "phone": "+34 600 111 222",
  "cif_nif": "B12345678",
  "grupos": [
    {
      "grupoNum": "1",
      "servicios": [
        {
          "process_code": "S.201",
          "time_seconds": 1800,
          "box": 0,
          "units_box": 0,
          "number_of_pallets": 0
        },
        {
          "process_code": "S.305",
          "time_seconds": 900,
          "box": 2,
          "units_box": 12,
          "number_of_pallets": 1
        }
      ],
      "articulos": [
        {
          "codigo_articulo": "2.H3710ST12.19",
          "descripcion_articulo": "Tablero H3710",
          "grupo_articulo": "LAMINADO",
          "in_stock": 1
        },
        {
          "codigo_articulo": "ACC123",
          "descripcion_articulo": "Kit herrajes",
          "grupo_articulo": "ACCESORIOS",
          "in_stock": 0
        }
      ]
    },
    {
      "grupoNum": "2",
      "servicios": [
        {
          "process_code": "S.450",
          "time_seconds": 2400,
          "box": 1,
          "units_box": 6,
          "number_of_pallets": 0
        }
      ],
      "articulos": [
        {
          "codigo_articulo": "PINT001",
          "descripcion_articulo": "Barniz mate 5L",
          "grupo_articulo": "ACABADOS",
          "in_stock": 1
        }
      ]
    }
  ]
}
```

#### Comportamiento

- **Si la orden NO existe**:
  - Crea la orden con los datos proporcionados
  - Crea los procesos a partir de `grupos.servicios`
  - Crea los artículos a partir de `grupos.articulos`
  - Si no se crea ningún proceso válido, elimina la orden

- **Si la orden YA existe y `reprocess=false` (por defecto)**:
  - Actualiza campos ligeros (`client_number`, `route_name`, `delivery_date`, `fecha_pedido_erp`, `in_stock`)
  - Guarda el JSON completo en `order_details`
  - NO reprocesa procesos ni artículos

- **Si la orden YA existe y `reprocess=true`**:
  - Elimina completamente la orden existente y sus procesos/artículos
  - Crea una nueva orden con los datos proporcionados
  - Crea nuevos procesos y artículos

#### Respuesta Exitosa (200 OK)

```json
{
  "message": "Order created",
  "order_id": "A-12345",
  "id": 123
}
```

### Eliminar Orden

**DELETE** `/api/incoming/original-orders/{order_id}`

Elimina una orden y todos sus procesos y artículos asociados.

#### Parámetros de Path

- `order_id`: El ID externo de la orden a eliminar

#### Respuesta Exitosa (200 OK)

```json
{
  "message": "Order deleted",
  "order_id": "A-12345"
}
```

## Notas Importantes

1. **Códigos de Proceso**: El campo `process_code` debe coincidir exactamente con un código existente en la tabla `processes` (campo `code`). Si no existe, se ignora ese servicio.

2. **Factor de Corrección**: El tiempo proporcionado en `time_seconds` se multiplica por el factor de corrección del proceso (`processes.factor_correccion`).

3. **Artículos**: Los artículos siempre deben estar asociados a un proceso dentro del mismo grupo.

4. **Duplicados**: Se evitan duplicados por:
   - Procesos: `(original_order_id + process_id + grupo_numero)`
   - Artículos: `(original_order_process_id + codigo_articulo [+ grupo_articulo])`

5. **Campos de Fecha**: `delivery_date` y `fecha_pedido_erp` deben estar en formato `YYYY-MM-DD`.

6. **Campo `route_name` (opcional)**:
   - Si se proporciona, la API buscará un registro en `route_names` por `customer_id` y `name`.
   - Si existe, su ID se guarda en `original_orders.route_name_id`.
   - Si no existe, se crea automáticamente (`active=true`, `days_mask=0`) y se guarda su ID.
   - Si no se envía, `route_name_id` permanecerá `null`.

## Ejemplos de Uso con cURL

### Crear una nueva orden

```bash
curl -X POST \
  http://tu-servidor/api/incoming/original-orders \
  -H 'Authorization: Bearer tu-token-aqui' \
  -H 'Content-Type: application/json' \
  -d '{
    "order_id": "A-12345",
    "client_number": "C-777",
    "route_name": "Ruta Centro",
    "delivery_date": "2025-09-30",
    "grupos": [
      {
        "grupoNum": "1",
        "servicios": [
          {
            "process_code": "S.201",
            "time_seconds": 1800
          }
        ],
        "articulos": [
          {
            "codigo_articulo": "2.H3710ST12.19",
            "descripcion_articulo": "Tablero H3710"
          }
        ]
      }
    ]
  }'
```

### Actualizar una orden existente con reprocess

```bash
curl -X POST \
  'http://tu-servidor/api/incoming/original-orders?reprocess=true' \
  -H 'Authorization: Bearer tu-token-aqui' \
  -H 'Content-Type: application/json' \
  -d '{
    "order_id": "A-12345",
    "client_number": "C-777",
    "delivery_date": "2025-09-30",
    "grupos": [
      {
        "grupoNum": "1",
        "servicios": [
          {
            "process_code": "S.201",
            "time_seconds": 2400
          }
        ]
      }
    ]
  }'
```

### Eliminar una orden

```bash
curl -X DELETE \
  http://tu-servidor/api/incoming/original-orders/A-12345 \
  -H 'Authorization: Bearer tu-token-aqui'
```

## Persistencia y Relaciones Internas

- **Tabla `original_orders`**: Campos principales `order_id` (único), `customer_id`, `customer_client_id`, `route_name_id`, `client_number`, `order_details` (JSON íntegro del payload), `processed`, `finished_at`, `delivery_date`, `estimated_delivery_date`, `actual_delivery_date`, `in_stock` y `fecha_pedido_erp`. Se gestionan a través de las migraciones `2025_06_12_152000_create_original_orders_table.php`, `2025_09_24_095700_add_delivery_dates_to_original_orders_table.php`, `2025_09_24_100600_add_customer_client_and_route_name_to_original_orders_table.php` y añadidos posteriores.
- **Modelo `OriginalOrder`** (`app/Models/OriginalOrder.php`): Define `fillable`, `casts` y `dates` coherentes con la tabla, desactiva eventos automáticos y expone métodos utilitarios como `allProcessesFinished()`, `updateInStockStatus()` y `updateFinishedStatus()` (este último usa `saveQuietly()` para evitar ciclos en eventos).
- **Relaciones clave**:
  - `processes()` many-to-many vía `original_order_processes` usando el pivot `OriginalOrderProcess` con campos extra (`time`, `created`, `finished`, `finished_at`, `grupo_numero`, `box`, `units_box`, `number_of_pallets`, `in_stock`).
  - `orderProcesses()`/`originalOrderProcesses()` para consultas directas al pivot.
  - `articles()` hasManyThrough hacia `OriginalOrderArticle` enlazando artículos con el proceso correspondiente.
  - `productionOrders()` hasMany para enlazar las órdenes del kanban (`ProductionOrder`).
  - `qcConfirmations()` hasMany hacia confirmaciones de calidad.

## Flujos de Creación y Actualización

- **Webhook entrante** (este documento) y **comando `orders:check`** (`app/Console/Commands/CheckOrdersFromApi.php`) generan o actualizan registros en `original_orders` respetando la lógica de reprocesamiento e ignorando procesos/artículos inválidos.
- **Creación manual** (`CustomerOriginalOrderController@store`):
  - Valida campos básicos y procesa `order_details` para calcular `time` por proceso multiplicando `Cantidad` por `processes.factor_correccion`.
  - Sincroniza pivots con flags `created`/`finished` inicializados en `false`.
- **Bulk delete** (`CustomerOriginalOrderController@bulkDelete`): Elimina múltiples órdenes y encadena cascadas (procesos, artículos, órdenes de producción).
- **Importación manual** (`CustomerOriginalOrderController@import`): Lanza `php artisan orders:check` en background usando un lock file `storage/app/orders_check.lock` para evitar ejecuciones simultáneas.

## Cálculo de Estado y Vistas de Gestión

- **Listado principal** (`CustomerOriginalOrderController@index` + vista `customers/original-orders/index.blade.php`):
  - Carga relaciones `processes` + `ProductionOrder` asociadas, calcula un **nivel de estado** por pedido (0 *Pendiente crear*, 1 *Planificar*, 2 *Asignado*, 3 *Iniciado*, 4 *Finalizado*) combinando flags `finished`, asignaciones y `status` de órdenes de producción.
  - Ofrece filtros DataTables por estado (`to-create`, `planned`, `assigned`, `started`, `finished`) y paginación en memoria.
- **Vista de detalle** (`CustomerOriginalOrderController@show`): Expone procesos, artículos y órdenes de producción (`productionOrders`) con sus tiempos estimados (`estimated_start_datetime`, `estimated_end_datetime`).
- **Vista de procesos finalizados** (`finishedProcessesView`/`finishedProcessesData`): Endpoints DataTables que listan pivots con `finished_at` dentro de un rango de fechas, usando joins dinámicos para ordenar por `order_id` o descripción de proceso.

## Sincronización de Estados

- `OriginalOrder::updateFinishedStatus()` mantiene `finished_at` según `allProcessesFinished()`. Al no existir eventos automáticos en el modelo, **debe llamarse explícitamente** tras cualquier cambio en procesos pivot.
- `OriginalOrder::updateInStockStatus()` sincroniza `in_stock` de la orden con la agregación de `in_stock` en procesos. También requiere invocación manual al actualizar pivots.
- Los comandos y controladores que modifiquen pivots deben garantizar que estos métodos se ejecuten para mantener consistencia con el kanban y reportes.

## Consideraciones y Próximos Pasos

- **Rendimiento DataTables**: El cálculo del estado en `CustomerOriginalOrderController@index` se realiza en memoria tras cargar todas las órdenes filtradas; para grandes volúmenes puede requerir optimizaciones (subconsultas o agregaciones SQL).
- **Ejecuciones programadas**: El supervisor lanza `orders:check` cada 5 minutos. Cualquier cambio en los eventos de `OriginalOrderProcess` debe revisarse para evitar efectos secundarios en el kanban (`ProductionOrder`), especialmente si se reactivan listeners como el `saved` previamente deshabilitado.
- **Documentación adicional**: Mantener este documento actualizado si se agregan nuevos campos pivot (`original_order_processes`), cambios en la lógica de estado o nuevos endpoints relacionados con `original_orders`.

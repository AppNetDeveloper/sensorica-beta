### Gestión de Pedidos a Proveedor

Módulo dedicado a la gestión de compras por fábrica (`customer_id`). Incluye catálogos de proveedores, productos de compra y pedidos.

- **Documentación detallada**: ver `docs/vendor-procurement.md`
- **Rutas**: `customers/{customer}/vendor-suppliers`, `vendor-items`, `vendor-orders`
- **Permisos**: `vendor-suppliers-*`, `vendor-items-*`, `vendor-orders-*` (Seeder `VendorProcurementPermissionsSeeder`)
- **Recepciones de almacén**: desde la vista del pedido (`vendor-orders.show`) se pueden registrar entradas con pestaña **Recepciones**, generar KPIs de avance y crear activos automáticamente (requiere permisos `asset-receipts-*`).
- **Vista de acciones**: accesos rápidos desde `customers/index`

# SENSORICA - Sistema Integral de Gestión de Producción Industrial

<p align="center">
  <img src="public/img/logo.png" alt="Logo Sensorica" width="300">
</p>

## 📋 Índice

- [Descripción General](#descripción-general)
- [Características Principales](#características-principales)
  - [Sistema Kanban](#sistema-kanban)
  - [Monitoreo OEE](#monitoreo-oee)
  - [Gestión de Sensores](#gestión-de-sensores)
  - [Integración con APIs Externas](#integración-con-apis-externas)
  - [Gestión de Incidencias](#gestión-de-incidencias)
  - [Control de Calidad (QC): Incidencias y Confirmaciones](#control-de-calidad-qc-incidencias-y-confirmaciones)
  - [Control de Activos e Inventario](#control-de-activos-e-inventario)
  - [Gestión de Pedidos a Proveedor](#gestión-de-pedidos-a-proveedor)
  - [Tecnologías Utilizadas](#tecnologías-utilizadas)
  - [Requisitos del Sistema](#requisitos-del-sistema)
  - [Instalación y Configuración](#instalación-y-configuración)
  - [Estructura de la Base de Datos](#estructura-de-la-base-de-datos)
  - [Servicios en Segundo Plano](#servicios-en-segundo-plano)
- [🧭 Mapa de funcionalidades](#🧭-mapa-de-funcionalidades-qué-puede-hacer-la-app)
- [📚 Dónde está cada cosa](#📚-dónde-está-cada-cosa-mapa-de-código)
- [🔄 Flujos clave](#🔄-flujos-clave)
- [🔐 Acceso y seguridad](#🔐-acceso-y-seguridad)
- [🚀 Quickstart](#🚀-quickstart-cómo-empezar)
- [🔗 URLs útiles / Navegación](#🔗-urls-útiles--navegación)
- [🛡️ Operación y mantenimiento](#🛡️-operación-y-mantenimiento)
  - [📦 Copias de seguridad automáticas](#📦-copias-de-seguridad-automáticas)
  - [🔒 Seguridad operacional](#🔒-seguridad-operacional)
  - [🛠️ Comandos Artisan](#🛠️-comandos-artisan)
- [🏗️ Infraestructura y despliegue](#🏗️-infraestructura-y-despliegue)
  - [Base de datos: Percona Server for MySQL](#base-de-datos-percona-server-for-mysql)
  - [Servidor web: Caddy](#servidor-web-caddy)
  - [Red y acceso seguro: ZeroTier + Cloudflare Tunnels](#red-y-acceso-seguro-zerotier--cloudflare-tunnels)
  - [🔧 Sistema de Monitoreo de Cloudflare Tunnel](#🔧-sistema-de-monitoreo-de-cloudflare-tunnel)
- [Licencia](#licencia)
 - [🤖 Integración IA (Análisis con Ollama)](#🤖-integración-ia-análisis-con-ollama)

## 📄 Descripción General

Sensorica es una plataforma integral para la gestión y monitorización de procesos industriales en tiempo real. El sistema permite la visualización, seguimiento y control de líneas de producción a través de tableros Kanban, monitoreo OEE (Overall Equipment Effectiveness), integración con sensores IoT, y gestión completa de órdenes de producción.

{{ ... }}
Diseñado para entornos industriales exigentes, Sensorica ofrece una interfaz intuitiva que permite a los operadores y gerentes de producción optimizar los flujos de trabajo, identificar cuellos de botella, y mejorar la eficiencia general de la planta.

## 🌟 Características Principales

- **Sistema Kanban Avanzado**: Gestión visual de órdenes de producción con arrastrar y soltar (drag & drop) entre diferentes estados.
- **Monitoreo OEE en Tiempo Real**: Cálculo y visualización de métricas de eficiencia global de equipos.
- **Integración IoT**: Conexión con sensores industriales a través de MQTT para monitoreo en tiempo real.
- **Gestión de Clientes Multiempresa**: Soporte para múltiples clientes con configuraciones independientes.
- **Mapeo de Campos Personalizable**: Sistema flexible para mapear campos de APIs externas a estructuras internas.
- **Gestión de Incidencias**: Sistema completo para registro y seguimiento de problemas en la producción.
- **Calendario Laboral**: Configuración de días laborables y turnos para cálculos precisos de producción.
- **Integración con ERPs**: Conexión bidireccional con sistemas ERP externos.
- **Panel de Control en Tiempo Real**: Visualización de estadísticas y KPIs de producción.
- **🚚 Sistema de Planificación de Rutas**: Módulo completo para la gestión de rutas de entrega y asignación de vehículos.

## 🏗️ Arquitectura del Sistema

Sensorica está construido sobre el framework Laravel, siguiendo una arquitectura MVC (Modelo-Vista-Controlador) con las siguientes capas:

1. **Capa de Presentación**: Interfaces de usuario basadas en Blade y JavaScript.
2. **Capa de Aplicación**: Controladores Laravel que gestionan la lógica de negocio.
3. **Capa de Dominio**: Modelos Eloquent que representan las entidades del sistema.
4. **Capa de Infraestructura**: Servicios de integración con MQTT, bases de datos y APIs externas.

El sistema utiliza un enfoque de microservicios para los componentes críticos, con procesos en segundo plano gestionados por Supervisor para tareas como:
- Monitoreo continuo de sensores
- Cálculo de métricas OEE
- Sincronización con APIs externas
- Procesamiento de datos en tiempo real

### 🗺️ Diagrama de arquitectura (alto nivel)

```
Usuarios/Operarios                           Integraciones/Dispositivos
        |                                               |
        v                                               v
  [SPAs públicas (public/*)]     [RFID Readers]   [SCADA/Modbus]
        |                               |              |
        v                               v              v
 [Nginx/Apache]  →  Laravel (routes/web.php, routes/api.php)
                           |                    |
                           v                    v
                  [Controllers/API]      [Console Commands]
                           |                    |
                           └──────► [Models/DB] ◄──────┘
                                            ^
                                            |
                         [MQTT Brokers] ◄───┼───► [Node services]
                                             \      - sender-mqtt-server*.js
                                              \     - sensor-transformer.js
                                               \    - mqtt-rfid-to-api.js
                                                \   - client-modbus.js
```

## 📦 Módulos Principales

### Sistema Kanban

El corazón de Sensorica es su sistema Kanban para la gestión visual de órdenes de producción. Características principales:

- **Tablero Interactivo**: Interfaz drag & drop para mover órdenes entre estados (Pendientes, En Curso, Finalizadas, Incidencias).
- **Filtrado Avanzado**: Búsqueda y filtrado de órdenes por múltiples criterios.
- **Indicadores Visuales**: Sistema de iconos para identificar órdenes urgentes, problemas de stock, y prioridades.
- **Actualización en Tiempo Real**: Sincronización automática del estado del tablero.
- **Restricciones de Flujo de Trabajo**: Reglas configurables para el movimiento de tarjetas (ej. tarjetas finalizadas solo pueden moverse a incidencias).
- **Gestión de Posiciones**: Algoritmo inteligente para mantener el orden correcto de las tarjetas.
- **Menú Contextual**: Acciones rápidas para cada tarjeta mediante menú de tres puntos.

### Monitoreo OEE

Sistema completo para el cálculo y visualización de la Eficiencia General de los Equipos:

- **Cálculo en Tiempo Real**: Actualización continua de métricas de disponibilidad, rendimiento y calidad.
- **Configuración por Línea**: Parámetros OEE personalizables para cada línea de producción.
- **Integración con MQTT**: Recepción de datos directamente desde sensores y PLCs.
- **Visualización de Tendencias**: Gráficos históricos de evolución del OEE.
- **Alertas Configurables**: Notificaciones cuando los valores caen por debajo de umbrales definidos.

### Gestión de Sensores

Módulo completo para la configuración y monitoreo de sensores industriales:

- **Múltiples Tipos de Sensores**: Soporte para sensores de producción, calidad, tiempo, etc.
- **Transformación de Datos**: Sistema para transformar y normalizar lecturas de sensores.
- **Tópicos MQTT Configurables**: Asignación flexible de tópicos para cada sensor.
- **Histórico de Lecturas**: Almacenamiento y consulta de datos históricos.
- **Calibración de Sensores**: Herramientas para ajustar y calibrar sensores.

#### Cálculo automático de tiempos óptimos

El comando Artisan `php artisan production:calculate-optimal-time` mantiene sincronizados los tiempos óptimos de producción siguiendo una arquitectura de tres capas diseñada para balancear aprendizaje automático, valores operativos y configuración base del cliente:

1. **`optimal_sensor_times` (historial maestro)**
   - Guarda el mejor tiempo encontrado por combinación *sensor × línea × producto*.
   - Se crea el registro si no existía y se actualiza cuando:
     - El nuevo cálculo es **menor** al registrado, o
     - El nuevo cálculo es **mayor** pero supera el umbral `min_correction_percentage`, aplicando un límite con `max_correction_percentage`.
   - Evita actualizaciones cuando el incremento no supera el porcentaje mínimo definido en el sensor.

2. **`sensors.optimal_production_time` (cache operativo)**
   - Copia el valor desde `optimal_sensor_times` únicamente si el sensor tiene activado `auto_update_sensor_optimal_time`.
   - Solo guarda cuando el valor cambia para minimizar escrituras innecesarias.
   - Si todavía no hay historial, utiliza el cálculo en tiempo real como valor inicial.

3. **`product_lists.optimal_production_time` (baseline del cliente)**
   - Conserva el tiempo inicial proporcionado por el cliente y solo se actualiza cuando se encuentra un valor **menor**.
   - Sirve como respaldo cuando no existe historial en `optimal_sensor_times` (p.ej., producto nuevo).

##### Configuración y UI

- En la edición de un sensor se pueden ajustar los porcentajes `min_correction_percentage` (umbral mínimo) y `max_correction_percentage` (tope máximo aplicable al nuevo valor).
- Para habilitar la actualización automática del campo `optimal_production_time` en `sensors`, se debe activar `auto_update_sensor_optimal_time` desde la misma vista.
- Los logs del comando detallan claramente la fuente del valor aplicado y cuándo se omiten actualizaciones por no superar los umbrales.

### Integración con APIs Externas

Sistema flexible para la integración con sistemas externos:

- **Mapeo de Campos Personalizable**: Configuración visual de mapeos entre sistemas.
- **Transformaciones de Datos**: Funciones para transformar datos durante la importación/exportación.
- **Validación de Datos**: Verificación de integridad y formato de los datos.
- **Procesamiento por Lotes**: Importación eficiente de grandes volúmenes de datos.
- **Registro Detallado**: Logs completos de todas las operaciones de integración.

#### API de Webhooks Entrantes (sin mapeos)

Para clientes que prefieren notificar por HTTP cuando crean/actualizan/borran pedidos en su ERP, Sensorica expone una API de webhooks que crea `original_orders` y sus hijos directamente con un contrato JSON estándar, sin mapeos por cliente.

- Endpoint crear/actualizar: `POST /api/incoming/original-orders`
- Endpoint borrar: `DELETE /api/incoming/original-orders/{order_id}`
- Autenticación: `Authorization: Bearer <customer.token>` (también soporta `X-Customer-Token` o `?token=`)
- Reproceso: `?reprocess=true` borra por completo la orden existente y la recrea desde cero con el payload recibido

Campos principales del payload (POST):
- `order_id` (string, requerido)
- `client_number` (string, opcional)
- `route_name` (string, opcional) → si existe en `route_names.name` para el cliente, se usa su ID; si no existe, se crea automáticamente y se guarda su id en `original_orders.route_name_id`
- `delivery_date` (YYYY-MM-DD, opcional)
- `fecha_pedido_erp` (YYYY-MM-DD, opcional)
- `in_stock` (0|1, opcional)
- `grupos[]` (array):
  - `grupoNum` (string|number)
  - `servicios[]`: procesos a crear (real manufacturing steps)
    - `process_code` (string, requerido; debe existir en `processes.code`)
    - `time_seconds` (int, requerido; se multiplica por `processes.factor_correccion`)
    - `box`, `units_box`, `number_of_pallets` (int, opcionales)
  - `articulos[]`: materiales vinculados al grupo/proceso
    - `codigo_articulo` (string, requerido)
    - `descripcion_articulo` (string, opcional)
    - `in_stock` (0|1, opcional; por defecto 1)

Ejemplo mínimo (1 grupo, 1 servicio y 1 artículo) con route_name:

```json
{
  "order_id": "A-12345",
  "client_number": "C-777",
  "route_name": "Ruta Centro",
  "delivery_date": "2025-09-30",
  "fecha_pedido_erp": "2025-09-20",
  "in_stock": 1,
  "grupos": [
    {
      "grupoNum": "1",
      "servicios": [
        { "process_code": "S.201", "time_seconds": 1800, "box": 0, "units_box": 0, "number_of_pallets": 0 }
      ],
      "articulos": [
        { "codigo_articulo": "2.H3710ST12.19", "descripcion_articulo": "Tablero H3710", "in_stock": 1 }
      ]
    }
  ]
}
```

Ejemplo avanzado (2 grupos, varios servicios por grupo):

```json
{
  "order_id": "B-98765",
  "client_number": "CLI-42",
  "route_name": "Ruta Norte",
  "delivery_date": "2025-10-05",
  "grupos": [
    {
      "grupoNum": 1,
      "servicios": [
        { "process_code": "S.101", "time_seconds": 1200 },
        { "process_code": "S.201", "time_seconds": 2700, "box": 2, "units_box": 6 }
      ],
      "articulos": [
        { "codigo_articulo": "MAT-0001", "descripcion_articulo": "Tablero Roble A" },
        { "codigo_articulo": "MAT-0002", "descripcion_articulo": "Tornillería M4", "in_stock": 1 }
      ]
    },
    {
      "grupoNum": 2,
      "servicios": [
        { "process_code": "S.305", "time_seconds": 900 },
        { "process_code": "S.450", "time_seconds": 600, "number_of_pallets": 1 }
      ],
      "articulos": [
        { "codigo_articulo": "MAT-1001", "descripcion_articulo": "Barniz Satinado" }
      ]
    }
  ]
}
```

Comportamiento por defecto (óptimo): si la orden ya existe, se actualizan los campos ligeros y se guarda el payload como `order_details`, pero no se reprocesan procesos. Con `?reprocess=true`, se borra totalmente y se vuelve a crear con los procesos y artículos del JSON.

Notas sobre `route_name`:
- Si el payload incluye `route_name`, la API buscará una ruta del cliente por `name`. Si no existe, creará una nueva en `route_names` con `active=true` y `days_mask=0`.
- El ID resultante se guarda en `original_orders.route_name_id`.
- Si no se envía `route_name`, el campo `route_name_id` permanecerá `null` (columna nullable).

Para detalles extendidos, ver `docs/incoming_orders_api.md`.

### Control de Calidad (QC): Incidencias y Confirmaciones

El módulo de Control de Calidad (QC) permite gestionar tanto las Incidencias de Calidad como las Confirmaciones de QC realizadas sobre órdenes de producción. Este módulo integra vistas, rutas, permisos y mejoras de interfaz para una navegación clara.

- __¿Para qué se usa?__
  - Asegurar que cada pedido original pase por un punto de control de calidad antes de considerarse completamente terminado.
  - Registrar y consultar incidencias de calidad detectadas en el flujo productivo.
  - Dar trazabilidad: qué orden, en qué línea, qué operador y cuándo se confirmó la calidad.

- __Flujo de trabajo (alto nivel)__
  1. El equipo detecta un problema de calidad durante la producción y lo registra como __Incidencia de Calidad__ desde el tablero/acciones del cliente (`customers/{customer}/quality-incidents`).
  2. Una vez resuelto y verificado, un responsable realiza la __Confirmación de QC__ asociada a la orden original/orden de producción (`customers/{customer}/qc-confirmations`).
  3. En el detalle de la orden (`customers/original-orders/show`) el sistema muestra un badge:
     - “QC confirmation done” si existe al menos una confirmación (`OriginalOrder::hasQcConfirmations()`).
     - “QC confirmation pending” si aún no se confirmó.

- __Vistas__
  - `resources/views/customers/quality-incidents/index.blade.php`: Lista las incidencias de calidad por cliente.
  - `resources/views/customers/qc-confirmations/index.blade.php`: Lista las confirmaciones de QC por cliente.
  - `resources/views/customers/original-orders/show.blade.php`: Muestra el estado “QC confirmation done / pending” en el detalle de pedido original, con enlace directo a la lista de confirmaciones.

- __Modelos y relaciones__
  - `app/Models/QcConfirmation.php`: Modelo para confirmaciones de QC, relacionado con `OriginalOrder`, `ProductionOrder`, `ProductionLine` y `Operator`.
  - `app/Models/OriginalOrder.php`: Incluye la relación `qcConfirmations()` y el helper `hasQcConfirmations()` para saber si un pedido original tiene confirmaciones de QC.

- __Rutas__ (en `routes/web.php`)
  - `customers/{customer}/quality-incidents` → nombre `customers.quality-incidents.index`.
  - `customers/{customer}/qc-confirmations` → nombre `customers.qc-confirmations.index` (controlador `QcConfirmationWebController@index`).

- __Permisos__
  - Las vistas y botones de QC usan el permiso `productionline-incidents` para control de acceso.

- __Controladores__
  - `app/Http/Controllers/QcConfirmationWebController.php@index(Customer $customer)`: lista confirmaciones de QC filtradas por cliente, con `with()` de relaciones necesarias.
  - `app/Http/Controllers/CustomerController.php@getCustomers()`: genera acciones de cada cliente e integra el acceso a QC (Incidencias y Confirmaciones).

- __Mejoras de interfaz (Customers)__
  - En `resources/views/customers/index.blade.php` y `CustomerController@getCustomers()` se reemplazó la multitud de botones por un __diseño de fila expandible__:
    - Columna `action`: botón “Actions” con icono para expandir.
    - Al hacer clic se inserta una __segunda fila__ bajo el cliente con todos los botones agrupados: Básicas, Órdenes/Procesos, Calidad & Incidencias (incluye QC Incidents y QC Confirmations), Estadísticas y zona de peligro.
    - DataTables recibe una columna oculta `action_buttons` con el HTML de los botones. JS inserta la fila expandida dinámicamente.

- __Estado en detalle de pedido__
  - En `resources/views/customers/original-orders/show.blade.php` se añadió una fila con badge de estado:
    - Verde: “QC confirmation done” si el pedido tiene confirmaciones (`hasQcConfirmations()`)
    - Amarillo: “QC confirmation pending” si no tiene
    - Incluye enlace a `route('customers.qc-confirmations.index', $customer->id)`

- __Migraciones__
  - `database/migrations/2025_08_26_000000_create_qc_confirmations_table.php`: tabla para confirmaciones de QC.
  - `database/migrations/2025_08_26_113700_add_original_order_id_qc_to_quality_issues_table.php`: relación de issues de calidad con `original_order_id` para trazabilidad.

- __Pruebas y verificación rápida__
  1. __Permisos__: Con un usuario con `productionline-incidents`, entrar a `Clientes` y expandir una fila: deben aparecer “Incidencias”, “Incidencias QC” y “QC Confirmations”.
  2. __Navegación__: En `Clientes` → expandir → “QC Confirmations” debe llevar a `customers/{id}/qc-confirmations` y listar confirmaciones de ese cliente.
  3. __Detalle de pedido__: En `customers/original-orders/show` verificar el badge “QC confirmation done/pending” y el enlace a confirmaciones.
  4. __Responsivo__: Probar la expansión/contracción de la segunda fila en desktop y móvil. El ícono debe alternar chevron up/down.
  5. __Traducciones__: Verificar textos “QC Confirmations”, “QC confirmation done”, “QC confirmation pending”.

- __Consideraciones UX__
  - La fila expandible reduce ruido visual y agrupa acciones por contexto.
  - Los iconos usan colores semánticos: rojo para incidencias, azul para confirmaciones, verde/amarillo para estadísticas.


### 🤖 Integración IA (Análisis con Ollama)

La aplicación integra un flujo de análisis asistido por IA que permite enviar los datos actualmente visibles en tablas (DataTables) junto con un prompt a un servicio de tareas de IA. El backend esperado es un endpoint interno tipo `/api/ollama-tasks` que crea y gestiona tareas con un modelo LLM (por ejemplo, Ollama).

__Vistas con botón “Análisis IA”__
- `resources/views/customers/maintenances/index.blade.php`
- `resources/views/customers/quality-incidents/index.blade.php`
- `resources/views/customers/qc-confirmations/index.blade.php`
- `resources/views/productionlines/liststats.blade.php`

__Habilitación por configuración__
- El botón de IA solo se muestra si existen ambas variables en configuración Laravel:
  - `config('services.ai.url')` → URL base del servicio IA
  - `config('services.ai.token')` → Token Bearer
- Defínelas en `.env` y el mapeo en `config/services.php`:
  - `.env`:
    - `AI_URL=https://mi-servidor-ia`
    - `AI_TOKEN=mi_token_secreto`
  - `config/services.php`:
    - `'ai' => ['url' => env('AI_URL'), 'token' => env('AI_TOKEN')],`

__Comportamiento de UI__
- Botón en el header de la tarjeta, estilo `btn btn-dark` con icono “stars”.
- Modal de prompt con:
  - Prompt por defecto que se autocompleta al abrir.
  - Botón “Limpiar prompt por defecto” para restablecer el texto.
  - Botón “Enviar a IA” que muestra estado de carga.
- Modal de resultados que muestra el prompt y la respuesta formateada.

__Qué datos se envían a la IA__
- Se recoge el contexto visible en el DataTable (página o búsqueda aplicada según vista) y los filtros actuales.
- El JavaScript combina el prompt del usuario con los datos en formato JSON dentro del mismo campo `prompt` (no se envía un JSON separado en el body). Ejemplo de estructura:

```
<prompt_del_usuario>

=== Datos para analizar (JSON) ===
{ "rows": [...], "filters": { ... } }
```

__API utilizada__
- Crear tarea: `POST {AI_URL}/api/ollama-tasks`
  - Headers: `Authorization: Bearer {AI_TOKEN}`
  - Body: `multipart/form-data` con campo `prompt` (string combinado)
- Consultar tarea: `GET {AI_URL}/api/ollama-tasks/{id}`
  - Headers: `Authorization: Bearer {AI_TOKEN}`
  - Polling automático cada 5s hasta obtener `task.response`.

__Mensajería de errores__
- Si la creación o el polling fallan, se muestra un `alert()` y logs en consola.
- Si falta configuración (`AI_URL`/`AI_TOKEN`), el botón no aparece.

__Prueba rápida__
1. Asegúrate de tener `AI_URL` y `AI_TOKEN` válidos en `.env` y `php artisan config:clear`.
2. Abre una de las vistas listadas, ajusta filtros para reducir filas visibles a un subconjunto relevante.
3. Haz clic en “Análisis IA”, revisa/ajusta el prompt (o usa el predeterminado) y envía.
4. Espera a que el polling complete y verifica el resultado en el modal.


### Gestión de Mantenimientos

El módulo de Mantenimientos permite registrar, iniciar y finalizar incidencias de mantenimiento por línea de producción, con trazabilidad de causas y piezas utilizadas, y una vista índice con métricas agregadas.

- __¿Qué incluye?__
  - Relación de muchos-a-muchos entre `maintenances` y `maintenance_causes`, y entre `maintenances` y `maintenance_parts` mediante tablas pivote.
  - Formulario de finalización con selección múltiple de causas y piezas usadas.
  - Vista índice con DataTable y un bloque de 3 tarjetas de resumen con totales dinámicos: “Stopped before Start”, “Downtime” y “Total Time”.
  - Endpoint ligero en el `MaintenanceController@index` para devolver totales filtrados vía AJAX.

- __Migraciones (tablas pivote)__
  - `database/migrations/2025_08_28_173600_create_pivot_maintenance_cause_maintenance_table.php`
  - `database/migrations/2025_08_28_173601_create_pivot_maintenance_part_maintenance_table.php`
  - Ejecutar: `php artisan migrate`

- __Modelo__
  - `app/Models/Maintenance.php`
    - Relaciones añadidas:
      - `causes(): belongsToMany(MaintenanceCause::class)`
      - `parts(): belongsToMany(MaintenancePart::class)`

- __Controlador__
  - `app/Http/Controllers/MaintenanceController.php`
    - En `index(Request $request)` se añadió soporte AJAX para totales cuando `?totals=1`:
      - `stopped_before_start`: segundos desde `created_at` hasta `start_datetime` (o hasta `end/now` si nunca inició).
      - `downtime`: segundos desde `start_datetime` hasta `end/now`.
      - `total_time`: segundos desde `created_at` hasta `end/now`.
    - Respuesta también incluye las versiones formateadas `HH:MM:SS`.

- __Vistas__
  - `resources/views/customers/maintenances/finish.blade.php`
    - Multiselect de causas y piezas usadas al finalizar un mantenimiento.
  - `resources/views/customers/maintenances/index.blade.php`
    - Se eliminó la columna “Machine Stopped?”.
    - Se añadió una fila de 3 tarjetas debajo de los filtros con ids `#sum_stopped`, `#sum_downtime`, `#sum_total`.
    - JS llama a `loadTotals()` que hace `fetch` a `index?totals=1` con los filtros actuales y actualiza las tarjetas.
    - DataTable muestra columnas “Stopped before Start”, “Downtime”, “Total Time”, además de listas de causas y piezas.

- __Internacionalización__
  - Claves añadidas en `resources/lang/es.json` y `resources/lang/en.json`:
    - "Cause", "Causes", "Part", "Parts"
    - "Maintenance Cause(s)", "Maintenance Part(s)", "Used Parts"
    - "Stopped before Start", "Total Time"

- __Uso rápido__
  1. Ejecuta migraciones: `php artisan migrate`.
  2. Entra a `Clientes` → Mantenimientos de un cliente.
  3. Aplica filtros según línea/operario/usuario/fechas.
  4. Observa las tarjetas de resumen; se actualizan automáticamente según los filtros.
  5. Finaliza un mantenimiento seleccionando múltiples causas y piezas; verifica que el índice muestra las listas y que los totales se recalculan.

### Sistema de Callbacks ERP (Historial de Callbacks)

El sistema de Callbacks ERP permite registrar, monitorear y gestionar las notificaciones automáticas enviadas a sistemas ERP externos cuando las órdenes de producción alcanzan ciertos estados o hitos. Este módulo integra completamente la funcionalidad de callbacks con el resto del sistema Sensorica, incluyendo permisos, políticas de autorización, interfaces de usuario y gestión de errores.

#### Características principales

- **Gestión de Callbacks HTTP**: Envío automático de notificaciones HTTP a URLs externas configuradas
- **Mapeo de Campos Configurable**: Sistema de mapeos para transformar datos de órdenes de producción a formatos ERP
- **Transformaciones Dinámicas**: Soporte para transformaciones de datos (trim, uppercase, lowercase, number, date, to_boolean)
- **Mecanismo de Reintentos**: Sistema robusto de reintentos con backoff exponencial para fallos de conectividad
- **Historial y Auditoría**: Registro completo de todos los callbacks enviados con estados y respuestas
- **Interfaz de Usuario Completa**: Gestión visual de callbacks por cliente con edición y eliminación
- **Permisos Granulares**: Control de acceso basado en roles para operaciones de callbacks
- **Detección Automática**: Creación automática de callbacks cuando órdenes alcanzan estados específicos

#### Flujo de trabajo

1. **Creación de Callback**: Se crea un registro de callback cuando una orden de producción alcanza un estado que requiere notificación
2. **Procesamiento**: El comando `callbacks:process` procesa callbacks pendientes cada 10 segundos
3. **Mapeo de Datos**: Se aplican las transformaciones configuradas en `CustomerCallbackMapping`
4. **Envío HTTP**: Se realiza la petición HTTP POST a la URL configurada
5. **Gestión de Respuestas**: Se registra el resultado (éxito/error) con detalles completos
6. **Reintentos**: En caso de error, se reintenta con backoff hasta el límite configurado

#### Componentes del Sistema

**Modelos:**

- **`ProductionOrderCallback`**: Modelo principal que representa cada callback individual
  - Campos: `production_order_id`, `customer_id`, `callback_url`, `payload`, `status`, `attempts`, `last_attempt_at`, `success_at`, `error_message`
  - Estados: 0=Pendiente, 1=Éxito, 2=Error/Reintento

- **`CustomerCallbackMapping`**: Configuración de mapeos de campos por cliente
  - Campos: `customer_id`, `source_field`, `target_field`, `transformation`, `is_required`
  - Transformaciones soportadas: trim, uppercase, lowercase, number, date, to_boolean

**Controladores:**

- **`ProductionOrderCallbackController`**: Gestión CRUD de callbacks
  - Métodos: index, edit, update, destroy, force (reintento manual)
  - Permisos: callbacks.view, callbacks.update, callbacks.delete, callbacks.force

- **`ProcessProductionOrderCallbacks`**: Comando Artisan para procesamiento
  - Ejecuta cada 10 segundos vía Supervisor
  - Configuración: `CALLBACK_MAX_ATTEMPTS` (por defecto: 20)

**Vistas Blade:**

- **`resources/views/customers/callbacks/index.blade.php`**: Listado de callbacks con filtros
- **`resources/views/customers/callbacks/edit.blade.php`**: Edición de configuración de callback

**Rutas:**

```php
Route::prefix('customers/{customer}/callbacks')->name('customers.callbacks.')->group(function(){
    Route::get('/', [ProductionOrderCallbackController::class, 'index'])->name('index');
    Route::get('{callback}/edit', [ProductionOrderCallbackController::class, 'edit'])->name('edit');
    Route::put('{callback}', [ProductionOrderCallbackController::class, 'update'])->name('update');
    Route::delete('{callback}', [ProductionOrderCallbackController::class, 'destroy'])->name('destroy');
    Route::post('{callback}/force', [ProductionOrderCallbackController::class, 'force'])->name('force');
});
```

#### Configuración

**Variables de Entorno:**

- `CALLBACK_MAX_ATTEMPTS`: Número máximo de intentos antes de marcar como fallido (por defecto: 20)

**Configuración por Cliente:**

Cada cliente puede configurar:
- URL del endpoint ERP para recibir callbacks
- Mapeos de campos entre datos de Sensorica y formato ERP
- Transformaciones a aplicar a cada campo
- Estados de órdenes que activan callbacks

**Ejemplo de Payload:**

```json
{
    "order_id": "ORD-001",
    "processes_code": "PROC-001",
    "status": "COMPLETED",
    "finished_at": "2024-01-15 14:30:00"
}
```

#### Monitoreo y Mantenimiento

**Comando de Procesamiento:**

```bash
php artisan callbacks:process [--once]
```

- `--once`: Procesa un ciclo único y termina
- Sin parámetros: Ejecuta indefinidamente (para Supervisor)

**Configuración de Supervisor:**

```ini
[program:laravel-callbacks-process]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan callbacks:process
autostart=true
autorestart=true
numprocs=1
```

**Logs:**

- Los callbacks exitosos y errores se registran en `storage/logs/laravel.log`
- Información detallada de cada callback incluye URL, payload, respuesta HTTP y errores

#### Integración con el Sistema

El sistema de callbacks se integra automáticamente con:

- **Observer de Órdenes**: Crea callbacks automáticamente cuando órdenes cambian de estado
- **Sistema de Permisos**: Controla el acceso a funciones de callback
- **Interfaz de Cliente**: Gestión visual integrada en la sección de clientes
- **API de Reportes**: Los callbacks pueden activarse desde cambios en la API

Este sistema asegura que los sistemas ERP externos reciban notificaciones en tiempo real sobre el progreso de las órdenes de producción, manteniendo la integridad de los datos y proporcionando mecanismos robustos de recuperación de fallos.

#### Transformación de Sensores

El componente `sensor-transformer.js` es un servicio Node.js crítico para el procesamiento de sensores en tiempo real. Este servicio actúa como un middleware entre los sensores físicos y la aplicación, permitiendo la normalización y transformación de valores según reglas configurables.

**Características principales:**

- **Transformación configurable**: Transforma valores de sensores según rangos configurados (min, mid, max) y valores de salida personalizados.
- **Persistencia en base de datos**: Las configuraciones de transformación se almacenan en la tabla `sensor_transformations`.
- **Comunicación MQTT**: Se suscribe a tópicos de entrada y publica en tópicos de salida mediante el protocolo MQTT.
- **Caché de valores**: Implementa un sistema de caché para evitar publicaciones redundantes cuando los valores no cambian.
- **Reconexión automática**: Manejo robusto de reconexiones tanto para MySQL como para MQTT.
- **Actualizaciones en tiempo real**: Detecta cambios en las configuraciones de transformación sin necesidad de reiniciar el servicio.

**Flujo de trabajo:**

1. Se conecta a la base de datos MySQL para obtener las configuraciones de transformación activas.
2. Se suscribe a los tópicos MQTT especificados en las configuraciones.
3. Al recibir un mensaje en un tópico suscrito, aplica la transformación correspondiente según los rangos configurados.
4. Publica el valor transformado en el tópico de salida solo si el valor ha cambiado desde la última publicación.

**Configuración de transformaciones:**

Cada transformación en la tabla `sensor_transformations` incluye:

- `input_topic`: Tópico MQTT de entrada donde se reciben los valores del sensor.
- `output_topic`: Tópico MQTT de salida donde se publican los valores transformados.
- `min_value`, `mid_value`, `max_value`: Valores que definen los rangos para la transformación.
- `below_min_value_output`: Valor de salida cuando el valor de entrada es menor que `min_value`.
- `min_to_mid_value_output`: Valor de salida cuando el valor está entre `min_value` y `mid_value`.
- `mid_to_max_value_output`: Valor de salida cuando el valor está entre `mid_value` y `max_value`.
- `above_max_value_output`: Valor de salida cuando el valor es mayor que `max_value`.

Este componente es esencial para la interpretación de datos de sensores industriales, permitiendo convertir valores crudos (como voltajes o resistencias) en valores significativos para la aplicación (como estados "on"/"off" o niveles "bajo"/"medio"/"alto").

#### Sistema de Integración RFID

El componente `mqtt-rfid-to-api.js` es un gateway que conecta el sistema RFID físico con la aplicación Sensorica, actuando como puente entre los lectores RFID y el backend de la aplicación.

**Características principales:**

- **Arquitectura Gateway**: Funciona como un puente bidireccional entre el protocolo MQTT (usado por los lectores RFID) y la API REST de Sensorica.
- **WebSockets en tiempo real**: Proporciona una interfaz WebSocket para monitorizar lecturas RFID en tiempo real.
- **Gestión dinámica de antenas**: Carga y actualiza automáticamente la configuración de antenas RFID desde la base de datos.
- **Interfaz de monitoreo**: Incluye una interfaz web en `/gateway-test` para visualizar y filtrar lecturas RFID en tiempo real.
- **Seguridad configurable**: Soporte opcional para HTTPS/WSS mediante certificados SSL configurables.
- **Resiliencia**: Implementa reconexión automática tanto para MQTT como para la base de datos.
- **Almacenamiento temporal**: Mantiene un historial de las últimas 100 lecturas RFID para análisis inmediato.

**Flujo de trabajo:**

1. Se conecta a la base de datos MySQL para obtener la configuración de antenas RFID (`rfid_ants` tabla).
2. Se suscribe a los tópicos MQTT correspondientes a cada antena RFID configurada.
3. Al recibir una lectura RFID a través de MQTT:
   - La procesa y almacena temporalmente.
   - La transmite en tiempo real a todos los clientes WebSocket conectados.
   - La hace disponible a través de la API REST.
4. Periódicamente verifica y actualiza la configuración de antenas desde la base de datos.

**Integración con el sistema:**

- **API REST**: Expone un endpoint `/api/gateway-messages` que proporciona las últimas lecturas RFID y la información de las antenas.
- **WebSockets**: Permite a las interfaces de usuario recibir actualizaciones en tiempo real de las lecturas RFID.
- **Monitoreo**: La interfaz web en `/gateway-test` permite visualizar y filtrar lecturas RFID por antena.
- **Base de datos**: Se integra con la tabla `rfid_ants` que almacena la configuración de las antenas RFID.

Este componente es fundamental para la funcionalidad de seguimiento RFID en tiempo real, permitiendo el monitoreo de productos y operarios equipados con tags RFID a lo largo del proceso de producción.

### 🚚 Sistema de Planificación de Rutas

El Sistema de Planificación de Rutas es un módulo completo para la gestión de rutas de entrega, asignación de vehículos y planificación de clientes. Este sistema permite optimizar las operaciones logísticas mediante una interfaz visual intuitiva con funcionalidades avanzadas de drag & drop.

#### Características principales

- **Gestión Visual de Rutas**: Interfaz tipo calendario semanal para visualizar y planificar rutas por días
- **Asignación Multi-Vehículo**: Soporte para múltiples vehículos por ruta/día sin restricciones
- **Drag & Drop Avanzado**: Arrastrar clientes entre vehículos y reordenar dentro de cada vehículo
- **Auto-Refresh Inteligente**: Sistema que actualiza automáticamente la vista respetando interacciones del usuario
- **Gestión de Órdenes Ficticias**: Sistema de mini-tarjetas de pedidos dentro de cada cliente
- **Notificaciones Toast**: Feedback visual inmediato para todas las operaciones
- **Modales de Confirmación**: Confirmaciones elegantes que no interrumpen el flujo de trabajo
- **Búsqueda y Filtros**: Sistema de filtros avanzados para clientes y vehículos
- **Responsive Design**: Interfaz adaptativa para dispositivos móviles y desktop

#### Componentes del Sistema

**Modelos:**

- **`RouteName`**: Rutas configuradas por cliente (ej. "Ruta Centro", "Ruta Norte")
- **`RouteDayAssignment`**: Asignaciones de vehículos a rutas específicas por día
- **`RouteClientVehicleAssignment`**: Asignaciones de clientes a vehículos específicos
- **`FleetVehicle`**: Vehículos disponibles con tipos y capacidades
- **`CustomerClient`**: Clientes del sistema con información de contacto

**Controlador Principal:**

- **`RoutePlanController`**: Gestión completa de la planificación de rutas
  - `index()`: Vista principal con calendario semanal
  - `assignVehicle()`: Asignación de vehículos a rutas/días
  - `removeVehicle()`: Eliminación de asignaciones de vehículos
  - `assignClientToVehicle()`: Asignación de clientes a vehículos específicos
  - `removeClientFromVehicle()`: Eliminación de clientes de vehículos
  - `moveClient()`: Movimiento de clientes entre vehículos
  - `reorderClients()`: Reordenación de clientes dentro de un vehículo

**Vistas Blade:**

- **`customers/routes/index.blade.php`**: Vista principal del planificador
- **`components/routes/day-cell.blade.php`**: Celda individual de día con clientes y vehículos
- **`components/routes/vehicle-card.blade.php`**: Tarjeta de vehículo con clientes asignados

#### Funcionalidades Avanzadas

**Sistema de Auto-Refresh Inteligente:**

- **Detección de Modals**: Pausa el refresh cuando hay modals abiertos
- **Detección de Drag & Drop**: Pausa el refresh durante operaciones de arrastre
- **Refresh Programado**: Actualización automática después de cambios (1.5-3 segundos)
- **Cancelación Inteligente**: Cancela refreshes programados cuando es necesario

**Gestión de Múltiples Vehículos:**

- **Sin Restricciones**: Permite asignar múltiples vehículos a la misma ruta/día
- **Constraint Único Correcto**: `unique_customer_route_vehicle_day` evita duplicados exactos
- **Migración Automática**: Sistema que migró desde constraint restrictivo a permisivo

**Sistema de Órdenes Ficticias:**

- **Mini-Tarjetas**: Pedidos ficticios ("pedido-test1", "pedido-test2") dentro de cada cliente
- **Visualización en Hover**: Las mini-tarjetas aparecen al pasar el ratón sobre el cliente
- **Diseño Consistente**: Tanto clientes renderizados como añadidos dinámicamente usan la misma estructura

#### Flujo de Trabajo Típico

1. **Configuración Inicial**:
   - Crear rutas por cliente (`route_names`)
   - Registrar vehículos de la flota (`fleet_vehicles`)
   - Configurar clientes (`customer_clients`)

2. **Planificación Semanal**:
   - Seleccionar semana en el calendario
   - Asignar vehículos a rutas específicas por día
   - Arrastrar clientes desde la lista disponible a vehículos

3. **Optimización**:
   - Reordenar clientes dentro de cada vehículo
   - Mover clientes entre vehículos del mismo día
   - Añadir o quitar vehículos según demanda

4. **Gestión de Cambios**:
   - Eliminar clientes de vehículos (vuelven a lista disponible)
   - Eliminar vehículos completos de rutas
   - Modificar asignaciones en tiempo real

#### Tecnologías y Patrones

**Frontend:**

- **JavaScript Vanilla**: Sin dependencias externas para máximo rendimiento
- **Bootstrap 5**: Framework CSS para componentes y responsive design
- **Drag & Drop API**: API nativa del navegador para funcionalidad de arrastre
- **Fetch API**: Comunicación asíncrona con el backend
- **Event Delegation**: Patrón para manejar elementos dinámicos

**Backend:**

- **Laravel Eloquent**: ORM para gestión de datos
- **Validation**: Validación robusta de datos de entrada
- **Transactions**: Transacciones de base de datos para operaciones complejas

#### Sistema de Transportistas y Entregas

El sistema incluye un módulo completo para la gestión de transportistas (conductores) y sus entregas diarias, permitiendo asignar conductores a vehículos y proporcionarles una vista móvil-friendly para gestionar sus entregas.

**Características principales:**

- **Asignación de Conductores**: Asignar usuarios como conductores a vehículos específicos
- **Vista para Transportistas**: Interfaz dedicada para que los conductores vean sus entregas del día
- **Gestión de Entregas**: Marcar pedidos como entregados desde dispositivos móviles
- **Control de Permisos**: Sistema de roles y permisos para controlar acceso
- **KPIs en Tiempo Real**: Métricas visuales de entregas pendientes y completadas

**Componentes del Sistema:**

**Base de Datos:**
- Campo `user_id` en tabla `route_day_assignments` para vincular conductor con vehículo
- Relación `driver()` en modelo `RouteDayAssignment`
- Foreign key y índice para optimización de consultas

**Modelos:**
- **`RouteDayAssignment`**: Incluye relación `driver()` con modelo `User`
- **`DeliveryController`**: Controlador dedicado para gestión de entregas

**Vistas:**
- **`deliveries/my-deliveries.blade.php`**: Vista principal para transportistas
  - Diseño móvil-friendly con gradientes modernos
  - 4 KPIs visuales: Total paradas, Entregados, Pendientes, Vehículos
  - Selector de fecha para ver entregas de otros días
  - Tarjetas por cliente con información de contacto
  - Botón para llamar directamente al cliente
  - Lista de pedidos con botón "✓ Entregar"
  - Actualización en tiempo real sin recargar página

**Funcionalidades:**

1. **Asignación de Conductores**:
   - Dropdown en modal de asignación de vehículo
   - Botón 👤 en cada vehículo para cambiar conductor
   - Muestra nombre del conductor o "No driver"
   - Modal para editar conductor después de asignar

2. **Vista del Transportista** (`/my-deliveries`):
   - Acceso mediante enlace en sidebar (🚚 Mis Entregas)
   - Filtrado automático por usuario autenticado
   - Selector de fecha para planificación
   - Información de cliente: nombre, dirección, teléfono
   - Estado visual de pedidos (pendiente/entregado)
   - Actualización de contadores en tiempo real

3. **Gestión de Entregas**:
   - Botón "✓ Entregar" por cada pedido
   - Confirmación antes de marcar
   - Actualiza `actual_delivery_date` en base de datos
   - Feedback visual inmediato
   - Toast de confirmación

**Permisos y Roles:**

- **Permiso**: `deliveries-view` - Controla acceso a vista de entregas
- **Rol**: `driver` - Rol específico para transportistas
- **Seeder**: `DeliveryPermissionsSeeder` - Crea permiso y rol automáticamente
- Asignado por defecto a roles `admin` y `driver`
- Enlace en navbar solo visible con permiso

**Rutas:**
```php
Route::get('/my-deliveries', [DeliveryController::class, 'myDeliveries'])
    ->name('deliveries.my-deliveries');
Route::post('/deliveries/mark-delivered', [DeliveryController::class, 'markAsDelivered'])
    ->name('deliveries.mark-delivered');
```

**Controlador:**
- **`DeliveryController@myDeliveries`**: Lista entregas del usuario autenticado
- **`DeliveryController@markAsDelivered`**: Marca pedido como entregado
- Seguridad: Solo ve sus propias entregas filtradas por `user_id`

**Flujo de Trabajo:**

1. **Administrador asigna conductor**:
   - Al crear vehículo: selecciona conductor en dropdown
   - Después de crear: click en botón 👤 para cambiar

2. **Asignar rol al usuario**:
   - Ir a gestión de usuarios
   - Asignar rol "driver" al usuario
   - O asignar permiso "deliveries-view" directamente

3. **Transportista accede**:
   - Login con sus credenciales
   - Ve enlace "🚚 Mis Entregas" en sidebar
   - Accede a `/my-deliveries`

4. **Gestión de entregas**:
   - Ve lista de clientes asignados
   - Puede llamar directamente desde la app
   - Marca pedidos como entregados
   - Contadores se actualizan automáticamente

**Archivos Clave:**
- `database/migrations/2025_09_29_212242_add_user_id_to_route_day_assignments_table.php`
- `app/Models/RouteDayAssignment.php`
- `app/Http/Controllers/DeliveryController.php`
- `resources/views/deliveries/my-deliveries.blade.php`
- `database/seeders/DeliveryPermissionsSeeder.php`
- `resources/views/partial/nav-builder.blade.php`
- `resources/views/components/routes/vehicle-card.blade.php`

**Mejoras de Rutas Implementadas:**

1. **Búsqueda Global de Pedidos**:
   - Campo de búsqueda específico para números de pedido
   - Resalta pedidos encontrados con outline azul
   - Scroll automático al pedido
   - Toast con cantidad de resultados

2. **Copiar de Semana Anterior**:
   - Botón por vehículo: Copia solo ese vehículo
   - Botón por ruta: Copia toda la ruta con todos los vehículos
   - Usa pedidos ACTUALES del cliente
   - Solo copia clientes con pedidos pendientes
   - Mantiene orden original (sort_order)

3. **Exportación e Impresión**:
   - Botón imprimir: PDF individual por vehículo
   - Botón Excel: Exporta a .xlsx con formato profesional
   - Impresión de ruta completa: PDF con todos los vehículos
   - Excel de ruta completa: Archivo con separadores por vehículo
   - Nombres descriptivos: `hoja_ruta_{matricula}_{fecha}.xlsx`

4. **Gestión de Pedidos**:
   - Toggle activo/inactivo por pedido (click en X)
   - Drag & drop para reordenar pedidos
   - Contador de pedidos activos por cliente
   - Estado visual (activo/inactivo)
   - Actualización automática de `estimated_delivery_date`

5. **Hoja de Ruta con Detalles Completos**:
   - Muestra procesos de cada pedido (grupo, código, nombre, cajas, pallets)
   - Muestra artículos agrupados por grupo
   - Estado de stock por artículo (✓/✗)
   - Formato profesional para impresión
   - Información completa para verificación de entrega

#### Sistema de Firma Digital y Fotos de Entrega

El sistema permite a los transportistas capturar pruebas de entrega mediante firma digital, fotos y notas.

**Características:**

1. **Firma Digital del Cliente**:
   - Canvas interactivo para firmar con dedo/ratón
   - Tamaño: 700x300px (optimizado para móvil)
   - Librería: SignaturePad.js 4.1.7
   - Guardado en base64 en campo `delivery_signature`
   - Botón para limpiar y volver a firmar
   - Inicialización diferida al abrir modal

2. **Fotos de Entrega**:
   - Captura múltiple de fotos desde cámara o galería
   - Atributo `capture="environment"` para cámara trasera
   - Validación: máximo 5MB por foto
   - Preview de miniaturas antes de enviar
   - Botón X para eliminar fotos individuales
   - Guardado en `storage/deliveries/{order_id}/`
   - Rutas almacenadas en JSON en campo `delivery_photos`

3. **Notas de Entrega**:
   - Campo de texto libre (máximo 1000 caracteres)
   - Contador de caracteres en tiempo real
   - Guardado en campo `delivery_notes`
   - Ejemplos: "Cliente ausente", "Dejado en recepción", etc.

4. **Modal de Prueba de Entrega**:
   - Header verde con icono de clipboard
   - Tres secciones: Firma, Fotos, Notas
   - Botón "Confirmar Entrega" envía todo
   - Validación opcional: puede entregar sin firma/fotos
   - Loading spinner durante envío
   - Recarga automática al confirmar

**Base de Datos:**

Campos añadidos a tabla `original_orders`:
```sql
delivery_signature TEXT NULL COMMENT 'Firma digital del cliente en base64'
delivery_photos JSON NULL COMMENT 'Array de rutas de fotos de entrega'
delivery_notes TEXT NULL COMMENT 'Notas del transportista sobre la entrega'
```

**Controlador:**

`DeliveryController@markAsDelivered` actualizado para:
- Validar firma (string), fotos (array de imágenes), notas (string max 1000)
- Guardar firma en base64
- Subir fotos a storage con `store('deliveries/{order_id}', 'public')`
- Guardar notas
- Actualizar `actual_delivery_date` y `delivery_date`

**Vista:**

Modal `#deliveryProofModal` con:
- Canvas `#signaturePad` con SignaturePad
- Input file `#deliveryPhotos` con `multiple` y `accept="image/*"`
- Textarea `#deliveryNotes` con maxlength 1000
- Preview dinámico de fotos con miniaturas
- JavaScript para gestión completa

**Flujo de Uso:**

1. Transportista hace click en botón "Entregar"
2. Se abre modal con canvas de firma, input de fotos y campo de notas
3. Cliente firma en el canvas (opcional)
4. Transportista toma/selecciona fotos (opcional)
5. Añade notas si necesario (opcional)
6. Click en "Confirmar Entrega"
7. Se envía FormData con firma, fotos y notas
8. Backend guarda todo y marca como entregado
9. Vista se recarga mostrando pedido entregado

**Archivos Clave:**
- `database/migrations/2025_09_29_222422_add_delivery_proof_to_original_orders_table.php`
- `app/Http/Controllers/DeliveryController.php` (método markAsDelivered actualizado)
- `app/Models/OriginalOrder.php` (fillable y casts actualizados)
- `resources/views/deliveries/my-deliveries.blade.php` (modal y JavaScript)
- CDN: `https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js`

**Traducciones:**
- "Delivery Proof" / "Prueba de Entrega"
- "Customer Signature" / "Firma del Cliente"
- "Clear Signature" / "Limpiar Firma"
- "Delivery Photos" / "Fotos de Entrega"
- "Delivery Notes" / "Notas de Entrega"
- "Confirm Delivery" / "Confirmar Entrega"

- **Logging**: Sistema completo de logs para debugging y auditoría

**Base de Datos:**

- **Constraints Únicos**: Prevención de duplicados con constraints específicos
- **Foreign Keys**: Integridad referencial entre todas las tablas
- **Indexes**: Optimización de consultas para rendimiento
- **Migrations**: Versionado de esquema de base de datos

#### Configuración y Despliegue

**Variables de Entorno:**

No requiere configuración especial, utiliza la configuración estándar de Laravel.

**Permisos Requeridos:**

- Acceso a la sección de clientes
- Permisos de lectura/escritura en tablas de rutas
- Acceso a gestión de vehículos y clientes

**Rutas del Sistema:**

```php
Route::prefix('customers/{customer}/routes')->name('customers.routes.')->group(function(){
    Route::get('/', [RoutePlanController::class, 'index'])->name('index');
    Route::post('assign-vehicle', [RoutePlanController::class, 'assignVehicle'])->name('assign-vehicle');
    Route::delete('remove-vehicle', [RoutePlanController::class, 'removeVehicle'])->name('remove-vehicle');
    Route::post('assign-client-vehicle', [RoutePlanController::class, 'assignClientToVehicle'])->name('assign-client-vehicle');
    Route::delete('remove-client-vehicle', [RoutePlanController::class, 'removeClientFromVehicle'])->name('remove-client-vehicle');
    Route::post('move-client', [RoutePlanController::class, 'moveClient'])->name('move-client');
    Route::post('reorder-clients', [RoutePlanController::class, 'reorderClients'])->name('reorder-clients');
});
```

#### Mejoras Implementadas (2025-09-24)

**Resolución de Problemas de Múltiples Vehículos:**

- **Problema**: Constraint único `unique_route_day_assignment` impedía múltiples vehículos por ruta/día
- **Solución**: Migración que recreó la tabla con constraint correcto `unique_customer_route_vehicle_day`
- **Resultado**: Soporte completo para múltiples vehículos sin restricciones

**Sistema de Auto-Refresh Mejorado:**

- **Problema**: Refresh interrumpía modals y operaciones de drag & drop
- **Solución**: Sistema inteligente que detecta y respeta interacciones del usuario
- **Características**: Pausa automática durante modals y drag & drop, reprogramación inteligente

**Interfaz de Usuario Optimizada:**

- **Problema**: Clientes eliminados no reaparecían en lista disponible
- **Solución**: Sistema robusto de reposición con múltiples estrategias de búsqueda
- **Mejoras**: Creación dinámica de listas cuando no existen, logs detallados para debugging

**Gestión de Errores Mejorada:**

- **Toasts Globales**: Sistema de notificaciones accesible desde cualquier script
- **Manejo de Errores HTTP**: Gestión específica de errores 500 y constraints de base de datos
- **Logs Detallados**: Información completa para debugging y auditoría

Este sistema representa una solución completa y robusta para la planificación de rutas en entornos industriales y logísticos, proporcionando una experiencia de usuario intuitiva y funcionalidades avanzadas para optimizar las operaciones de entrega.

#### Sistema de Control SCADA/Modbus

El componente `client-modbus.js` es un servicio Node.js especializado que gestiona la comunicación con sistemas industriales SCADA (Supervisory Control And Data Acquisition) mediante el protocolo Modbus, enfocado principalmente en el control de pesaje y dosificación industrial.

**Características principales:**

- **Integración MQTT-SCADA**: Actuúa como puente entre el protocolo MQTT y los sistemas SCADA/Modbus industriales.
- **Filtrado inteligente**: Implementa algoritmos avanzados para filtrar lecturas repetitivas o con variaciones mínimas.
- **Caché de configuración**: Mantiene en memoria la configuración de cada dispositivo Modbus para optimizar el rendimiento.
- **Modos especializados**: Soporta diferentes modos de operación según el tipo de dispositivo (`weight`, `height` u otros).
- **Control de repeticiones**: Sistema configurable para limitar el envío de datos repetidos según un umbral definido por dispositivo.
- **Control de variaciones mínimas**: Para dispositivos de pesaje, filtra cambios menores según un factor de variación configurable.
- **Sincronización dinámica**: Actualiza automáticamente la configuración de dispositivos desde la base de datos.
- **Resiliencia**: Implementa mecanismos robustos de reconexión tanto para MQTT como para la base de datos.

**Flujo de trabajo:**

1. Se conecta a la base de datos MySQL para obtener la configuración de dispositivos Modbus (`modbuses` tabla).
2. Se suscribe a los tópicos MQTT correspondientes a cada dispositivo Modbus configurado.
3. Al recibir datos de un dispositivo a través de MQTT:
   - Aplica lógica de filtrado según el tipo de dispositivo (peso, altura, etc.).
   - Controla repeticiones mediante contadores específicos para cada tópico.
   - Para dispositivos de pesaje, aplica lógica de variación mínima con factor de conversión.
   - Para dispositivos de altura, compara con dimensiones predeterminadas.
   - Si el valor supera los filtros, lo envía a la API REST de Sensorica.
4. Periódicamente resetea los contadores de repetición y sincroniza la configuración desde la base de datos.

**Integración con el sistema:**

- **Pesaje industrial**: Procesa datos de básculas y sistemas de pesaje con filtrado de variaciones mínimas.
- **Control de altura**: Monitoriza alturas en procesos industriales con comparación contra valores predeterminados.
- **Dosificación**: Facilita el control preciso de sistemas de dosificación mediante la gestión de valores repetidos.
- **Base de datos**: Se integra con la tabla `modbuses` que almacena la configuración de cada dispositivo.
- **API REST**: Envía los datos filtrados a endpoints específicos de la API de Sensorica.

Este componente es crucial para la integración con maquinaria industrial, permitiendo un control preciso de sistemas de pesaje, dosificación y medición en entornos de producción.

Sensorica utiliza Supervisor para gestionar y mantener en ejecución una serie de procesos críticos para el funcionamiento del sistema. Estos procesos incluyen comandos Artisan de Laravel y servidores Node.js que realizan tareas específicas de monitoreo, comunicación y procesamiento de datos.

**Principales comandos y sus funciones:**

1. **Cálculo de OEE (`calculate-monitor-oee`):**
   - **Archivo:** `CalculateProductionMonitorOeev2.php`
   - **Descripción:** Calcula y gestiona las métricas OEE (Overall Equipment Effectiveness) en tiempo real.
   - **Funcionalidad:**
     - Monitorea el estado de las líneas de producción activas
     - Calcula tiempos de actividad, parada y rendimiento
     - Procesa datos de sensores y dispositivos Modbus
     - Actualiza contadores de producción por turno y semanales
     - Calcula métricas de disponibilidad, rendimiento y calidad
     - Genera estadísticas de OEE en tiempo real

2. **Suscriptor MQTT Local (`subscribe-local`):**
   - **Archivo:** `MqttSubscriberLocal.php`
   - **Descripción:** Gestiona la comunicación MQTT para eventos locales del sistema.
   - **Funcionalidad:**
     - Se suscribe a tópicos MQTT locales como `production/+/+/status`
     - Procesa mensajes relacionados con cambios de estado en líneas de producción
     - Actualiza el estado de órdenes de producción en tiempo real
     - Registra eventos de inicio/fin de turnos y paradas
     - Sincroniza el estado del sistema con la base de datos

3. **Verificación de Órdenes desde API (`orders-check`):**
   - **Archivo:** `CheckOrdersFromApi.php`
   - **Descripción:** Sincroniza órdenes de producción desde sistemas externos vía API.
   - **Funcionalidad:**
     - Consulta APIs externas para obtener nuevas órdenes
     - Transforma datos de órdenes según mapeo de campos configurado
     - Crea o actualiza órdenes en el sistema Sensorica
     - Gestiona la sincronización de artículos y procesos asociados
     - Mantiene un registro de auditoría de sincronización

4. **Lectura de Sensores (`read-sensors`):**
   - **Archivo:** `ReadSensors.php`
   - **Descripción:** Gestiona la lectura y procesamiento de datos de sensores industriales.
   - **Funcionalidad:**
     - Lee datos de sensores conectados al sistema
     - Procesa y filtra lecturas según configuración
     - Actualiza contadores de producción y tiempos de actividad
     - Detecta paradas y eventos especiales
     - Almacena datos históricos para análisis

5. **Lectura RFID (`read-rfid`):**
   - **Archivo:** `ReadRfidReadings.php`
   - **Descripción:** Procesa lecturas de tags RFID y las asocia con operarios y productos.
   - **Funcionalidad:**
     - Lee datos de antenas RFID configuradas en el sistema
     - Asocia lecturas con operarios y productos mediante EPC/TID
     - Registra eventos de entrada/salida de zonas de trabajo
     - Actualiza estado de asignaciones de puestos
     - Mantiene un historial de lecturas para trazabilidad

6. **Integración Modbus (`modbus-subscriber`):**
   - **Archivo:** `ReadModbus.php`
   - **Descripción:** Gestiona la comunicación con dispositivos industriales mediante protocolo Modbus.
   - **Funcionalidad:**
     - Lee registros de dispositivos Modbus configurados
     - Procesa datos de pesaje, altura y otros parámetros industriales
     - Aplica filtros y transformaciones a las lecturas
     - Envía datos procesados al sistema central
     - Gestiona la reconexión automática en caso de fallos

7. **Servidor WhatsApp (`connect-whatsapp`):**
   - **Archivo:** `ConnectWhatsApp.php` (gestor Laravel) y `connect-whatsapp.js` (servidor Node.js)
   - **Descripción:** Gestiona la comunicación bidireccional con WhatsApp para notificaciones y comandos.
   - **Funcionalidad:**
     - Mantiene conexión con la API de WhatsApp
     - Envía notificaciones automáticas sobre eventos del sistema
     - Procesa comandos recibidos vía WhatsApp
     - Gestiona la autenticación y sesión de WhatsApp
     - Permite la interacción remota con el sistema

8. **Transformación de Sensores (`sensor-transformers`):**
   - **Archivo:** `sensor-transformer.js` (servidor Node.js)
   - **Descripción:** Procesa y transforma datos de sensores para su uso en el sistema.
   - **Funcionalidad:**
     - Aplica algoritmos de transformación a lecturas de sensores
     - Convierte unidades y formatos según configuración
     - Filtra lecturas erróneas o fuera de rango
     - Optimiza el flujo de datos para reducción de tráfico
     - Gestiona la calibración virtual de sensores

Todos estos comandos son gestionados por Supervisor, que garantiza su ejecución continua, reinicio automático en caso de fallo, y registro adecuado de su actividad en archivos de log dedicados. La configuración de cada comando se encuentra en archivos `.conf` individuales en el directorio raíz del proyecto.

### 📣 Notificaciones WhatsApp y Alertas de Incidencias (Cambios recientes)

- **Nuevo campo en módulo WhatsApp/Notifications**
  - Vista: `resources/views/whatsapp/notification.blade.php`
  - Se añadió la tarjeta “Teléfonos de Incidencias de Orden” con un formulario para gestionar teléfonos separados por comas.
  - Variable de entorno utilizada: `WHATSAPP_PHONE_ORDEN_INCIDENCIA`.
  - Rutas web añadidas:
    - `POST whatsapp/update-incident-phones` → `App\Http\Controllers\WhatsAppController@updateIncidentPhones`.
  - Controlador actualizado: `app/Http/Controllers/WhatsAppController.php`
    - `sendNotification()` ahora inyecta `phoneNumberIncident` con `env('WHATSAPP_PHONE_ORDEN_INCIDENCIA')`.
    - `updateIncidentPhones()` guarda la lista en `.env` editando/insertando la línea `WHATSAPP_PHONE_ORDEN_INCIDENCIA=...`.

- **Observer de órdenes de producción (alertas automáticas)**
  - Archivo: `app/Observers/ProductionOrderObserver.php`
  - Registrado en: `app/Providers/AppServiceProvider.php` (`ProductionOrder::observe(ProductionOrderObserver::class);`)
  - Envía notificaciones WhatsApp a los teléfonos definidos en `WHATSAPP_PHONE_ORDEN_INCIDENCIA` mediante el endpoint Laravel `LOCAL_SERVER/api/send-message` (con `jid=<tel>@s.whatsapp.net`).
  - Todas las notificaciones están protegidas con `try/catch` y registran únicamente errores en logs (`Log::error`).
  - Mensajes implementados:
    - **Tarjeta pasada a incidencias**: cuando el `status` cambia a un valor distinto de `0`, `1` o `2`.
      - Título: “ALERTA ORDEN (tarjeta pasada a incidencias):”
      - Contenido: Centro de producción (nombre de `customer`), Línea, OrderID, Status, Fecha.
    - **Finalizada sin iniciarse**: cuando el `status` cambia a `2` y el estado anterior NO era `1`.
      - Título: “ALERTA ORDEN (finalizada sin iniciarse):”
      - Contenido: Centro de producción, Línea, OrderID, Status, Fecha.
    - **Posible incidencia: menos de N segundos en curso**: cuando el `status` cambia de `1` → `2` y el tiempo transcurrido es menor que el umbral configurado.
      - Umbral configurable con `ORDER_MIN_ACTIVE_SECONDS` (por defecto `60`).
      - Título: “ALERTA ORDEN (posible incidencia - menos de N s en curso):”
      - Contenido: Centro de producción, Línea, OrderID, Status, Tiempo en curso (segundos), Fecha.

- **Variables de entorno relevantes**
  - `WHATSAPP_PHONE_MANTENIMIENTO`: lista separada por comas para notificaciones de mantenimientos.
  - `WHATSAPP_PHONE_ORDEN_INCIDENCIA`: lista separada por comas para alertas de incidencias de órdenes.
  - `LOCAL_SERVER`: base URL del backend Laravel (usado para `.../api/send-message`).
  - `ORDER_MIN_ACTIVE_SECONDS`: umbral en segundos para detectar finalizaciones “demasiado rápidas” desde estado en curso (por defecto `60`).

- **Notas**
  - El texto “Centro de producción” en los mensajes corresponde al `name` del `Customer` vinculado a la línea (`ProductionLine->customer->name`).
  - El botón “Desconectar WhatsApp” llama a `WhatsAppController@disconnect`, que debe apuntar al endpoint de logout válido en API (`/api/whatsapp/logout`). Verificar correspondencia de rutas si se cambia el endpoint.

#### Servidores Node.js

Sensorica implementa varios servidores Node.js especializados que complementan la funcionalidad del backend Laravel, proporcionando capacidades de comunicación en tiempo real, integración con dispositivos industriales y procesamiento de datos.

**1. Servidores MQTT (`sender-mqtt-server1.js` y `sender-mqtt-server2.js`):**

- **Descripción:** Gestionan la comunicación MQTT entre diferentes componentes del sistema, actuando como puentes entre el almacenamiento local y los brokers MQTT.
- **Características principales:**
  - **Arquitectura de publicación por lotes:** Procesan archivos JSON almacenados localmente y los publican en brokers MQTT.
  - **Tolerancia a fallos:** Implementan mecanismos de reconexión automática y manejo de errores.
  - **Configuración dinámica:** Monitorean y recargan automáticamente cambios en la configuración (.env).
  - **Procesamiento secuencial:** Garantizan la entrega ordenada de mensajes mediante publicación secuencial.
  - **Limpieza automática:** Eliminan archivos procesados correctamente para evitar duplicados.
  - **Registro detallado:** Mantienen logs detallados de todas las operaciones para diagnóstico.

**Flujo de trabajo:**

1. Monitorizan directorios específicos (`../storage/app/mqtt/server1` y `../storage/app/mqtt/server2`).
2. Procesan archivos JSON encontrados en estos directorios y sus subdirectorios.
3. Extraen el tópico MQTT y el contenido del mensaje de cada archivo.
4. Publican los mensajes en los brokers MQTT configurados.
5. Eliminan los archivos procesados correctamente.
6. Registran todas las operaciones y errores en logs detallados.

**Diferencias entre servidores:**

- `sender-mqtt-server1.js`: Se conecta al broker MQTT principal (MQTT_SENSORICA_SERVER).
- `sender-mqtt-server2.js`: Se conecta al broker MQTT secundario (MQTT_SERVER), utilizado para comunicación con sistemas externos.

**2. Transformador de Sensores (`sensor-transformer.js`):**

- **Descripción:** Procesa y transforma datos de sensores industriales para su uso en el sistema.
- **Características principales:**
  - **Transformación configurable:** Aplica algoritmos de transformación específicos para cada tipo de sensor.
  - **Filtrado inteligente:** Elimina lecturas erróneas, duplicadas o fuera de rango.
  - **Conversión de unidades:** Normaliza las lecturas a unidades estándar del sistema.
  - **Calibración virtual:** Permite ajustar las lecturas mediante factores de calibración.
  - **Integración MQTT:** Recibe datos de sensores vía MQTT y publica los datos transformados.

**3. Cliente MQTT para Sensores (`client-mqtt-sensors.js`):**

- **Descripción:** Gestiona la comunicación con sensores industriales mediante protocolo MQTT.
- **Características principales:**
  - **Descubrimiento automático:** Detecta y configura nuevos sensores conectados a la red.
  - **Monitoreo en tiempo real:** Supervisa el estado y las lecturas de los sensores.
  - **Gestión de alarmas:** Detecta y notifica condiciones anormales en los sensores.
  - **Almacenamiento local:** Guarda temporalmente lecturas cuando la conexión está interrumpida.
  - **Sincronización:** Actualiza la configuración de sensores desde la base de datos.

**4. Cliente MQTT para RFID (`client-mqtt-rfid.js`):**

- **Descripción:** Gestiona la comunicación con lectores RFID mediante protocolo MQTT.
- **Características principales:**
  - **Procesamiento de tags:** Decodifica y procesa datos de tags RFID (EPC, TID, etc.).
  - **Filtrado de lecturas:** Elimina lecturas duplicadas o no válidas.
  - **Asociación de tags:** Vincula tags RFID con operarios, productos o ubicaciones.
  - **Detección de eventos:** Identifica eventos de entrada/salida de zonas de trabajo.
  - **Integración con API:** Envía datos procesados a la API REST de Sensorica.

**5. Configuración RFID (`config-rfid.js`):**

- **Descripción:** Proporciona configuración centralizada para el sistema RFID.
- **Características principales:**
  - **Definición de antenas:** Configura parámetros de antenas RFID (ubicación, potencia, etc.).
  - **Mapeo de zonas:** Define zonas de trabajo y su asociación con antenas RFID.
  - **Filtros de tags:** Configura filtros para tipos específicos de tags RFID.
  - **Parámetros de lectura:** Define intervalos de lectura, potencia y otros parámetros.
  - **Integración con base de datos:** Sincroniza configuración con la tabla `rfid_ants`.

Estos servidores Node.js son componentes críticos de la arquitectura de Sensorica, proporcionando capacidades de comunicación en tiempo real, procesamiento de datos y integración con dispositivos industriales que complementan el backend Laravel principal.

#### Archivos auxiliares en `node/` y ejecución con Supervisor

Además de los servidores indicados, en el directorio `node/` existen archivos auxiliares y de soporte que conviene conocer. No es necesario modificar código para usarlos: los servicios son gestionados por Supervisor y se inician automáticamente según la configuración del sistema.

- **`cert.pem` / `key.pem`**
  - Certificado y clave TLS en formato PEM usados cuando se habilita HTTPS/WSS en los servidores que lo soportan (p. ej., gateway RFID).
  - Úselos sólo si ha configurado TLS; de lo contrario, los servidores operan en HTTP/WS.

- **`index.html`**
  - Interfaz de monitoreo en tiempo real del gateway RFID (referenciada en este README como ruta `/gateway-test`).
  - Es servida por el proceso Node correspondiente (no requiere configuración adicional desde Laravel).

- **`install.sh`**
  - Script auxiliar de instalación/configuración para el entorno Node (dependencias, permisos, etc.).
  - Ejecútelo manualmente si necesita preparar el entorno; no afectará a la orquestación por Supervisor.

- **`baileys_auth_info/` y `baileys_store_multi.json`**
  - Archivos de estado/sesión de WhatsApp (librería Baileys) usados por `connect-whatsapp.js`.
  - Contienen credenciales de sesión; trate estos archivos como sensibles y evite versionarlos públicamente.

- **`wa-logs.txt`**
  - Archivo de logs del servicio de WhatsApp. Puede crecer con el tiempo; considere rotación de logs en producción.

- **`package.json` (en `node/`)**
  - Declara dependencias Node utilizadas por los servicios. Aunque define un `main`, los servicios en producción se gestionan mediante Supervisor.

**Ejecución y orquestación:**

- Los servidores Node se ejecutan bajo **Supervisor** (ver archivos `.conf` en la raíz del proyecto, por ejemplo `laravel-mqtt-rfid-to-api.conf`, `laravel-sensor-transformers.conf`, `laravel-modbus-subscriber.conf`, etc.).
- Supervisor asegura su arranque automático, reinicio en caso de fallo y registro de logs.
- No es necesario iniciar manualmente estos procesos; cualquier actualización de configuración debe aplicarse en los archivos `.conf` correspondientes o variables de entorno.

#### Scripts Python de IA y Detección de Anomalías (`python/`)

En `python/` se incluyen scripts para entrenamiento y monitoreo de anomalías en producción y en turnos. Estos scripts pueden ser gestionados por **Supervisor** para su ejecución continua (existen ejemplos de configuración en la raíz como `laravel-production-monitor-ia.conf.back` y `laravel-shift-monitor-ia.conf.back`). No se requiere modificar código para su uso en producción.

- **`entrenar_produccion.py`**
  - Entrena autoencoders por combinación `(production_line_id, sensor_type)` a partir de agregaciones de `sensor_counts`.
  - Features: `mean_time_11`, `std_time_11`, `mean_time_00`, `std_time_00` con lógica por tipo (tipo 0 usa `time_11`; resto usa `time_00`).
  - Salida: `models/line_{line}_type_{type}_autoencoder.h5` y `models/line_{line}_type_{type}_scaler.pkl`.
  - Conecta a la DB usando variables de entorno del `.env` de Laravel.

- **`detectar_anomalias_produccion.py`**
  - Monitoriza cada 60 s los últimos 15 minutos de `sensor_counts` por línea/tipo, omitiendo líneas con turno no activo.
  - Carga los modelos y scalers entrenados para evaluar el MSE y reportar anomalías por sensor.
  - Considera inactividad (pocos registros) y reporta sensores tipo 0 sin actividad reciente.
  - Requiere: TensorFlow, scikit-learn, pandas, numpy, SQLAlchemy, python-dotenv, joblib.

- **`entrena_shift.py`**
  - Construye sesiones de turnos desde `shift_history` (parejas start/end), genera features: hora inicio, hora fin, duración.
  - Entrena un autoencoder global para turnos y guarda `models/shift_autoencoder.h5` y `models/shift_scaler.save`.

- **`detectar_anomalias_shift.py`**
  - Cada 60 s analiza el último día de sesiones de turnos, calcula MSE y marca anomalías con umbral dinámico (p95).
  - Usa los artefactos `shift_autoencoder.h5` y `shift_scaler.save` generados por `entrena_shift.py`.

**Notas de ejecución con Supervisor:**

- Estos scripts pueden ejecutarse como procesos en segundo plano mediante archivos `.conf` de Supervisor (activar/ajustar los `.conf` de ejemplo si procede).
- Supervisor gestiona arranque automático, reinicios y logs; no es necesario invocarlos manualmente.

#### Servicios RS-485 v2 para básculas (`485-v2/`)

Integración con básculas/dispensadores vía RS-485/Modbus RTU y publicación/consumo de órdenes por MQTT. La configuración está en `485-v2/config.json`.

- **`config.json`**
  - MQTT: `mqtt_broker`, `mqtt_base_topic` (peso), `mqtt_status_topic`, `mqtt_dosificador_topic`, `mqtt_zero_topic`, `mqtt_tara_topic`, `mqtt_cancel_topic`.
  - Modbus: `modbus.port` (ej. `/dev/ttyUSB0`), `baudrate`, `timeout`, `stopbits`, `bytesize`, `parity`.
  - Rango de direcciones: `modbus_address_range` `{ start, end }`.
  - Otros: `batch_size` (envío por lotes), `scan_interval` (escaneo), `reconnect_interval`.

- **`swift.py`**
  - Cliente Modbus (pymodbus) que escanea direcciones y lanza hilos lectores por dispositivo.
  - Publica peso neto en `mqtt_base_topic/{direccion}` por lotes (`batch_size`).
  - Suscripciones MQTT para operar: dosificación (`.../dosifica/{dir}` con `{"value":<decimas_kg>}`), cero (`.../zero/{dir}`), tara (`.../tara/{dir}`) y lectura de tara (`{"read":true}` → responde con `{"tara":<kg>}`).
  - Publica estado cada 10s en `mqtt_status_topic` (`{"status":"OK|FALLO"}`).

- **`swift-con-cancelacion.py`**
  - Igual que `swift.py`, añade soporte de cancelación vía `mqtt_cancel_topic/{dir}` con `{"value": true}` que ejecuta cancelación por Modbus.

- **`swift-con-cancelacion-automatica.py`**
  - Igual que el anterior, pero fuerza una cancelación previa automática antes de iniciar una nueva dosificación.
  - Ajusta cadencia de lectura (intervalos más rápidos) para respuesta más ágil.

**Ejecución y orquestación:**

- Estos servicios pueden ejecutarse de forma continua bajo **Supervisor**. Configure un `.conf` que ejecute el script deseado en `485-v2/` con el entorno apropiado y gestione logs/reintentos.
- No es necesario modificar los scripts; la operación se controla vía MQTT y el archivo `config.json`.

#### Vistas Blade Principales

Las vistas Blade son componentes fundamentales de la interfaz de usuario de Sensorica, proporcionando interfaces interactivas para la gestión de producción, monitoreo OEE y organización de órdenes. A continuación se detallan las vistas más importantes del sistema.

**1. Organizador de Órdenes (`order-organizer.blade.php`):**

- **Descripción:** Proporciona una vista general de los procesos de producción disponibles para un cliente específico.
- **Características principales:**
  - **Agrupación por procesos:** Muestra los procesos disponibles agrupados por categoría.
  - **Navegación intuitiva:** Permite acceder rápidamente al tablero Kanban de cada proceso.
  - **Visualización de líneas:** Muestra el número de líneas de producción asociadas a cada proceso.
  - **Diseño responsive:** Adapta la visualización a diferentes tamaños de pantalla mediante Bootstrap.
  - **Integración con rutas:** Utiliza rutas nombradas de Laravel para la navegación entre vistas.

**Estructura de la vista:**

- **Cabecera:** Incluye título, migas de pan y navegación contextual.
- **Tarjetas de procesos:** Cada proceso se muestra como una tarjeta con su descripción y número de líneas.
- **Botón de acceso:** Enlace directo al tablero Kanban específico de cada proceso.

**2. Tablero Kanban (`order-kanban.blade.php`):**

- **Descripción:** Implementa un sistema Kanban completo para la gestión visual de órdenes de producción.
- **Características principales:**
  - **Drag & Drop:** Permite mover órdenes entre columnas mediante interacción drag & drop.
  - **Columnas dinámicas:** Genera columnas basadas en líneas de producción y estados finales.
  - **Filtrado avanzado:** Incluye búsqueda en tiempo real por ID de orden, cliente y otros campos.
  - **Indicadores visuales:** Muestra estados de líneas de producción, prioridad de órdenes y alertas.
  - **Menús contextuales:** Proporciona acciones rápidas para cada orden y columna.
  - **Actualización en tiempo real:** Sincroniza el estado del tablero periódicamente con el servidor.
  - **Modo pantalla completa:** Permite visualizar el tablero en modo pantalla completa.

**Estructura de la vista:**

- **Barra de filtros:** Controles para búsqueda, pantalla completa y navegación.
- **Tablero Kanban:** Contenedor principal con columnas para cada línea de producción y estados finales.
- **Tarjetas de órdenes:** Representación visual de cada orden con información relevante.
- **Leyenda visual:** Explicación de los iconos y colores utilizados en las tarjetas.
- **Modales:** Interfaces para editar notas, gestionar incidencias y configurar disponibilidad.

**Interacción JavaScript:**

- **Gestión de eventos:** Manejo de eventos de arrastrar y soltar para las tarjetas.
- **Validación de movimientos:** Lógica para permitir o restringir movimientos según el estado de las órdenes.
- **Actualización asíncrona:** Comunicación con el servidor mediante AJAX para guardar cambios.
- **Filtrado en tiempo real:** Búsqueda dinámica sin necesidad de recargar la página.
- **Gestión de estados:** Manejo del estado de las líneas de producción (activa, pausada, detenida).

**3. Vistas de Monitoreo OEE (`oee/index.blade.php`, `oee/create.blade.php`, `oee/edit.blade.php`):**

- **Descripción:** Conjunto de vistas para configurar, visualizar y analizar métricas OEE (Overall Equipment Effectiveness).
- **Características principales:**
  - **Gestión de monitores:** Interfaz CRUD completa para configurar monitores OEE por línea de producción.
  - **Integración MQTT:** Configuración de tópicos MQTT para la recolección de datos en tiempo real.
  - **Integración Modbus:** Activación/desactivación de conexiones Modbus para sensores industriales.
  - **Configuración de turnos:** Definición de horarios de inicio de turnos para cálculos precisos.
  - **Visualización tabular:** Presentación de monitores configurados mediante DataTables.
  - **Navegación contextual:** Migas de pan (breadcrumbs) para facilitar la navegación entre secciones relacionadas.

**Estructura de las vistas:**

- **Vista de índice (`index.blade.php`):**
  - Tabla responsive con DataTables para listar todos los monitores OEE.
  - Columnas para ID, línea de producción, tópicos MQTT, estado de sensores y Modbus.
  - Acciones para editar y eliminar monitores.
  - Integración con rutas nombradas de Laravel para la navegación.

- **Vista de creación (`create.blade.php`):**
  - Formulario para configurar nuevos monitores OEE.
  - Generación automática de tópicos MQTT basados en el nombre de la línea de producción.
  - Opciones para activar/desactivar sensores y conexiones Modbus.
  - Selector de fecha/hora para configurar inicio de turnos.

- **Vista de edición (`edit.blade.php`):**
  - Formulario prellenado con la configuración actual del monitor.
  - Opciones para modificar tópicos MQTT, estado de sensores y configuración de turnos.
  - Validación de formularios para garantizar datos correctos.

**4. Vistas de Gestión de Incidencias:**

- **Descripción:** Interfaces para registrar, visualizar y gestionar incidencias en la producción.
- **Características principales:**
  - **Listado filtrable:** Tabla de incidencias con filtros por fecha, tipo y estado.
  - **Detalles completos:** Vista detallada de cada incidencia con información contextual.
  - **Registro de notas:** Capacidad para añadir notas y seguimiento a cada incidencia.
  - **Integración con Kanban:** Vinculación directa con el tablero Kanban para visualizar órdenes afectadas.
  - **Gestión de estados:** Flujo de trabajo para la resolución de incidencias.

Estas vistas Blade constituyen la interfaz principal de Sensorica, proporcionando una experiencia de usuario intuitiva y funcional para la gestión de producción industrial. La combinación de Laravel Blade con JavaScript moderno permite crear interfaces dinámicas y reactivas que facilitan la visualización y manipulación de datos complejos en tiempo real.

### Gestión de Incidencias

Sistema para el registro y seguimiento de problemas en la producción:

- **Registro**: Alta de incidencias vinculadas a órdenes de producción (vía UI/API). El Kanban incluye una columna "Incidencias" que centraliza las órdenes en estado de incidencia.
- **Categorización**: Clasificación por motivo (reason) y estado de la orden afectada.
- **Asignación**: Posibilidad de asociar creador/responsable (campo `created_by`).
- **Seguimiento**: Fechas de creación/actualización, estado activo/finalizado y notas.
- **Análisis**: Listados filtrables y relación con el Kanban para detectar cuellos de botella.

#### Vistas Blade de Incidencias

- **Listado (`resources/views/customers/production-order-incidents/index.blade.php`)**
  - Ruta: `customers.production-order-incidents.index`.
  - Tabla con columnas: `#`, `ORDER ID`, `REASON`, `STATUS`, `CREATED BY`, `CREATED AT`, `ACTIONS`.
  - Estado visual:
    - `Incidencia activa` si `productionOrder.status == 3` (badge rojo).
    - `Incidencia finalizada` en caso contrario (badge gris).
  - Acciones: Ver detalle y eliminar (eliminación protegida por permisos `@can('delete', $customer)`).
  - Acceso rápido: Botón a `Order Organizer` (`customers.order-organizer`).

- **Detalle (`resources/views/customers/production-order-incidents/show.blade.php`)**
  - Ruta: `customers.production-order-incidents.show`.
  - Muestra: ID de orden, motivo, creador, `created_at`, `updated_at`, estado de la orden y estado de incidencia.
  - Acciones: Volver al listado y eliminar (con confirmación y control de permisos).
  - Sección de notas: listado/gestión de notas asociadas a la incidencia.

- **Integración con Kanban**
  - En `customers/order-kanban.blade.php` se define la columna `paused` con etiqueta `Incidencias`, integrando visualmente las órdenes afectadas en el flujo operativo.

### Otras Vistas Blade Relevantes

- **`resources/views/productionlines/liststats.blade.php`**
  - Panel de estadísticas por línea de producción con estados y KPI operativos.
  - Usa badges para estados: `Incidencia` (rojo), entre otros.
  - Integra tablas y componentes JS para filtrado y visualización.

- **`resources/views/productionlines/status-legend.blade.php`**
  - Leyenda compacta de estados utilizados en los paneles (incluye `Incidencia`).

- **`resources/views/dashboard/homepage.blade.php`**
  - Dashboard general con tarjetas/resúmenes. Incluye bloques para "estado de líneas con incidencias".

Estas vistas complementan el Kanban y OEE, ofreciendo un panorama operativo con foco en estados y alertas.

### Vistas Blade de Clientes, Líneas y Sensores

- **Clientes (`resources/views/customers/*.blade.php`)**
  - `index/create/edit`: Gestión CRUD de clientes, navegación hacia organizador/kanban por cliente.

- **Líneas de Producción (`resources/views/modbuses/*.blade.php`, `resources/views/oee/*.blade.php`)**
  - `modbuses/index/create/edit`: Configuración de endpoints Modbus por línea.
  - `oee/index/create/edit`: Alta y administración de monitores OEE por línea.

- **Sensores**
  - Listado/detalle accesible desde breadcrumbs de OEE: `route('sensors.index', ['id' => $production_line_id])`.

Estas pantallas soportan el flujo de alta y configuración técnica de cada centro/línea y su instrumentación (sensores, Modbus, OEE).

### Usuarios, Roles y Permisos

Sensorica usa Spatie Laravel Permission para control de acceso basado en roles/permisos.

- **Modelo de Usuario**: `app/Models/User.php` usa `Spatie\Permission\Traits\HasRoles`.
- **Configuración**: `config/permission.php` define los modelos `Role` y `Permission`.
- **Seeders de permisos**:
  - `database/seeders/DatabaseSeeder.php` (registro genérico de permisos).
  - `database/seeders/OriginalOrderPermissionsTableSeeder.php` (permisos de órdenes originales).
  - `database/seeders/ProductionLineProcessesPermissionSeeder.php` (permisos de procesos por línea).
  - `database/seeders/ProductionLineOrdersKanbanPermissionSeeder.php` (permisos de tablero Kanban).
  - `database/seeders/WorkCalendarPermissionSeeder.php` (permisos de calendario laboral).

- **Controladores con middleware `permission:`**:
  - `CustomerOriginalOrderController`: `original-order-list|original-order-create|original-order-edit|original-order-delete`.
  - `ProcessController`: `process-show|process-create|process-edit|process-delete`.
  - `ProductionLineProcessController`: `productionline-process-view|create|edit|delete`.
  - `ProductionOrderIncidentController`: `productionline-orders` (index/show), `productionline-delete` (destroy).
  - `WorkCalendarController`: `workcalendar-list|create|edit|delete`.
  - Gestión de roles/permisos: `RoleController` (`manage-role|create-role|edit-role|delete-role`), `PermissionController`, `PermissionManageController`.

- **Patrón de uso**:
  - Middleware: `->middleware('permission:perm-a|perm-b', ['only' => ['index','show']])`.
  - Asignación típica: usuarios reciben roles; roles agrupan permisos definidos por los seeders.

Este esquema garantiza control de acceso granular en vistas y endpoints, alineado con los módulos de producción, procesos, Kanban e incidencias.

## 🔧 Tecnologías Utilizadas

- **Backend**: Laravel (PHP), MySQL/Percona
- **Frontend**: Blade, JavaScript, Bootstrap, SweetAlert2
- **Comunicación en Tiempo Real**: MQTT, WebSockets
- **Servicios en Segundo Plano**: Supervisor, Laravel Commands
- **Integración IoT**: Protocolos MQTT, Modbus
- **Contenedores**: Docker (opcional)
- **Monitoreo**: Sistema propio de logs y alertas

## 💻 Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o Percona equivalente
- Servidor web compatible con PHP (Apache, Nginx)
- Supervisor para procesos en segundo plano
- Broker MQTT (como Mosquitto)
- Conexión a Internet para integraciones externas

## 🚀 Instalación y Configuración

1. **Clonar el repositorio**:
   ```bash
   git clone [url-del-repositorio]
   ```

2. **Instalar dependencias**:
   ```bash
   composer install
   npm install
   ```

3. **Configurar variables de entorno**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos en .env**:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sensorica
   DB_USERNAME=usuario
   DB_PASSWORD=contraseña
   ```

5. **Configurar zona horaria**:
   ```
   APP_TIMEZONE=Europe/Madrid
   ```

6. **Ejecutar migraciones y seeders**:
   ```bash
   php artisan migrate --seed
   ```

7. **Compilar assets**:
   ```bash
   npm run dev
   ```

8. **Configurar Supervisor**:
   ```bash
   cp laravel-*.conf /etc/supervisor/conf.d/
   supervisorctl reread
   supervisorctl update
   ```

## Estructura de la Base de Datos

Sensorica utiliza una arquitectura de base de datos relacional con MySQL/MariaDB, implementando el ORM Eloquent de Laravel para gestionar las relaciones entre entidades. A continuación se describen los modelos principales y sus relaciones:

### Modelos Principales y Relaciones

#### Sistema de Producción

- **ProductionOrder**: Representa una orden de producción en el tablero Kanban.
  - Pertenece a (`belongsTo`) una `ProductionLine`
  - Pertenece a (`belongsTo`) un `OriginalOrder`
  - Pertenece a (`belongsTo`) un `OriginalOrderProcess`
  - Tiene muchos (`hasMany`) `BarcodeScan`
  - Tiene muchos (`hasMany`) `ProductionOrderIncident`

- **OriginalOrder**: Representa una orden original recibida desde un sistema ERP externo.
  - Pertenece a (`belongsTo`) un `Customer`
  - Tiene muchos (`hasMany`) `OriginalOrderProcess`
  - Tiene muchos (`hasMany`) `ProductionOrder`
  - Tiene muchos a través de (`hasManyThrough`) `OriginalOrderArticle`

- **OriginalOrderProcess**: Tabla pivote que relaciona órdenes originales con procesos.
  - Extiende la clase `Pivot` de Laravel
  - Pertenece a (`belongsTo`) un `OriginalOrder`
  - Pertenece a (`belongsTo`) un `Process`
  - Tiene muchos (`hasMany`) `OriginalOrderArticle`
  - Tiene muchos (`hasMany`) `ProductionOrder`

- **Process**: Define un proceso de producción estándar.
  - Pertenece a muchos (`belongsToMany`) `ProductionLine` a través de `production_line_process`
  - Métodos para navegación secuencial: `nextProcess()` y `previousProcess()`

- **ProductionLine**: Representa una línea de producción física.
  - Pertenece a (`belongsTo`) un `Customer`
  - Pertenece a muchos (`belongsToMany`) `Process` a través de `production_line_process`
  - Tiene muchos (`hasMany`) `ProductionOrder`
  - Tiene muchos (`hasMany`) `Sensor`
  - Tiene uno (`hasOne`) `Barcode`

#### Monitoreo OEE

- **MonitorOee**: Almacena métricas OEE calculadas para líneas de producción.
  - Pertenece a (`belongsTo`) una `ProductionLine`
  - Incluye métricas de disponibilidad, rendimiento, calidad y OEE global

- **OrderStat**: Registra estadísticas de producción por orden.
  - Pertenece a (`belongsTo`) una `ProductionOrder`
  - Pertenece a (`belongsTo`) un `Operator`
  - Pertenece a (`belongsTo`) un `ShiftList`
  - Almacena unidades producidas, peso, tiempo, etc.

#### Sensores y Dispositivos

- **Sensor**: Representa un sensor físico en una línea de producción.
  - Pertenece a (`belongsTo`) una `ProductionLine`
  - Tiene muchos (`hasMany`) `SensorReading`
  - Tiene muchos (`hasMany`) `SensorTransformer`

- **RfidReading**: Registra lecturas de dispositivos RFID.
  - Pertenece a (`belongsTo`) un `RfidAnt` (antena)
  - Almacena EPC, TID, RSSI y timestamp

- **Modbus**: Configura conexiones Modbus/SCADA.
  - Tiene muchos (`hasMany`) `ModbusHistory`
  - Define registros, direcciones, tipos de datos, etc.

#### Usuarios y Permisos

- **User**: Usuarios del sistema (extiende el modelo base de Laravel).
  - Pertenece a muchos (`belongsToMany`) `Role`
  - Pertenece a muchos (`belongsToMany`) `Permission`
  - Pertenece a muchos (`belongsToMany`) `Customer`

- **Role**: Roles de usuario (ej: Administrador, Supervisor, Operario).
  - Pertenece a muchos (`belongsToMany`) `Permission`
  - Pertenece a muchos (`belongsToMany`) `User`

- **Permission**: Permisos individuales del sistema.
  - Pertenece a muchos (`belongsToMany`) `Role`
  - Pertenece a muchos (`belongsToMany`) `User`

### Diagrama Simplificado de Relaciones

```
Customer 1 → * ProductionLine 1 → * ProductionOrder
    |
    ↓
    1
OriginalOrder 1 → * OriginalOrderProcess * ← 1 Process
    |                    |
    |                    ↓
    |                    *
    ↓                OriginalOrderArticle
    *
ProductionOrder * ← 1 ProductionLine 1 → * Sensor
    |
    ↓
    *
ProductionOrderIncident
```

### Campos Clave

Los siguientes campos son fundamentales para entender el flujo de datos:

- **ProductionOrder.status**: Define el estado de una orden en el tablero Kanban:
  - 0: Pendiente
  - 1: En proceso
  - 2: Finalizada
  - 3: Incidencia

- **ProductionOrder.orden**: Número secuencial que determina el orden de procesamiento dentro de una línea.

- **OriginalOrderProcess.in_stock**: Indica si hay stock disponible para este proceso (0: sin stock, 1: con stock).

- **Process.sequence**: Define el orden secuencial de los procesos en el flujo de producción.

- **ProductionLine.token**: Identificador único usado en endpoints API para identificar líneas de producción.

El sistema utiliza una base de datos relacional con las siguientes entidades principales:

- **Customers**: Clientes del sistema
- **ProductionLines**: Líneas de producción asociadas a clientes
- **ProductionOrders**: Órdenes en el sistema Kanban
- **OriginalOrders**: Órdenes importadas de sistemas externos
- **OriginalOrderProcesses**: Procesos asociados a órdenes originales
- **OriginalOrderArticles**: Artículos asociados a procesos
- **Sensors**: Configuración de sensores
- **SensorHistory**: Lecturas históricas de sensores
- **MonitorOee**: Configuración de monitoreo OEE
- **ProductionOrderIncidents**: Registro de incidencias
- **WorkCalendar**: Calendario laboral para cálculos de producción

### 🔬 Detalle de Modelos y Eventos (Eloquent)

Esta sección documenta los modelos principales, sus campos críticos, relaciones y eventos de ciclo de vida según la implementación actual en `app/Models/`.

#### ProductionOrder (`app/Models/ProductionOrder.php`)

- __Tabla__: `production_orders`
- __Fillable__: `has_stock`, `production_line_id`, `original_production_line_id`, `barcoder_id`, `order_id`, `json`, `status`, `box`, `units_box`, `number_of_pallets`, `units`, `orden`, `theoretical_time`, `accumulated_time`, `process_category`, `delivery_date`, `customerId`, `original_order_id`, `original_order_process_id`, `grupo_numero`, `processes_to_do`, `processes_done`, `is_priority`, `finished_at`, `fecha_pedido_erp`, `estimated_start_datetime`, `estimated_end_datetime`, `note`
- __Casts__: `json: array`, `processed: boolean`, `orden: integer`, `delivery_date: datetime`, `status: integer`, `theoretical_time: float`, `is_priority: boolean`, `finished_at: datetime`, `fecha_pedido_erp: datetime`, `estimated_start_datetime: datetime`, `estimated_end_datetime: datetime`
- __Relaciones__:
  - `originalOrder()` → `belongsTo(OriginalOrder, original_order_id)`
  - `originalOrderProcess()` → `belongsTo(OriginalOrderProcess, original_order_process_id)`
  - `productionLine()` → `belongsTo(ProductionLine)`
  - `originalProductionLine()` → `belongsTo(ProductionLine, original_production_line_id)`
  - `barcode()` → `belongsTo(Barcode)`
  - `barcodeScans()` → `hasMany(BarcodeScan)`
- __Eventos__:
  - `creating`:
    - Calcula `orden` incremental por `production_line_id`.
    - Establece `status = 0` si viene nulo.
    - Si existe una orden con mismo `order_id` y misma `production_line_id`, la archiva modificando su `order_id` a `order_id-<process_category>-<grupo_numero>` y guarda.
  - `saving`:
    - Si `status` cambia a 2 y `finished_at` está vacío, asigna `finished_at = now()`.
    - Si cambia `production_line_id`, busca `Barcode` de esa línea y asigna `barcoder_id` (loggea cuando no encuentra).
  - `saved`:
    - Si `status` cambió y es 2, marca el `OriginalOrderProcess` relacionado como finalizado (`finished = 1`, `finished_at = now()`).

Estados Kanban utilizados: `status = 0 (Pendiente)`, `1 (En proceso)`, `2 (Finalizada)`, `3 (Incidencia)`.

#### OriginalOrder (`app/Models/OriginalOrder.php`)

- __Fillable__: `order_id`, `customer_id`, `client_number`, `order_details`, `processed`, `finished_at`, `delivery_date`, `in_stock`, `fecha_pedido_erp`
- __Casts__: `order_details: json`, `processed: boolean`, `finished_at: datetime`
- __Relaciones__:
  - `processes()` → `belongsToMany(Process, 'original_order_processes')` usando pivot `OriginalOrderProcess` con `pivot: id, time, created, finished, finished_at, grupo_numero`
  - `customer()` → `belongsTo(Customer)`
  - `articles()` → `hasManyThrough(OriginalOrderArticle, OriginalOrderProcess, ...)`
  - `orderProcesses()` / `originalOrderProcesses()` → `hasMany(OriginalOrderProcess)`
  - `productionOrders()` → `hasMany(ProductionOrder)`
- __Lógica clave__:
  - `allProcessesFinished()` comprueba si todos los pivots están `finished = true`.
  - `updateInStockStatus()` establece `in_stock` a 0 si algún proceso tiene `in_stock = 0`, o 1 si todos son 1.
  - `updateFinishedStatus()` fija/borra `finished_at` según resultado de `allProcessesFinished()`, usando `saveQuietly()` para evitar eventos recursivos.

#### OriginalOrderProcess (`app/Models/OriginalOrderProcess.php`)

- __Extiende__: `Pivot` (tabla `original_order_processes`)
- __Fillable__: `original_order_id`, `process_id`, `time`, `box`, `units_box`, `number_of_pallets`, `created`, `finished`, `finished_at`, `grupo_numero`, `in_stock`
- __Casts__: `time: decimal:2`, `box: integer`, `units_box: integer`, `number_of_pallets: integer`, `created: boolean`, `finished: boolean`, `finished_at: datetime`, `in_stock: integer`
- __Relaciones__:
  - `articles()` → `hasMany(OriginalOrderArticle, 'original_order_process_id')`
  - `originalOrder()` → `belongsTo(OriginalOrder, 'original_order_id')`
  - `process()` → `belongsTo(Process)`
  - `productionOrders()` → `hasMany(ProductionOrder, 'original_order_process_id')`
- __Eventos__:
  - `saving`: si `finished` cambia, sincroniza `finished_at`. Si `in_stock` cambia en creación, precarga `articles`.
  - `saved`: actualiza primero su propio `in_stock` en base a artículos (`updateStockStatus()`), luego:
    - `originalOrder?->updateFinishedStatus()`
    - `originalOrder?->updateInStockStatus()`

#### Process (`app/Models/Process.php`)

- __Fillable__: `code`, `name`, `sequence`, `description`, `factor_correccion` (cast `decimal:2`, default 1.00)
- __Relaciones__:
  - `productionLines()` → `belongsToMany(ProductionLine)` con `order` en pivot
  - `nextProcess()` / `previousProcess()` por `sequence`

#### ProductionLine (`app/Models/ProductionLine.php`)

- __Fillable__: `customer_id`, `name`, `token`
- __Relaciones__:
  - `processes()` → `belongsToMany(Process)` con `order` en pivot
  - `customer()` → `belongsTo(Customer)`
  - `barcodes()` → `hasMany(Barcode)`
  - `sensors()` → `hasMany(Sensor, 'production_line_id')`
  - `orderStats()` → `hasMany(OrderStat, 'production_line_id')`
  - `lastShiftHistory()` → `hasOne(ShiftHistory)->latest()`
  - `barcodeScans()` → `hasMany(BarcodeScan)`

#### Operator (`app/Models/Operator.php`)

- __Fillable__: `client_id`, `name`, `password`, `email`, `phone`, `count_shift`, `count_order`
- __Hidden__: `password`
- __Relaciones__:
  - `client()` → `belongsTo(Client)`
  - `operatorPosts()` → `hasMany(OperatorPost, 'operator_id')`
  - `shiftHistories()` → `hasMany(ShiftHistory, 'operator_id')`
  - `barcodeScans()` → `hasMany(BarcodeScan)`
  - `orderStats()` → `belongsToMany(OrderStat, 'order_stats_operators')` con pivote `shift_history_id`, `time_spent`, `notes`

#### OrderStat (`app/Models/OrderStat.php`)

- __Tabla__: `order_stats`
- __Fillable__: métricas de producción y peso por orden/turno/línea (p. ej. `production_line_id`, `order_id`, `units`, `oee`, `weights_*`, etc.)
- __Relaciones__:
  - `productionLine()` → `belongsTo(ProductionLine)`
  - `productList()` → `belongsTo(ProductList)`
  - `operators()` / `shiftHistories()` → `belongsToMany` vía `order_stats_operators`
  - `orderStatOperators()` → `hasMany(OrderStatOperator)`

#### MonitorOee (`app/Models/MonitorOee.php`)

- __Fillable__: `production_line_id`, `sensor_active`, `modbus_active`, `mqtt_topic`, `mqtt_topic2`, `topic_oee`, `time_start_shift`
- __Relaciones__: `productionLine()`, `sensor()`, `modbus()`
- __Eventos__: en `updating`, `created`, `deleted` llama a `restartSupervisor()` (ejecuta `sudo supervisorctl restart all` y registra en el canal `supervisor`).

#### Sensor (`app/Models/Sensor.php`)

- __Fillable__: campos de configuración del sensor (tópicos MQTT, contadores, parámetros de corrección, etc.)
- __Relaciones__: `productionLine()`, `controlWeights()`, `controlHeights()`, `modbuses()`, `barcoder()`, `sensorCounts()`, `productList()`, `history()`
- __Eventos__:
  - `creating`: genera `token` único (`Str::uuid()`).
  - `updating`/`deleted`: si cambian `mqtt_topic_sensor`/`mqtt_topic_1` o se elimina, llama a `restartSupervisor()`.
  - `restartSupervisor()` usa `sudo supervisorctl restart all` con logs en canal `supervisor`.

## ⚙️ Servicios en Segundo Plano

## 🔄 Servicios en Segundo Plano

Sensorica implementa una arquitectura de microservicios donde múltiples procesos trabajan de forma coordinada para garantizar el funcionamiento del sistema en tiempo real. Estos servicios se gestionan mediante Supervisor y se dividen en dos categorías principales: comandos Laravel y servidores Node.js.

### 📊 Comandos Laravel (Supervisor)

Los siguientes comandos se ejecutan como procesos daemon gestionados por Supervisor:

#### Monitoreo OEE y Producción

- **CalculateProductionMonitorOee**: Calcula métricas OEE (Eficiencia Global del Equipo) en tiempo real, procesando datos de sensores y modbuses para determinar disponibilidad, rendimiento y calidad.
- **CalculateProductionDowntime**: Monitoriza y registra tiempos de inactividad en las líneas de producción, categorizando las paradas según su causa.
- **CalculateOptimalProductionTime**: Calcula tiempos teóricos óptimos para cada orden de producción basándose en históricos y configuraciones.
- **UpdateAccumulatedTimes**: Actualiza los tiempos acumulados de producción para órdenes en proceso, esencial para el cálculo de eficiencia.

#### Integración MQTT

- **MqttSubscriber**: Suscriptor principal que escucha tópicos MQTT relacionados con códigos de barras y actualiza órdenes de producción.
- **MqttSubscriberLocal**: Versión optimizada para entornos locales que reduce la latencia en la comunicación.
- **MqttShiftSubscriber**: Especializado en la gestión de mensajes MQTT relacionados con turnos de trabajo.

#### Sensores y Dispositivos

- **ReadSensors**: Procesa datos de sensores industriales recibidos vía MQTT y los almacena en la base de datos.
- **ReadRfidReadings**: Gestiona lecturas de dispositivos RFID, aplicando filtros y reglas de negocio específicas.
- **ReadModbus**: Integra con sistemas SCADA/Modbus para control de maquinaria industrial y dosificación.
- **ReadBluetoothReadings**: Procesa datos de sensores Bluetooth para seguimiento de activos y personal.

#### Sincronización y Mantenimiento

- **CheckOrdersFromApi**: Sincroniza órdenes de producción con sistemas ERP externos mediante APIs configurables.
- **CheckShiftList**: Verifica y actualiza la información de turnos activos.
- **ClearOldRecords**: Realiza limpieza periódica de registros antiguos para optimizar el rendimiento de la base de datos.
- **ResetWeeklyCounts**: Reinicia contadores semanales para estadísticas y reportes.

### 🔌 Servidores Node.js

Complementando los comandos Laravel, Sensorica utiliza servidores Node.js para tareas que requieren alta concurrencia y comunicación en tiempo real:

#### Servidores MQTT

- **sender-mqtt-server1.js**: Servidor MQTT principal que gestiona la comunicación entre sensores y el sistema central. Monitoriza la carpeta `/storage/app/mqtt/server1` y publica mensajes almacenados localmente.
- **sender-mqtt-server2.js**: Servidor MQTT secundario que proporciona redundancia y balanceo de carga. Monitoriza la carpeta `/storage/app/mqtt/server2`.

#### Integración Industrial

- **client-modbus.js**: Cliente Modbus/TCP que se comunica con PLCs y sistemas SCADA industriales. Implementa caché de configuración y manejo de reconexiones.
- **mqtt-rfid-to-api.js**: Gateway que traduce mensajes MQTT de lectores RFID a llamadas a la API REST de Sensorica. Incluye interfaz web de monitoreo en tiempo real.
- **config-rfid.js**: Servidor de configuración para lectores RFID con interfaz WebSocket para administración remota.

#### Comunicación Externa

- **connect-whatsapp.js**: Servidor de integración con WhatsApp Business API que permite enviar notificaciones sobre incidencias y estados de producción a través de WhatsApp.

### 🔧 Configuración de Supervisor

Todos estos servicios se gestionan mediante archivos de configuración en `/etc/supervisor/conf.d/` que definen parámetros como:

- Número de procesos worker
- Reinicio automático
- Rotación de logs
- Prioridades de ejecución
- Dependencias entre servicios

La arquitectura distribuida permite alta disponibilidad y escalabilidad horizontal, con capacidad para procesar miles de eventos por segundo provenientes de sensores industriales.

#### 🧭 Mapa Supervisor → Comando/Script (archivo → programa → ejecución)

- `laravel-auto-finish-operator-post.conf` → `[program:operator-post-finalize]` → `php artisan operator-post:finalize`
- `laravel-calculate-optimal-production-time.conf` → `[program:calculate_optimal_time]` → `php artisan production:calculate-optimal-time`
- `laravel-calculate-production-downtime.conf` → `[program:calculate-production-downtime]` → `php artisan production:calculate-production-downtime`
- `laravel-check-bluetooth.conf` → `[program:laravel-bluetooth-check-exit]` → `php artisan bluetooth:check-exit`
- `laravel-clear-db.conf` → `[program:clear-old-records]` → `php artisan clear:old-records`
- `laravel-connect-whatsapp.conf` → `[program:connect-whatsapp]` → `node node/connect-whatsapp.js` (dir: `node/`, user: root)
- `laravel-control-antena-rfid.conf` → `[program:laravel-config-rfid-antena]` → `node node/config-rfid.js` (dir: `node/`)
- `laravel-created-production-orders.conf` → `[program:laravel-created-production-orders]` → bucle `orders:list-stock` cada 60 s
- `laravel-modbus-subscriber.conf` → `[program:laravel-modbus-subscriber]` → `node node/client-modbus.js` (dir: `node/`)
- `laravel-modbus-web-8001.conf` → `[program:modbus-web.8001]` → `python3 modbus-web-8001.py`
- `laravel-monitor-oee.conf` → `[program:calculate-monitor-oee]` → `php artisan production:calculate-monitor-oee`
- `laravel-monitor-server.conf` → `[program:servermonitor]` → `python3 servermonitor.py`
- `laravel-mqtt-rfid-to-api.conf` → `[program:laravel-mqtt-rfid-to-api]` → `node node/mqtt-rfid-to-api.js` (dir: `node/`)
- `laravel-mqtt-shift-subscriber.conf` → `[program:laravel-shift-subscriber]` → `php artisan mqtt:shiftsubscribe`
- `laravel-mqtt-subscriber-local-ordermac.conf` → `[program:subscribe-local-ordermac]` → `php artisan mqtt:subscribe-local-ordermac`
- `laravel-mqtt-subscriber-local.conf` → `[program:subscribe-local]` → `php artisan mqtt:subscribe-local`
- `laravel-mqtt_send_server1.conf` → `[program:laravel-mqtt-sendserver1]` → `node node/sender-mqtt-server1.js` (dir: `node/`)
- `laravel-orders-check.conf` → `[program:laravel-orders-check]` → bucle `orders:check` cada 1800 s (30 min)
- `laravel-production-updated-accumulated-times.conf.conf` → `[program:laravel-production-update-accumulated-times]` → bucle `production:update-accumulated-times` cada 60 s
- `laravel-read-bluetooth.conf` → `[program:laravel-read-bluetooth]` → `php artisan bluetooth:read`
- `laravel-read-rfid.conf` → `[program:laravel-read-rfid]` → `node node/client-mqtt-rfid.js` (dir: `node/`)
- `laravel-read-sensors.conf` → `[program:laravel-read-sensors]` → `node node/client-mqtt-sensors.js` (dir: `node/`)
- `laravel-reset-weekly-counts.conf` → `[program:reset-weekly-counts]` → `php artisan reset:weekly-counts`
- `laravel-sensor-transformers.conf` → `[program:laravel-sensor-transformers]` → `node node/sensor-transformer.js` (dir: `node/`)
- `laravel-server-check-host-monitor.conf` → `[program:check_host_monitor]` → `php artisan hostmonitor:check`
- `laravel-shift-list.conf` → `[program:laravel-shift-list]` → `php artisan shift:check`
- `laravel-tcp-client-local.conf` → `[program:laravel-tcp-client-local]` → `php artisan tcp:client-local`
- `laravel-tcp-client.conf` → `[program:laravel-tcp-client]` → `php artisan tcp:client`
- `laravel-tcp-server.conf` → `[program:tcp-server]` → `python3 tcp-server.py`
- `laravel-telegram-server.conf` → `[program:connect-telegram-server]` → `node telegram/telegram.js` (dir: `telegram/`, user: root)

## 📱 Sistemas Especializados

Sensorica integra varios sistemas especializados para cubrir necesidades específicas de entornos industriales:

### 💪 Sistema RFID

El sistema RFID (Radio Frequency Identification) permite el seguimiento de activos, operarios y productos en la planta de producción:

#### Componentes del Sistema RFID

- **Lectores RFID**: Dispositivos físicos que leen etiquetas RFID y envían datos a través de MQTT.
- **Antenas RFID**: Configurables por zonas para detectar entrada/salida de productos y personal.
- **Gateway MQTT-RFID**: Procesa y filtra lecturas RFID antes de enviarlas al sistema central.
- **Panel de Monitoreo**: Interfaz web en `/live-rfid/index.html` para visualización en tiempo real de lecturas.

#### Funcionalidades RFID

- **Asignación de Operarios**: Vinculación de tarjetas RFID con operarios específicos.
- **Control de Acceso**: Restricción de acceso a áreas específicas mediante RFID.
- **Seguimiento de Productos**: Trazabilidad completa del producto durante el proceso de fabricación.
- **Sistema de Bloqueo**: Capacidad para bloquear tarjetas RFID específicas (por EPC o TID).
- **Filtrado por RSSI**: Configuración de potencia mínima de señal para evitar lecturas fantasma.

### 🎛️ Sistema SCADA/Modbus

Integración con sistemas de control industrial para monitoreo y control de maquinaria:

#### Componentes SCADA

- **Cliente Modbus/TCP**: Comunicación con PLCs y controladores industriales.
- **Tolvas y Dosificadores**: Control de sistemas de dosificación industrial con precisión configurable.
- **ScadaList**: Gestión de materiales y fórmulas para sistemas de mezcla automática.

#### Funcionalidades SCADA

- **Lectura de Registros**: Lectura periódica de registros Modbus de dispositivos industriales.
- **Control de Dosificación**: Envío de comandos para dosificación precisa de materiales.
- **Alarmas y Eventos**: Detección y registro de alarmas en sistemas industriales.
- **Sincronización de Fórmulas**: Envío automático de fórmulas a sistemas de dosificación.

### 💬 Integración con WhatsApp

Sensorica incluye un sistema de notificaciones vía WhatsApp para mantener informados a supervisores y gerentes:

#### Características de la Integración WhatsApp

- **Notificaciones de Incidencias**: Envío automático de alertas cuando se registran incidencias en producción.
- **Resúmenes de Producción**: Envío programado de informes de producción diarios/semanales.
- **Comandos Remotos**: Capacidad para ejecutar comandos básicos mediante mensajes de WhatsApp.
- **Autenticación QR**: Sistema de conexión mediante código QR para vincular la cuenta de WhatsApp.

#### Configuración WhatsApp

- **Panel de Administración**: Interfaz web para configurar destinatarios y tipos de notificaciones.
- **Plantillas de Mensajes**: Mensajes predefinidos para diferentes tipos de eventos.
- **Programación de Envíos**: Configuración de horarios para envío automático de informes.

### 📚 Inventario Completo (Archivos Reales)

A continuación se listan los archivos reales detectados en el repositorio para trazabilidad directa.

#### Comandos Artisan (app/Console/Commands/)

- CalculateOptimalProductionTime.php
- CalculateProductionDowntime.php
- CalculateProductionMonitorOee.php
- CalculateProductionMonitorOeev2.php
- CheckBluetoothExit.php
- CheckHostMonitor.php
- CheckOrdersFromApi.php
- CheckShiftList.php
- ClearOldRecords.php
- ConnectWhatsApp.php
- FinalizeOperatorPosts.php
- ListStockOrdersCommand.php
- MonitorConnections.php
- MqttShiftSubscriber.php
- MqttSubscriber.php
- MqttSubscriberLocal.php
- MqttSubscriberLocalMac.php
- PublishOrderStatsCommand.php
- ReadBluetoothReadings.php
- ReadModbuBackup.php
- ReadModbus.php
- ReadModbusGroup.php
- ReadRfidReadings.php
- ReadSensors.php
- ReplicateDatabaseNightly.php
- ResetWeeklyCounts.php
- TcpClient.php
- TcpClientLocal.php
- UpdateAccumulatedTimes.php

#### Archivos Supervisor (.conf en raíz del proyecto)

- laravel-auto-finish-operator-post.conf
- laravel-calculate-optimal-production-time.conf
- laravel-calculate-production-downtime.conf
- laravel-check-bluetooth.conf
- laravel-clear-db.conf
- laravel-connect-whatsapp.conf
- laravel-control-antena-rfid.conf
- laravel-created-production-orders.conf
- laravel-modbus-subscriber.conf
- laravel-modbus-web-8001.conf
- laravel-monitor-oee.conf
- laravel-monitor-server.conf
- laravel-mqtt-rfid-to-api.conf
- laravel-mqtt-shift-subscriber.conf
- laravel-mqtt-subscriber-local-ordermac.conf
- laravel-mqtt-subscriber-local.conf
- laravel-mqtt_send_server1.conf
- laravel-orders-check.conf
- laravel-production-updated-accumulated-times.conf.conf
- laravel-read-bluetooth.conf
- laravel-read-rfid.conf
- laravel-read-sensors.conf
- laravel-reset-weekly-counts.conf
- laravel-sensor-transformers.conf
- laravel-server-check-host-monitor.conf
- laravel-shift-list.conf
- laravel-tcp-client-local.conf
- laravel-tcp-client.conf
- laravel-tcp-server.conf
- laravel-telegram-server.conf

Nota: la configuración efectiva suele residir en `/etc/supervisor/conf.d/`, pero estos `.conf` de proyecto documentan los programas y comandos a declarar allí.

#### Servidores Node.js

- node/client-modbus.js
- node/client-mqtt-rfid.js
- node/client-mqtt-sensors.js
- node/config-rfid.js
- node/connect-whatsapp.js
- node/mqtt-rfid-to-api.js
- node/sender-mqtt-server1.js
- node/sender-mqtt-server2.js
- node/sensor-transformer.js
- telegram/telegram.js

Relación con secciones previas:
- SCADA/Modbus: `node/client-modbus.js`
- Gateway RFID: `node/mqtt-rfid-to-api.js`, `node/config-rfid.js`, `node/client-mqtt-rfid.js`
- MQTT publishers: `node/sender-mqtt-server1.js`, `node/sender-mqtt-server2.js`
- Transformación de sensores: `node/sensor-transformer.js`
- WhatsApp: `node/connect-whatsapp.js`
- Telegram: `telegram/telegram.js`

### 📦 Documentación detallada de servidores y servicios

#### node/client-modbus.js
- __Propósito__: Suscriptor MQTT para valores Modbus; aplica reglas de repetición/variación y publica a API cuando corresponde.
- __ENV__: `MQTT_SENSORICA_SERVER`, `MQTT_SENSORICA_PORT`, `DB_HOST/PORT/USERNAME/PASSWORD/DB_DATABASE`.
- __DB__: Lee `modbuses` (campos: `mqtt_topic_modbus`, `rep_number`, `model_name`, `variacion_number`, `conversion_factor`, `dimension_default`).
- __MQTT__: Suscribe dinámico por `modbuses.mqtt_topic_modbus` (QoS 1). Cachea config por tópico y controla repeticiones/umbrales.
- __HTTP__: Llama APIs internas según lógica (ver controlador correspondiente).
- __Supervisor__: `[program:laravel-modbus-subscriber]` → `node node/client-modbus.js`.
- __Operación/Logs__: Reconexión a MQTT/DB con backoff, limpieza de cachés en reconnect, logs con timestamps.

#### node/client-mqtt-rfid.js
- __Propósito__: Consumidor de lecturas RFID desde tópicos por antena; valida turnos y filtra duplicados por RSSI/intervalo.
- __ENV__: `MQTT_SENSORICA_*`, `LOCAL_SERVER`, `DB_*`.
- __DB__: Lee `rfid_ants` (topic, rssi_min, min_read_interval_ms, production_line_id), `shift_history` (estado turno), `rfid_blocked` (EPCs).
- __MQTT__: Suscribe a `rfid_ants.mqtt_topic`. Caches por antena, mapas de EPC/TID ignorados temporales.
- __HTTP__: POST a `${LOCAL_SERVER}/api/...` para registrar eventos RFID.
- __Supervisor__: `[program:laravel-read-rfid]` → `node node/client-mqtt-rfid.js`.
- __Operación__: Re-suscribe al reconectar; actualización periódica de caches; logs de control de flujo.

#### node/client-mqtt-sensors.js
- __Propósito__: Consumidor de sensores genéricos; extrae valores con rutas JSON y envía a API con reintentos y backoff.
- __ENV__: `MQTT_SENSORICA_*`, `LOCAL_SERVER` (HTTPS permitido), `DB_*`.
- __DB__: Lee `sensors` (mqtt_topic_sensor, sensor_type, invers_sensors, json_api).
- __MQTT__: Suscribe/unsuscribe dinámico según `sensors`.
- __HTTP__: POST `${LOCAL_SERVER}/api/sensor-insert` con `https.Agent({ rejectUnauthorized:false })` para entornos con TLS propio.
- __Supervisor__: `[program:laravel-read-sensors]` → `node node/client-mqtt-sensors.js`.
- __Operación__: Reintentos exponenciales y logging detallado de extracciones JSON.

#### node/config-rfid.js
- __Propósito__: Panel Socket.IO para administrar el lector RFID (tarea MQTT, lectura, antenas) vía API HTTP del lector.
- __ENV__: `MQTT_SENSORICA_*`, `RFID_READER_IP`, `RFID_READER_PORT` en `.env` de Laravel.
- __DB__: No requiere; lee `.env` para parámetros del lector.
- __MQTT__: Publica/escucha en `rfid_command` para comandos/estados.
- __HTTP externo__: `http://RFID_READER_IP:RFID_READER_PORT/API/Task` (endpoints `getMQTTInfo`, enable/disable, start/stop reading, etc.).
- __Supervisor__: `[program:laravel-config-rfid-antena]` → `node node/config-rfid.js`.
- __Operación__: Auto-monitoreo periódico, caché de estado/antenas, logs coloreados y reconexión controlada.

#### node/mqtt-rfid-to-api.js
- __Propósito__: Gateway Express + WebSocket para visualización en tiempo real de mensajes RFID y gestión de suscripciones por DB.
- __ENV__: `MQTT_SENSORICA_*`, `DB_*`, `MQTT_GATEWAY_PORT`, `USE_HTTPS`, `SSL_KEY_PATH`, `SSL_CERT_PATH`.
- __DB__: Lee tópicos y metadatos de antenas; mantiene `antennaDataMap`.
- __MQTT__: Suscribe a tópicos definidos en DB; re-sync en reconexiones.
- __HTTP__: 
  - REST: `/api/gateway-messages` (incluye topics_info)
  - UI: `/gateway-test` (viewer con WebSocket)
  - WebSocket: broadcast de mensajes y lista de tópicos/antenas
- __Supervisor__: `[program:laravel-mqtt-rfid-to-api]` → `node node/mqtt-rfid-to-api.js`.
- __Operación__: Soporta HTTP/WS y HTTPS/WSS; almacena histórico acotado en memoria.

#### node/sender-mqtt-server1.js
- __Propósito__: Publica archivos JSON como mensajes MQTT para “server1”. Elimina archivos tras éxito.
- __ENV__: `MQTT_SENSORICA_*`.
- __FS__: Lee `storage/app/mqtt/server1/` recursivamente.
- __MQTT__: Publica según `data.topic` y `data.message` del JSON.
- __Supervisor__: `[program:laravel-mqtt-sendserver1]` → `node node/sender-mqtt-server1.js`.
- __Operación__: Vigila cambios de `.env`, reconexión automática, manejo de JSON inválidos (eliminación segura + log).

#### node/sender-mqtt-server2.js
- __Propósito__: Igual a server1, usando broker alterno (`MQTT_SERVER`/`MQTT_PORT`).
- __ENV__: `MQTT_SERVER`, `MQTT_PORT`.
- __FS__: `storage/app/mqtt/server2/`.
- __Supervisor__: (si aplica) `[program:laravel-mqtt-sendserver2]` → `node node/sender-mqtt-server2.js`.

#### node/sensor-transformer.js
- __Propósito__: Transforma valores de sensores según `sensor_transformations` y publica a tópicos de salida sólo si cambia el resultado.
- __ENV__: `DB_*`, `MQTT_SENSORICA_*`.
- __DB__: Lee `sensor_transformations` (min/mid/max, output_topic, etc.).
- __MQTT__: Suscribe a `input_topic[]`; publica a `output_topic` tras `transformValue()` y deduplicación por cache.
- __Supervisor__: `[program:laravel-sensor-transformers]` → `node node/sensor-transformer.js`.
- __Operación__: Reconexión DB y MQTT; recarga periódica y detección de cambios de configuración.

#### node/connect-whatsapp.js
- __Propósito__: Servicio de WhatsApp basado en Baileys (QR login), persistencia de credenciales filtradas y callbacks a API Laravel.
- __ENV__: Dependen de Baileys/puerto local.
- __HTTP__: 
  - POST `/start-whatsapp`, `/logout`, `/get-qr`
  - Callback a `http://localhost/api/whatsapp-credentials` para guardar creds/keys filtrados
- __Supervisor__: `[program:connect-whatsapp]` → `node node/connect-whatsapp.js` (user `root`).
- __Operación__: Reconecta al cerrar no intencional; imprime QR en terminal; rota store a `baileys_store_multi.json`.

#### telegram/telegram.js
- __Propósito__: API completa para Telegram con Swagger (autenticación, mensajes, media, grupos, contactos, reglas y programación).
- __ENV__: `API_ID`, `API_HASH`, `PORT`, `API_EXTERNAL*`, `DATA_FOLDER`, `CALLBACK_BASE`.
- __HTTP__: Amplia lista de endpoints REST documentados en `/api-docs` (Swagger UI).
- __FS__: Maneja sesiones y media en `DATA_FOLDER`.
- __Supervisor__: `[program:connect-telegram-server]` → `node telegram/telegram.js` (user `root`).
- __Operación__: Carga sesiones al inicio, deduplicación de mensajes, manejo de tareas programadas en memoria.

### 🌐 Catálogo de Endpoints HTTP

Para el detalle completo revisar `routes/web.php` y `routes/api.php`. A continuación, un mapa de alto nivel de los grupos más relevantes:

#### Web (`routes/web.php`)
- __Kanban de órdenes__: 
  - `POST /production-orders/update-batch`, `/toggle-priority`, `/update-note`
  - `GET /customers/{customer}/order-organizer`, `/order-kanban/{process}`
  - `GET /kanban-data` (AJAX)
- __Clientes y Órdenes Originales__: `Route::resource('customers', ...)`, anidados `customers.original-orders.*` y utilidades `field-mapping-row`
- __Líneas de Producción__: `productionlines.*`, `.../productionlinesjson`, `liststats`
- __Procesos por Línea__: `productionlines/{production_line}/processes.*`
- __Sensores (SmartSensors)__: `smartsensors.*`, vistas `live`, `history`; detalle `sensors/{id}`
- __RFID__: `rfid.*`, categorías `rfid-categories.*`, colores `rfid.colors.*`, bloqueo `DELETE /rfid-blocked/destroy-all`
- __Turnos__: `shift-lists` CRUD, `shift-history/{productionLineId}`, `POST /shift-event`
- __Usuarios/Roles/Permisos__: `roles`, `users`, `permission`, `modules`, util `GET /roles/list`
- __Ajustes__: `settings` y POSTs específicos (`email`, `datetime`, `rfid`, `redis`, `upload-stats`, réplica DB)
- __Códigos de barras__: `barcodes.*`, impresoras `Route::resource('printers', ...)`
- __Modbus__: `modbuses.*`, `modbusesjson`, `queue-print`, `liststats`
- __OEE y Transformaciones__: `Route::resource('oee', ...)`, `sensor-transformations.*`
- __Monitor y Servidores__: `GET /server`, `GET /logs`
- __Puestos de Operario__: `worker-post.*`, `GET /scan-post`
- __SCADA/Producción__: `GET /scada-order`, `GET /production-order-kanban`
- __Varios__: `GET /debug`, `Auth::routes()`, `GET /` (dashboard)

#### API (`routes/api.php`)
- __Sistema/Servidor__: `/server-monitor-store`, `/register-server`, `/server-stats`, `/server-ips`, `restart|start|stop-supervisor`, `reboot`, `poweroff`, `restart-mysql`, `verne-update`, `app-update`, `update-env`, `check-db-connection`, `verify-and-sync-database`, `run-update`, `check-485-service`, `install-485-service`, `getSupervisorStatus`
- __Barcodes__: `/barcode`, `/barcode-info{,/POST}`, `/barcode-info-by-customer/{customerToken}`
- __Token/Producción__: `/production-lines/{customerToken}`, `/modbus-info/{token}`
- __Control de Peso__: `/control-weights/{token}/all`, throttled `/control-weight/{token}`, `GET /control_weight/{supplierOrderId}` consolidado
- __Modbus/SCADA__: `/modbuses`, `/tolvas/{id}/dosificacion/recalcular-automatico`, `POST /modbus/send|zero|tara|tara/reset|cancel`, `GET scada/{token}`, `PUT /modbus/{modbusId}/material`, grupo `scada/*` de material types
- __Sensores__: `/sensors{,/token}`, `POST /sensor-insert` (throttle alto)
- __Estadísticas de órdenes__: `/order-stats`, `/order-stats-all`
- __Producción (Kanban)__: `GET /kanban/orders`
- __Órdenes de producción API__: `/production-orders` (CRUD parcial), incidentes `production-orders/{order}/incidents`
- __Producción Topflow__: `reference-Topflow/*`, `topflow-production-order/*`
- __Disponibilidad y estado de líneas__: `GET /production-line/status/{token}`, `GET/POST /production-lines/{id}/availability`, `GET /production-lines/statuses/{customerId?}`
- __RFID__: `POST /rfid-insert`, `GET /rfid-history`, `GET /get-filters`
- __WhatsApp__: `POST /whatsapp-credentials`, `GET|POST /send-message`, `/whatsapp/logout`, `GET /whatsapp-qr{,/svg,/base64}`
- __Bluetooth Scanner__: `bluetooth/*` (`insert`, `history`, `filters`)
- __Operadores/Trabajadores__: `workers/*` (update/replace/list/show/reset-password/verify/destroy), `operators` y `operators/internal`, `workers/all-list/completed`, `scada/get-logins`
- __Listas de Producto__: `product-lists/*`, `product-list-selecteds/*`
- __TCP Publish__: `POST /publish-message`
- __Transferencias__: `POST /transfer-external-db`
- __Puestos de Operario (API)__: `operator-post/*` y `POST /operator-post/update-count`
- __Shift__: `/shift-event` (MQTT), `GET /shift-history{,/production-line/{id}}`, `GET /shift/statuses`, `GET /shift-lists`
- __IA Prompts__: `GET /ia-prompts{,/\{key\}}`
- __Barcode Scans__: `GET|POST /barcode-scans`
- __SCADA Orders__: `GET /scada-orders/{token}`, `POST /scada-orders/update`, `DELETE /scada-orders/delete`, `GET /scada-orders/{scadaOrderId}/lines`, `POST /scada-orders/process/update-used`
- __Zerotier/Red__: `GET|POST /ip-zerotier`
- __Cola de Impresión__: `GET|POST /queue-print`, `GET|POST /queue-print-list`
- __Avisos de Orden (Order Notice)__: `GET /order-notice/{token?}`, `POST /order-notice`, `POST /order-notice/store`
- __Modbus Ingest (MQTT)__: `POST /modbus-process-data-mqtt`
- __Eventos de Procesos de Turno__: `POST /shift-process-events`
- __Pedidos de Proveedor__: `POST /supplier-order/store`
- __RFID Readings (CRUD)__: `GET /rfid-readings`, `POST /rfid-readings`, `GET /rfid-readings/{id}`, `PUT /rfid-readings/{id}`, `DELETE /rfid-readings/{id}`
- __Exportaciones de Trabajadores__: `GET /workers-export/generate-excel`, `GET /workers-export/generate-pdf`, `GET /workers-export/send-email`, `GET /workers-export/send-assignment-list`, `GET /workers-export/complete-list`
- __Artículos de Órdenes de Producción__: `GET /production-orders/{id}/articles`

## 🧭 Mapa de funcionalidades (qué puede hacer la app)

- **Gestión de Producción con Kanban**: Organiza órdenes por líneas/estados, drag & drop con reglas, notas, artículos, incidencias, y prioridad. Rutas y UI en `routes/web.php` y vistas en `resources/views/customers/order-kanban.blade.php`.
- **Monitoreo OEE en tiempo real**: Cálculo de disponibilidad, rendimiento y calidad; integración con sensores/MQTT y Modbus. Backend en comandos `CalculateProductionMonitorOeev2.php` y endpoints en `routes/api.php`.
- **Sensores industriales**: Alta/gestión de sensores, transformación configurable de lecturas, publicación/ingesta MQTT. API en `SensorController`, servicio Node `node/sensor-transformer.js`.
- **Integración SCADA/Modbus**: Ingesta de pesaje/altura con filtros de repetición y variaciones mínimas; envío a API. Servicios en `node/client-modbus.js` y endpoints en `Api\Modbus*Controller`.
- **RFID (operarios/puestos)**: Lecturas en tiempo real, histórico/filtrado, asignaciones y “Master Reset”. UI pública en `public/live-rfid/` y `public/confeccion-puesto-listado/`; API `RfidReadingController`, `ProductListSelectedsController`.
- **Turnos (Shifts)**: Historial y estados, eventos por MQTT/API, publicación de cambios para producción. API `shift-history`, `shift/statuses`, `shift-event`, `shift-process-events`.
- **Órdenes desde APIs externas**: Ingesta por mapeos configurables (órdenes, procesos, artículos), validaciones y logs detallados. Comando `CheckOrdersFromApi.php`, mapeos en UI de clientes.
- **Gestión de incidencias**: Registro y seguimiento de incidencias ligadas a órdenes y líneas. Vistas en `resources/views/customers/production-order-incidents/*`, API dedicada.
- **Operadores/Trabajadores**: CRUD, reportes, exportación Excel/PDF, envío por email/listas de asignación. API `OperatorController`, `workers-export/*`.
- **Códigos de barras**: Generación y gestión. API `BarcodeController`, vistas `resources/views/barcodes/*`.
- **Cola de impresión**: Gestión de colas y listados de impresión vía API `StoreQueueController`.
- **Notificaciones**: WhatsApp (Baileys) y Telegram para alertas/comandos. Node `connect-whatsapp.js`, API `WhatsAppController` y servidor Telegram (`telegram/`).
- **Supervisión de sistema/host**: Healthcheck, monitor de servidor/hosts, IP Zerotier. API `ServerMonitor*`, `ZerotierIpBarcoderController`.
- **Exportaciones y reportes**: Workers PDF/Excel, listas completas y de asignación.

## 📚 Dónde está cada cosa (mapa de código)

- **Rutas**
  - `routes/api.php`: Endpoints REST (módulos de producción, sensores, RFID, Modbus/SCADA, workers, etc.).
  - `routes/web.php`: Rutas de interfaz (Kanban, organizador, administración).
- **Controladores (API)**: `app/Http/Controllers/Api/`
  - Producción: `ProductionOrderController`, `ProductionLineController`, `OrderStatsController`, `ProductionOrderArticlesController`, `ProductionOrderIncidentController`.
  - Sensores/SCADA: `SensorController`, `ModbusController`, `ModbusProcessController`, `Scada*Controller`.
  - RFID: `RfidReadingController`, `RfidDetailController`, `RfidErrorPointController`.
  - Operaciones: `OperatorController`, `OperatorPostController`, `WorkersExport*`, `StoreQueueController`, `OrderNoticeController`.
  - Utilidades: `Barcode*Controller`, `ProductList*Controller`, `IaPromptController`, `GetTokenController`, `ZerotierIpBarcoderController`, `ReferenceController`.
- **Comandos/Procesos**: `app/Console/Commands/*` (OEE, ingesta externa, sensores, shifts, bluetooth, TCP, limpieza, etc.).
- **Vistas clave**: `resources/views/customers/order-kanban.blade.php`, `resources/views/customers/order-organizer.blade.php`, OEE (`resources/views/monitor_oee/*`), incidencias, clientes y mapeos.
- **SPAs públicas**: `public/live-production/`, `public/live-rfid/`, `public/confeccion-puesto-listado/`.
- **Servicios Node**: `node/` (MQTT senders, sensor-transformer, client-mqtt-rfid, client-modbus, gateway de pruebas).
- **IA/Detección de anomalías**: `python/` (entrenamiento y detección para producción y turnos).
- **Supervisor**: archivos `.conf` en la raíz (orquestación de todos los procesos críticos).

## 🔄 Flujos clave

- **Ingesta de órdenes externas → Kanban**
  1) `orders:check` consulta APIs externas y aplica mapeos (órdenes, procesos, artículos).
  2) Se crean/actualizan órdenes y sus procesos/artículos.
  3) Kanban refleja estados y permite mover/gestionar incidencias/notas.
- **Monitoreo OEE**
  1) Sensores/Modbus publican por MQTT/HTTP.
  2) `calculate-monitor-oee` consolida actividad, tiempos y contadores.
  3) Métricas OEE y estados se exponen por API/UI.
- **RFID (operarios/puestos)**
  1) Lectores publican eventos a MQTT → gateway/API.
  2) API guarda historial/lecturas; vistas muestran en tiempo real y permiten asignaciones.
- **Turnos**
  1) `shift:check` y eventos `shift-event`/`shift-process-events` publican cambios.
  2) Historial/estado de turnos disponible por API/UI.
- **SCADA/Modbus (pesaje/altura)**
  1) `client-modbus.js` filtra/normaliza valores.
  2) Envía datos válidos a `/api/modbus-process-data-mqtt` u otros endpoints.
- **Incidencias**
  1) Operadores reportan; API registra y enlaza a órdenes/lineas.
  2) UI permite seguimiento y cierre.
- **Exportaciones/Reportes**
  1) Endpoints `workers-export/*` generan PDF/Excel y envían emails/listas.

## 🔐 Acceso y seguridad

- **Autenticación**: UI con login/registro/2FA (`resources/views/auth/*`).
- **Tokens del sistema**: Algunos endpoints requieren `TOKEN_SYSTEM` (ver `.env`).
- **Permisos/Roles**: Gestión de usuarios/roles vía UI de administración (Laravel estándar + personalizaciones del proyecto).
- **Entornos y credenciales**: Variables `.env` para DB, MQTT, brokers, gateways y servicios externos.

## 🚀 Quickstart (cómo empezar)

- **Configurar entorno**
  - Copia `.env.example` a `.env` y ajusta: DB (`DB_*`), URL (`APP_URL`), zona horaria (`APP_TIMEZONE`), MQTT (`MQTT_*`), token (`TOKEN_SYSTEM`).
- **Instalar dependencias**
  - PHP: `composer install` → `php artisan key:generate`
  - Migraciones/seeders: `php artisan migrate --seed`
- **Arrancar procesos**
  - Web (desarrollo): `php artisan serve` o configurar Apache/Nginx apuntando a `public/`.
  - Servicios en background: habilitar `.conf` de Supervisor en la raíz (MQTT senders, OEE, Modbus, RFID, WhatsApp, etc.).
- **Verificar**
  - Revisar logs en `storage/logs/`.
  - Probar endpoints clave en `routes/api.php` (ver sección “API”).
  - Abrir Kanban y SPAs públicas (ver “URLs útiles”).

## 🔗 URLs útiles / Navegación

- **Autenticación/Panel**: `/login`, `/register`.
- **Kanban de producción**: acceso desde el panel web (vista `resources/views/customers/order-kanban.blade.php`).
- **Organizador de órdenes**: acceso desde el panel (vista `resources/views/customers/order-organizer.blade.php`).
- **SPAs públicas** (`public/`):
  - Monitoreo Producción: `/live-production/machine.html`
  - Monitoreo RFID: `/live-rfid/index.html`
  - Confección/Asignación Puestos: `/confeccion-puesto-listado/index.html`
- **Documentación de API**: ver sección “API (routes/api.php)” en este README.

## 🛡️ Operación y mantenimiento

- **Logs**: `storage/logs/` (cada servicio tiene su archivo; ver `.conf` de Supervisor en la raíz para nombres y rutas completas).
- **Salud del sistema**: Comandos Artisan y endpoints de sistema/host monitor.
- **Backups y SFTP**: Variables `.env` (ver sección de configuración). Programe backups y verifique credenciales SFTP.
- **Limpieza y retención**: `CLEAR_DB_DAY` y comando `clear:old-records` (ver `laravel-clear-db.conf`).
- **Servicios críticos**: OEE (`calculate-monitor-oee`), MQTT senders (`node/sender-mqtt-server*.js`), Modbus (`node/client-modbus.js`), RFID gateway (`node/mqtt-rfid-to-api.js`), WhatsApp (`connect-whatsapp.js`).
- **Tareas periódicas**: `orders:check`, `shift:check`, `bluetooth:check-exit`, `production:update-accumulated-times` (ver archivos `.conf`).

### 📦 Copias de seguridad automáticas

- **Base de datos (diario)**: `php artisan db:replicate-nightly` — crea un volcado de la BD primaria y reemplaza la secundaria (auto-detección mysql/mariadb). Integrar en Supervisor/cron.
- **Script de apoyo**: `clean_and_backup.sh` en la raíz — ejemplo de limpieza y respaldo combinados. Ajustar rutas/retención.
- **Configuración**: `config/backup.php` y variables `.env` relacionadas a almacenamiento/credenciales SFTP si aplica.
- **Retención**: Alinear con `CLEAR_DB_DAY` y políticas internas.
- **Restauración**: Mantener procedimientos documentados y probados para restore desde dumps recientes.

#### Programación de backups (ejemplos)

- **cron (02:30 diario)**
  ```bash
  30 2 * * * cd /var/www/html && /usr/bin/php artisan db:replicate-nightly >> storage/logs/backup.log 2>&1
  ```
- **systemd timer**
  - `/etc/systemd/system/sensorica-backup.service`
    ```ini
    [Unit]
    Description=Backup diario de base de datos Sensorica
    After=network.target

    [Service]
    Type=oneshot
    WorkingDirectory=/var/www/html
    ExecStart=/usr/bin/php artisan db:replicate-nightly
    StandardOutput=append:/var/www/html/storage/logs/backup.log
    StandardError=append:/var/www/html/storage/logs/backup.log
    ```
  - `/etc/systemd/system/sensorica-backup.timer`
    ```ini
    [Unit]
    Description=Programador diario de backup Sensorica

    [Timer]
    OnCalendar=*-*-* 02:30:00
    Persistent=true

    [Install]
    WantedBy=timers.target
    ```
  - Activar: `systemctl enable --now sensorica-backup.timer`

### 🔒 Seguridad operacional

- **Entorno**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` correcto y HTTPS habilitado en el proxy/reverse.
- **Credenciales**: `.env` con permisos restringidos (600) y fuera del control de versiones; rotación periódica.
- **RBAC**: Revisar roles/permisos en la UI de admin; mínimos privilegios.
- **Tokens**: `TOKEN_SYSTEM` y claves de terceros (WhatsApp/Telegram/SFTP) almacenadas solo en `.env`.
- **Red**: Limitar puertos de brokers MQTT/DB a redes internas; usar autenticación en MQTT.
- **Logs**: Vigilar `storage/logs/`; evitar datos sensibles en logs; rotación.
- **Jobs/Servicios**: Supervisados por Supervisor con `Restart=always`; ejecutar como usuarios de servicio cuando sea posible.
- **Backups**: Cifrar/firmar copias; transferir por SFTP/SSH; pruebas de restore periódicas.
- **Actualizaciones**: Mantener dependencias (composer/npm) y parches de SO al día.

### 🛠️ Comandos Artisan (Supervisor y mantenimiento)

Extraídos de `app/Console/Commands/*`:

- `shift:check` — Check shift list and publish MQTT message if current time matches start time
- `bluetooth:read` — Read data from Bluetooth API and publish to MQTT
- `bluetooth:check-exit` — Verifica si los dispositivos Bluetooth han salido de la zona de detección
- `reset:weekly-counts` — Reset count_week_0 and count_week_1 to 0 every Monday at 00:00
- `tcp:client` — Connect to multiple TCP servers and read messages continuously
- `modbus:read {group}` — Read data from Modbus API and publish to MQTT for a specific group
- `hostmonitor:check` — Envía un correo de alerta si un host no tiene registros en host_monitors en los últimos 3 minutos
- `mqtt:subscribe-local` — Subscribe to MQTT topics and update order notices
- `operator-post:finalize` — Cierra y gestiona los registros de operadores según el inicio y fin de turno.
- `mqtt:subscribe-local-ordermac` — Subscribe to MQTT topics and update production orders
- `tcp:client-local` — Connect to TCP server using .env values and log messages in a loop
- `production:calculate-monitor-oee-vieja` — Calcular y gestionar el monitoreo de la producción (versión previa)
- `orders:check` — Verifica pedidos desde la API y los compara con la base de datos local
- `db:replicate-nightly` — Dumps the primary database and replaces the secondary (mysql/mariadb autodetect)
- `clear:old-records` — Clear old records from varias tablas según CLEAR_DB_DAY
- `production:calculate-monitor-oee` — Calcular y gestionar el monitoreo de la producción (OEE v2)
- `sensors:read` — Read data from Sensors API and publish to MQTT
- `rfid:read` — Read data from RFID API and publish to MQTT
- `modbus:read-ant` — Read data from Modbus API and publish to MQTT
- `monitor:connections` — Monitor MQTT topics for connections and update their status in the database
- `mqtt:subscribe` — Subscribe to MQTT topics and update order notices
- `whatsapp:connect` — Conecta a WhatsApp usando Baileys sin generar QR
- `production:calculate-production-downtime` — Calculate production downtime and publish MQTT
- `modbus:read-backup` — Read data from Modbus API and publish to MQTT
- `mqtt:shiftsubscribe` — Subscribe to MQTT topics and update shift control information from sensors
- `production:update-accumulated-times {line_id?}` — Actualiza tiempos acumulados de órdenes activas (opcional por línea)
- `production:calculate-optimal-time` — Calculate the optimal production time per product from sensor data
- `orders:list-stock` — Busca órdenes en stock y procesa siguiente tarea pendiente por grupo
- `mqtt:publish-order-stats` — Extrae barcodes/order_stats y publica JSON por MQTT cada 1s

### 🧩 Variables de entorno (.env) requeridas

Agrupadas por subsistema. Ver también `resources/views/settings/*.blade.php` para formularios de administración que dependen de estas claves.

- __Core/Laravel__
  - `APP_URL`, `ASSET_URL`
  - `APP_TIMEZONE`, `TIMEZONE` (zona horaria)
  - `SITE_RTL` (on/off)

- __Base de Datos__
  - `DB_CONNECTION` (mysql|pgsql|sqlsrv)
  - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

- __Correo__
  - `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

- __MQTT (principal y Sensorica)__
  - Broker genérico: `MQTT_SERVER`, `MQTT_PORT`
  - Broker Sensorica: `MQTT_SENSORICA_SERVER`, `MQTT_SENSORICA_PORT`
  - Backup: `MQTT_SENSORICA_SERVER_BACKUP`, `MQTT_SENSORICA_PORT_BACKUP`
  - Credenciales/opciones: `MQTT_USERNAME`, `MQTT_PASSWORD`, `MQTT_TOPIC`
  - Tiempos de envío por lotes (senders): `MQTT_SERVER1_CHECK_INTERVAL_MS`, `MQTT_SERVER2_CHECK_INTERVAL_MS`, `MQTT_CHECK_INTERVAL_MS`

- __RFID__
  - Panel/config lector: `RFID_READER_IP`, `RFID_READER_PORT`
  - Monitor externo (link en `server/index.blade.php`): `RFID_MONITOR_URL`

- __Gateway MQTT-RFID (Express/WebSocket)__
  - `MQTT_GATEWAY_PORT`
  - HTTPS opcional: `USE_HTTPS` (true/false), `SSL_KEY_PATH`, `SSL_CERT_PATH`
  - Puerto alternativo servidor lector: `NODE_RFID_PORT`

- __WhatsApp (Baileys)__
  - Usa callbacks HTTP locales; puede requerir `PORT` si se expone servidor HTTP local del script.

- __Telegram API server__
  - `API_ID`, `API_HASH`, `PORT`
  - `API_EXTERNAL`/`API_EXTERNAL_*` (si se usa reverse proxy o URLs públicas)
  - `DATA_FOLDER` (almacenamiento de sesiones/media)
  - `CALLBACK_BASE` (URL base para callbacks webhooks)

- __Backups y SFTP__
  - `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_ARCHIVE_ENCRYPTION`
  - `SFTP_HOST`, `SFTP_PORT`, `SFTP_USERNAME`, `SFTP_PASSWORD`, `SFTP_ROOT`

- __Producción/OEE/limpieza__
  - `SHIFT_TIME` (HH:MM:SS inicio de turno)
  - `PRODUCTION_MIN_TIME`, `PRODUCTION_MAX_TIME`, `PRODUCTION_MIN_TIME_WEIGHT`
  - `CLEAR_DB_DAY` (retención de registros en días)

- __Sistema/Operaciones__
  - `TOKEN_SYSTEM` (token de autenticación para endpoints de sistema)
  - `USE_CURL` (true/false), `EXTERNAL_API_QUEUE_TYPE` (get|post|put|delete)
  - Entorno runtime: `APP_ENV` (Node gateway), `NODE_ENV` (scripts Node)
  - Base URL backend para clientes Node: `LOCAL_SERVER` (ej. https://mi-backend)

Notas:
- Algunos servicios Node.js leen credenciales DB vía `.env` de Laravel (usado por scripts con `mysql2`). Asegura consistencia.
- Si se usa HTTPS propio, `https.Agent({ rejectUnauthorized:false })` en `client-mqtt-sensors.js` tolera TLS autofirmado.

## 🧰 Otros comandos del sistema (Artisan)

Listado de comandos disponibles en `app/Console/Commands/` con su `signature` y propósito principal:

- `production:calculate-optimal-time` — Calculate the optimal production time for each product based on sensor data (`CalculateOptimalProductionTime.php`).
- `production:calculate-production-downtime` — Calcula tiempos de parada y gestiona contadores por turno; envía mensajes MQTT (`CalculateProductionDowntime.php`).
- `production:calculate-monitor-oee` — Calcula/gestiona monitoreo OEE según reglas de `monitor_oee` (v2) (`CalculateProductionMonitorOeev2.php`).
- `production:calculate-monitor-oee-vieja` — Versión previa del cálculo OEE (`CalculateProductionMonitorOee.php`).
- `sensors:read` — Lee datos de Sensores y publica por MQTT (`ReadSensors.php`).
- `modbus:read-ant` — Lee datos Modbus y publica por MQTT (`ReadModbus.php`).
- `modbus:read-backup` — Lectura Modbus (backup) y publicación MQTT (`ReadModbuBackup.php`).
- `modbus:read {group}` — Lectura Modbus por grupo y publicación MQTT (`ReadModbusGroup.php`).
- `mqtt:subscribe` — Suscriptor MQTT y actualización de avisos de órdenes (`MqttSubscriber.php`).
- `mqtt:subscribe-local` — Suscriptor MQTT local para avisos de órdenes (`MqttSubscriberLocal.php`).
- `mqtt:subscribe-local-ordermac` — Suscriptor MQTT local para órdenes (modo OrderMac) (`MqttSubscriberLocalMac.php`).
- `mqtt:shiftsubscribe` — Suscripción MQTT para control de turnos desde sensores (`MqttShiftSubscriber.php`).
- `mqtt:publish-order-stats` — Publica cada 1s estadísticas de órdenes vía MQTT (`PublishOrderStatsCommand.php`).
- `rfid:read` — Lee RFID y publica por MQTT (`ReadRfidReadings.php`).
- `bluetooth:read` — Lee Bluetooth API y publica por MQTT (`ReadBluetoothReadings.php`).
- `bluetooth:check-exit` — Verifica salidas de zona de dispositivos Bluetooth (`CheckBluetoothExit.php`).
- `orders:check` — Verifica pedidos desde API externa y sincroniza con DB (`CheckOrdersFromApi.php`).
- `orders:list-stock` — Busca órdenes en stock y procesa la siguiente tarea pendiente por grupo (`ListStockOrdersCommand.php`).
- `operator-post:finalize` — Cierra/gestiona registros de operadores según el inicio y fin de turno (`FinalizeOperatorPosts.php`).
- `hostmonitor:check` — Alerta por ausencia de registros recientes en `host_monitors` (`CheckHostMonitor.php`).
- `monitor:connections` — Monitoriza conexiones (MQTT topics) y actualiza estado en DB (`MonitorConnections.php`).
- `tcp:client` — Cliente TCP multiproceso para leer mensajes continuamente (`TcpClient.php`).
- `tcp:client-local` — Cliente TCP con valores de `.env` y logging en bucle (`TcpClientLocal.php`).
- `db:replicate-nightly` — Dump de DB primaria y reemplazo de secundaria (auto-detección mysql/mariadb) (`ReplicateDatabaseNightly.php`).
- `clear:old-records` — Limpia registros antiguos según `CLEAR_DB_DAY` (`ClearOldRecords.php`).
- `reset:weekly-counts` — Resetea contadores semanales cada lunes 00:00 (`ResetWeeklyCounts.php`).
- `shift:check` — Verifica lista de turnos y publica mensaje MQTT al inicio (`CheckShiftList.php`).
- `whatsapp:connect` — Conexión a WhatsApp via Baileys sin generar QR (`ConnectWhatsApp.php`).
- `production:update-accumulated-times {line_id?}` — Actualiza tiempos acumulados de órdenes activas (opcional por línea) (`UpdateAccumulatedTimes.php`).

Notas:
- Los comandos están registrados en `app/Console/Kernel.php` y/o autocargados desde `app/Console/Commands/`.
- Algunos `.conf` de Supervisor ejecutan estos comandos en bucle (con `sleep`) o con reinicio automático.

## 🧩 Archivos Supervisor (.conf)

Configuraciones en la raíz del proyecto que mapean procesos gestionados por Supervisor. Para cada archivo se indica el comando ejecutado y rutas de logs.

- `laravel-calculate-optimal-production-time.conf`
  - command: `php /var/www/html/artisan production:calculate-optimal-time`
  - logs: `storage/logs/calculate_optimal_time.out.log`, `storage/logs/calculate_optimal_time.err.log`

- `laravel-calculate-production-downtime.conf`
  - command: `php /var/www/html/artisan production:calculate-production-downtime`
  - logs: `storage/logs/calculate-production-downtime.out.log`, `storage/logs/calculate-production-downtime.err.log`

- `laravel-monitor-oee.conf`
  - command: `php /var/www/html/artisan production:calculate-monitor-oee`
  - logs: `storage/logs/calculate-monitor-oee.out.log` (stderr redirigido)

- `laravel-mqtt-subscriber.conf.back`
  - command: `php /var/www/html/artisan mqtt:subscribe`
  - logs: `storage/logs/mqtt-subscribe.log`

- `laravel-mqtt-subscriber-local.conf`
  - command: `php /var/www/html/artisan mqtt:subscribe-local`
  - logs: `storage/logs/subscribe-local.out.log` (si configurado), `...err.log` (si configurado)

- `laravel-mqtt-subscriber-local-ordermac.conf`
  - command: `php /var/www/html/artisan mqtt:subscribe-local-ordermac`
  - logs: `storage/logs/subscribe-local-ordermac.out.log`, `storage/logs/subscribe-local-ordermac.err.log`

- `laravel-mqtt-shift-subscriber.conf`
  - command: `php /var/www/html/artisan mqtt:shiftsubscribe`
  - logs: `storage/logs/laravel-shift-subscriber.out.log` (si configurado)

- `laravel-read-sensors.conf`
  - command: `node /var/www/html/node/client-mqtt-sensors.js`
  - logs: `storage/logs/laravel-read-sensors.out.log`, `storage/logs/laravel-read-sensors.err.log`

- `laravel-sensor-transformers.conf`
  - command: `node /var/www/html/node/sensor-transformer.js`
  - logs: `storage/logs/laravel-sensor-transformers.out.log`, `storage/logs/laravel-sensor-transformers.err.log`

- `laravel-read-rfid.conf`
  - command: `node /var/www/html/node/client-mqtt-rfid.js`
  - logs: `storage/logs/laravel-read-rfid.out.log`, `storage/logs/laravel-read-rfid.err.log`

- `laravel-read-bluetooth.conf`
  - command: `php /var/www/html/artisan bluetooth:read`
  - logs: `storage/logs/laravel-read-bluetooth.out.log`, `storage/logs/laravel-read-bluetooth.err.log`

- `laravel-control-antena-rfid.conf`
  - command: `node /var/www/html/node/config-rfid.js`
  - logs: `storage/logs/laravel-config-rfid-antena.out.log`, `storage/logs/laravel-config-rfid-antena.err.log`

- `laravel-mqtt-rfid-to-api.conf`
  - command: `node /var/www/html/node/mqtt-rfid-to-api.js`
  - logs: `storage/logs/laravel-mqtt-rfid-to-api.out.log` (si configurado)

- `laravel-telegram-server.conf`
  - command: `node /var/www/html/telegram/telegram.js`
  - logs: `storage/logs/connect-telegram.out.log`, `storage/logs/connect-telegram.err.log`

- `laravel-connect-whatsapp.conf`
  - command: `node /var/www/html/node/connect-whatsapp.js`
  - logs: `storage/logs/connect-whatsapp.out.log`, `storage/logs/connect-whatsapp.err.log`

- `laravel-modbus-subscriber.conf`
  - command: `node /var/www/html/node/client-modbus.js`
  - logs: `storage/logs/laravel-modbus-subscriber.log`

- `laravel-modbus-web-8001.conf`
  - command: `python3 /var/www/html/modbus-web-8001.py`
  - logs: `storage/logs/modbus-web.log`

- `laravel-tcp-client.conf`
  - command: `php /var/www/html/artisan tcp:client`
  - logs: `storage/logs/laravel-tcp-client.out.log` (si configurado)

- `laravel-tcp-client-local.conf`
  - command: `php /var/www/html/artisan tcp:client-local`
  - logs: `storage/logs/laravel-tcp-client-local.out.log`, `storage/logs/laravel-tcp-client-local.err.log`

- `laravel-tcp-server.conf`
  - command: `python3 /var/www/html/tcp-server.py`
  - logs: `storage/logs/tcp-server.out.log`, `storage/logs/tcp-server.err.log`

- `laravel-auto-finish-operator-post.conf`
  - command: `php /var/www/html/artisan operator-post:finalize`
  - logs: `storage/logs/operator-post:finalize.out.log`, `storage/logs/operator-post:finalize.err.log`

- `laravel-clear-db.conf`
  - command: `php /var/www/html/artisan clear:old-records`
  - logs: `storage/logs/clear-old-db.out.log`, `storage/logs/clear-old-db.err.log`

- `laravel-check-bluetooth.conf`
  - command: `php /var/www/html/artisan bluetooth:check-exit`
  - logs: `storage/logs/laravel-bluetooth-check-exit.out.log`, `storage/logs/laravel-bluetooth-check-exit.err.log`

- `laravel-shift-list.conf`
  - command: `php /var/www/html/artisan shift:check`
  - logs: `storage/logs/laravel-shift-list.out.log`, `storage/logs/laravel-shift-list.err.log`

- `laravel-orders-check.conf`
  - command: `/bin/sh -c 'while true; do php /var/www/html/artisan orders:check; sleep 1800; done'`
  - logs: `storage/logs/laravel-orders-check.out.log` (según conf), `...err.log`

- `laravel-created-production-orders.conf`
  - command: `/bin/sh -c 'while true; do php /var/www/html/artisan orders:list-stock; sleep 60; done'`
  - logs: `storage/logs/laravel-created-production-orders.out.log`, `storage/logs/laravel-created-production-orders.err.log`

- `laravel-production-updated-accumulated-times.conf.conf`
  - command: `/bin/sh -c 'while true; do php /var/www/html/artisan production:update-accumulated-times; sleep 60; done'`
  - logs: `storage/logs/laravel-production-updated-accumulated-times.out.log` (según conf), `...err.log`

- `laravel-server-check-host-monitor.conf`
  - command: `php /var/www/html/artisan hostmonitor:check`
  - logs: `storage/logs/check_host_monitor.out.log`, `storage/logs/check_host_monitor.err.log`

- `laravel-monitor-server.conf`
  - command: `python3 /var/www/html/servermonitor.py`
  - logs: `storage/logs/servermonitor.out.log` (según conf), `...err.log`

- `laravel-mqtt_send_server1.conf` / `laravel-mqtt_send_server2.conf.back`
  - command: `node /var/www/html/node/sender-mqtt-server1.js` / `sender-mqtt-server2.js`
  - logs: `storage/logs/mqtt-sendserver1.log` / `storage/logs/mqtt-sendserver2.log`

- `laravel-production-monitor-ia.conf.back` / `laravel-shift-monitor-ia.conf.back`
  - command: `python3 -u python/detectar_anomalias_produccion.py` / `python3 python/detectar_anomalias_shift.py`
  - logs: `storage/logs/IA-production.out.log`, `storage/logs/IA-production.err.log` / `storage/logs/IA-Shift.out.log`, `storage/logs/IA-Shift.err.log`

Notas:
- Todas las rutas de logs son relativas a `storage/logs/` en este README por brevedad; en los `.conf` se usan rutas absolutas.
- Muchos programas especifican `redirect_stderr=true`, en cuyo caso sólo habrá `stdout_logfile`.
- Ajuste `numprocs`, `startretries`, `user` y otras opciones según su entorno.

## 🏗️ Infraestructura y despliegue

### Base de datos: Percona Server for MySQL

- Migración a Percona por estabilidad y rendimiento superiores manteniendo compatibilidad MySQL.
- Beneficios: mejoras en InnoDB, diagnósticos avanzados, `Percona Toolkit/Backup`, mejor manejo de alta concurrencia y recuperación ante fallos.
- Laravel continúa usando `DB_CONNECTION=mysql`; no se requieren cambios de código. Ajustar `my.cnf` según carga.

### Servidor web: Caddy

- Caddy reemplaza Nginx por su HTTPS automático, HTTP/2/3, y configuración simple.
- Beneficios: renovación automática de certificados, reverse proxy integrado, headers de seguridad por defecto, menor complejidad operativa.
- Ejemplo mínimo de Caddyfile:
  ```caddyfile
  ejemplo.midominio.com {
    encode zstd gzip
    root * /var/www/html/public
    php_fastcgi unix//run/php/php-fpm.sock
    file_server
    header {
      Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
      X-Content-Type-Options nosniff
      X-Frame-Options DENY
      Referrer-Policy no-referrer-when-downgrade
    }
  }
  ```

### Red y acceso seguro: ZeroTier + Cloudflare Tunnels

- Acceso sin abrir puertos en el firewall. La app es accesible en Internet mediante túneles salientes y redes P2P.
- ZeroTier: crea una red virtual P2P cifrada entre nodos (planta, servidores, operadores). Beneficios P2P: NAT traversal, baja latencia, cifrado extremo a extremo, microsegmentación y control de membresía.
- Cloudflare Tunnels: expone dominios públicos mediante un túnel saliente (origin cloaked). Beneficios: no hay puertos entrantes, WAF/CDN, Access/SSO opcional, reglas de origen restringido.
- Patrón recomendado: acceso interno por ZeroTier (IPs privadas/ZT) y acceso externo controlado vía Cloudflare (dominio público), ambos sin exposición directa.
  - Medidas de protección:
  - ACLs/Members en ZeroTier; rotación de tokens; restringir auto-join.
  - Cloudflare Access/SSO, IP allowlists, Origin Rules; mínimo de orígenes permitidos.
  - Cifrado TLS extremo a extremo (Caddy) y seguridad de aplicación (roles, tokens, rate limits).
  - Auditoría: métricas y logs de túneles, health-checks, alertas.

#### ¿Cómo salimos a Internet sin abrir puertos ni IP fija?

- **Todo es saliente**: El servidor inicia conexiones salientes (HTTPS/websocket) hacia Cloudflare y ZeroTier.
- **NAT traversal**: ZeroTier establece enlaces P2P entre nodos aun detrás de NAT/CG-NAT; si no es posible, relé cifrado.
- **Dominio público sin exposición**: Cloudflare Tunnel publica `https://tu-dominio` pero el origen permanece oculto (origin cloaked).
- **DHCP/Redes cambiantes**: Funciona en cualquier LAN con DHCP; no requiere IP pública ni estática. Si cambia la IP local, el túnel se reestablece automáticamente.
- **Seguridad**: Tráfico cifrado extremo a extremo (ZeroTier) y TLS en el túnel (Cloudflare) + WAF/CDN/Access.

```
[Cliente] ⇄ Internet ⇄ [Cloudflare Edge]
                    ⇵
                 (Túnel)
                    ⇵
            [Servidor en planta]
                 ⇵
            [ZeroTier P2P]
                 ⇵
      [Otros nodos internos]
```

#### Escenarios típicos

- **Planta con ISP residencial (sin IP fija / CG-NAT)**: El servicio funciona igual; no se abren puertos, dominio público operativo.
- **Multipunto (planta ↔ sucursales ↔ casa del gerente)**: Todos los nodos en la red ZeroTier con IPs privadas virtuales; acceso estable y cifrado.
- **Soporte remoto**: Proveer acceso temporal a técnicos vía ZeroTier Members con expiración y políticas ACL.
- **Exposición selectiva**: Panel interno solo por ZeroTier; APIs públicas específicas por Cloudflare con Access/SSO.

#### Buenas prácticas rápidas

- Usar ZeroTier para tráfico interno (DB, MQTT, panel admin) y Cloudflare solo para endpoints públicos necesarios.
- Habilitar Cloudflare Access (SSO) en rutas sensibles; limitar orígenes con Origin Rules.
- Segmentar por redes ZeroTier por cliente/línea; aplicar ACLs de mínimo privilegio.
- Rotar tokens/identidades de ZeroTier y credenciales de `cloudflared`; registrar y auditar accesos.
- Mantener Caddy con TLS y headers de seguridad; deshabilitar HTTP sin TLS.

### 🔧 Sistema de Monitoreo de Cloudflare Tunnel

Sensorica incluye un sistema automático de monitoreo y recuperación para el túnel de Cloudflare que garantiza la disponibilidad continua del acceso remoto al sistema.

#### Características principales

- **Monitoreo Automático**: Verificación cada 30 segundos del estado del túnel Cloudflare
- **Recuperación Automática**: Reinicio automático del servicio en caso de fallo
- **Logs Detallados**: Registro completo de todas las operaciones de monitoreo
- **Integración con Systemd**: Gestión nativa del sistema operativo
- **Rotación de Logs**: Gestión automática del tamaño de archivos de log
- **Configuración Automática**: Integración completa con el script de actualización

#### Componentes del sistema

**Script de Monitoreo**: `/var/www/html/scripts/cloudflare-tunnel-monitor.sh`

Funcionalidades del script:
- `monitor`: Verificación y reinicio automático (modo por defecto)
- `status`: Mostrar estado actual del túnel
- `restart`: Forzar reinicio del túnel
- `enable`: Habilitar el servicio si no está activo

**Servicio Systemd**: `cloudflare-tunnel-monitor.service`
- Ejecuta el script de monitoreo como servicio del sistema
- Configurado para ejecutarse con permisos de root
- Logs integrados con journald

**Timer Systemd**: `cloudflare-tunnel-monitor.timer`
- Ejecuta el monitoreo cada 30 segundos
- Configuración de alta precisión (AccuracySec=1sec)
- Inicio automático después del arranque del sistema

#### Verificaciones realizadas

1. **Estado del Servicio**: Verifica que `cloudflared.service` esté activo
2. **Proceso en Ejecución**: Confirma que el proceso cloudflared esté ejecutándose
3. **Habilitación del Servicio**: Asegura que el servicio esté habilitado para arranque automático
4. **Conectividad**: Verificación básica de que el proceso responde

#### Logs y monitoreo

**Archivo de Logs**: `/var/log/cloudflare-tunnel-monitor.log`
- Registro de todas las verificaciones y acciones
- Rotación automática cuando supera 10MB
- Formato con timestamp y nivel de log

**Logs del Sistema**: `journalctl -u cloudflare-tunnel-monitor.service`
- Integración con el sistema de logs del sistema operativo
- Acceso a logs históricos y en tiempo real

#### Comandos útiles

```bash
# Ver estado del timer
systemctl status cloudflare-tunnel-monitor.timer

# Ver logs del monitoreo
tail -f /var/log/cloudflare-tunnel-monitor.log

# Ver logs del sistema
journalctl -u cloudflare-tunnel-monitor.service -f

# Ejecutar verificación manual
/var/www/html/scripts/cloudflare-tunnel-monitor.sh status

# Forzar reinicio del túnel
/var/www/html/scripts/cloudflare-tunnel-monitor.sh restart
```

#### Configuración automática

El sistema se configura automáticamente durante la ejecución del script `update.sh`:

1. **Verificación de Archivos**: Comprueba que el script de monitoreo existe
2. **Permisos**: Asigna permisos de ejecución al script
3. **Habilitación del Timer**: Habilita el timer systemd si no está activo
4. **Inicio del Servicio**: Inicia el timer si no está ejecutándose
5. **Verificación Final**: Confirma que el sistema está funcionando correctamente

#### Integración con sudoers

El script `update.sh` configura automáticamente los permisos necesarios en sudoers para que el usuario `www-data` pueda ejecutar comandos relacionados con Cloudflare:

```bash
# Comandos permitidos para www-data sin contraseña
/bin/systemctl restart cloudflared.service
/bin/systemctl start cloudflared.service
/bin/systemctl stop cloudflared.service
/bin/systemctl enable cloudflared.service
/bin/systemctl is-active cloudflared.service
/bin/systemctl enable cloudflare-tunnel-monitor.timer
/bin/systemctl start cloudflare-tunnel-monitor.timer
/bin/systemctl stop cloudflare-tunnel-monitor.timer
/bin/systemctl is-active cloudflare-tunnel-monitor.timer
/var/www/html/scripts/cloudflare-tunnel-monitor.sh
```

#### Solución de problemas

**El timer no se ejecuta**:
```bash
# Verificar estado
systemctl status cloudflare-tunnel-monitor.timer

# Recargar configuración
sudo systemctl daemon-reload
sudo systemctl enable cloudflare-tunnel-monitor.timer
sudo systemctl start cloudflare-tunnel-monitor.timer
```

**Logs no se generan**:
```bash
# Verificar permisos del directorio de logs
sudo mkdir -p /var/log
sudo touch /var/log/cloudflare-tunnel-monitor.log
sudo chmod 644 /var/log/cloudflare-tunnel-monitor.log
```

**El túnel no se reinicia automáticamente**:
```bash
# Verificar permisos en sudoers
sudo visudo
# Buscar las líneas relacionadas con www-data y cloudflared

# Probar reinicio manual
sudo /var/www/html/scripts/cloudflare-tunnel-monitor.sh restart
```

Este sistema garantiza que el túnel de Cloudflare permanezca siempre disponible, proporcionando acceso continuo y confiable al sistema Sensorica desde ubicaciones remotas.

## 🚚 Sistema de Gestión de Rutas y Entregas

### Descripción General

El sistema de rutas permite planificar, gestionar y ejecutar entregas a clientes de forma eficiente. Incluye funcionalidades de asignación de vehículos, conductores, y seguimiento en tiempo real del estado de los pedidos.

### Estados de Pedidos en el Sistema de Rutas

El sistema maneja dos tipos de pedidos pendientes de entrega:

#### 1. Pedidos Finalizados (Ready)
- **Condición**: `finished_at IS NOT NULL` AND `actual_delivery_date IS NULL`
- **Descripción**: Pedidos que han completado todos sus procesos de producción y están listos para ser entregados.
- **Badge en UI**: Verde con texto "Ready" / "Listo"
- **Criterio**: Se usa `finished_at` como indicador definitivo de que el pedido está completo, independientemente del estado de stock individual de artículos.

#### 2. Pedidos Pendientes de Finalizar (Pending Completion)
- **Condición**: `finished_at IS NULL` AND `delivery_date IS NOT NULL` AND `actual_delivery_date IS NULL`
- **Descripción**: Pedidos que tienen una fecha de entrega programada pero aún no han completado su producción.
- **Badge en UI**: 
  - Amarillo con texto "Pending completion" / "Pendiente de finalizar" si la fecha de entrega no ha pasado
  - Rojo con texto "Pending completion" / "Pendiente de finalizar" si la fecha de entrega ya pasó (`is_overdue = true`)
- **Propósito**: Permite a los planificadores visualizar pedidos que deben priorizarse para cumplir con fechas de entrega comprometidas.

### Funcionalidades Principales

#### Planificador de Rutas (`/customers/{customer}/routes`)
- **Vista semanal**: Organización por días y rutas con navegación entre semanas
- **KPIs en tiempo real**: Vehículos asignados, clientes planificados, rutas activas, pedidos en camiones
- **Drag & Drop**: Arrastrar clientes a vehículos para asignar entregas
- **Búsqueda global**: Buscar pedidos específicos en todos los vehículos
- **Acciones por vehículo**:
  - Copiar asignaciones de semana anterior
  - Imprimir hoja de ruta (PDF)
  - Exportar a Excel
  - Asignar/cambiar conductor
  - Eliminar vehículo
- **Acciones por ruta completa**:
  - Copiar toda la ruta (todos los vehículos) de semana anterior
  - Imprimir ruta completa agrupada por vehículos
  - Exportar ruta completa a Excel

#### Modal de Detalles de Cliente (Doble Click)
- **Activación**: Doble click sobre cualquier cliente (disponible o asignado)
- **Información mostrada**:
  - Datos del cliente (nombre, dirección, teléfono)
  - Lista de pedidos pendientes con estado visual
  - Procesos de cada pedido con información de stock
  - Artículos por proceso con códigos y descripciones
- **Acciones disponibles**:
  - **Imprimir**: Abre ventana de impresión con formato optimizado
  - **Export PDF**: Genera PDF del contenido usando jsPDF + html2canvas

#### Vista de Transportista (`/my-deliveries`)
- **Diseño móvil-friendly**: Optimizado para tablets y smartphones
- **KPIs del día**: Total paradas, entregados, pendientes, vehículos asignados
- **Selector de fecha**: Ver entregas de otros días
- **Tarjetas por cliente**:
  - Información de contacto con botón de llamada directa
  - Lista de pedidos activos
  - Botón "Entregar" por pedido
- **Actualización en tiempo real**: Marca `actual_delivery_date` al confirmar entrega

### Modelos y Relaciones

#### `CustomerClient`
```php
// Relación que retorna pedidos pendientes de entrega
public function pendingDeliveries()
{
    return $this->hasMany(OriginalOrder::class)
        ->where(function ($query) {
            // Caso 1: Pedidos finalizados pero no entregados
            $query->whereNotNull('finished_at')
                  ->whereNull('actual_delivery_date');
        })
        ->orWhere(function ($query) {
            // Caso 2: Pedidos no finalizados con delivery_date programada
            $query->whereNull('finished_at')
                  ->whereNull('actual_delivery_date')
                  ->whereNotNull('delivery_date');
        })
        ->orderByRaw('CASE WHEN finished_at IS NOT NULL THEN 0 ELSE 1 END')
        ->orderByDesc('finished_at')
        ->orderBy('delivery_date');
}
```

#### `RouteDayAssignment`
- Asigna un vehículo (`fleet_vehicle_id`) a una ruta (`route_name_id`) en un día específico
- Incluye campo `user_id` para asignar conductor
- Relación `driver()` para acceder a datos del transportista

#### `RouteClientVehicleAssignment`
- Asigna un cliente a un vehículo específico en una fecha
- Relación con `RouteOrderAssignment` para gestionar pedidos individuales
- Permite reordenar clientes y pedidos con `sort_order`

### Endpoints Principales

#### Rutas Web
- `GET /customers/{customer}/routes` - Vista principal del planificador
- `GET /customers/{customer}/routes/client-details/{client}` - Detalles de cliente (JSON)
- `POST /customers/{customer}/routes/assign-vehicle` - Asignar vehículo a ruta
- `POST /customers/{customer}/routes/assign-client-vehicle` - Asignar cliente a vehículo
- `POST /customers/{customer}/routes/copy-previous-week` - Copiar vehículo de semana anterior
- `POST /customers/{customer}/routes/copy-entire-route-previous-week` - Copiar ruta completa
- `GET /customers/{customer}/routes/print-sheet` - Imprimir hoja de ruta de vehículo
- `GET /customers/{customer}/routes/print-entire-route` - Imprimir ruta completa
- `GET /customers/{customer}/routes/export-excel` - Exportar vehículo a Excel
- `GET /customers/{customer}/routes/export-entire-route-excel` - Exportar ruta completa a Excel
- `GET /my-deliveries` - Vista de transportista
- `POST /deliveries/mark-delivered` - Marcar pedido como entregado

### Permisos y Roles

#### Permiso: `deliveries-view`
- Permite acceso a la vista de transportista (`/my-deliveries`)
- Asignado por defecto a roles `admin` y `driver`

#### Rol: `driver` (Transportista)
- Acceso limitado a sus propias entregas
- Solo ve pedidos asignados a vehículos donde es conductor (`user_id`)

### Archivos Clave

#### Controladores
- `app/Http/Controllers/RoutePlanController.php` - Lógica del planificador
- `app/Http/Controllers/DeliveryController.php` - Vista de transportista

#### Modelos
- `app/Models/CustomerClient.php` - Clientes con relación `pendingDeliveries()`
- `app/Models/RouteDayAssignment.php` - Asignaciones de vehículos
- `app/Models/RouteClientVehicleAssignment.php` - Asignaciones cliente-vehículo
- `app/Models/OriginalOrder.php` - Pedidos con estados de entrega

#### Vistas
- `resources/views/customers/routes/index.blade.php` - Planificador principal
- `resources/views/customers/routes/print.blade.php` - Impresión de vehículo
- `resources/views/customers/routes/print-route.blade.php` - Impresión de ruta completa
- `resources/views/deliveries/my-deliveries.blade.php` - Vista de transportista
- `resources/views/components/routes/day-cell.blade.php` - Celda de día en planificador
- `resources/views/components/routes/vehicle-card.blade.php` - Tarjeta de vehículo

#### Exports
- `app/Exports/RouteSheetExport.php` - Excel de vehículo individual
- `app/Exports/EntireRouteExport.php` - Excel de ruta completa

#### Seeders
- `database/seeders/DeliveryPermissionsSeeder.php` - Permisos y roles de entregas

### Traducciones

Todas las cadenas de texto utilizan el sistema de traducción de Laravel (`__('key')`). Las traducciones están disponibles en:
- `resources/lang/en.json` - Inglés
- `resources/lang/es.json` - Español

Claves de traducción específicas del módulo de rutas:
- `Pending completion` - Estado de pedido no finalizado
- `Pending stock` - Pedido finalizado sin stock completo
- `Ready` - Pedido listo para entrega
- `Client details` - Título del modal de detalles
- `In stock` / `No stock` - Estado de disponibilidad de artículos
- `Stock ready` / `Awaiting stock` - Estado de stock de procesos

### Flujo de Trabajo Típico

1. **Planificación**:
   - Administrador accede a `/customers/{id}/routes`
   - Selecciona semana y día
   - Asigna vehículos a rutas usando el botón "+"
   - Arrastra clientes desde lista disponible a vehículos
   - Sistema automáticamente asigna pedidos pendientes del cliente

2. **Revisión de Detalles**:
   - Doble click sobre cliente para ver modal con pedidos
   - Verifica estado de cada pedido (Ready / Pending completion)
   - Imprime o exporta PDF si es necesario

3. **Asignación de Conductor**:
   - Click en botón 👤 del vehículo
   - Selecciona conductor del dropdown
   - Conductor recibe acceso a `/my-deliveries`

4. **Ejecución de Entrega**:
   - Conductor accede desde dispositivo móvil
   - Ve lista de clientes asignados para el día
   - Marca pedidos como entregados con botón "✓"
   - Sistema actualiza `actual_delivery_date` automáticamente

5. **Copiar Semana Anterior**:
   - Para repetir rutas recurrentes
   - Opción por vehículo individual o ruta completa
   - Sistema asigna pedidos ACTUALES (no históricos)

### Consideraciones Técnicas

- **Auto-refresh**: La vista del planificador se recarga automáticamente después de cambios, con detección inteligente de modals abiertos y drag & drop en progreso
- **Librerías externas**: jsPDF y html2canvas para generación de PDFs desde el navegador
- **Optimización de consultas**: Uso de `with()` y `whereHas()` para eager loading y evitar N+1
- **Seguridad**: Validación de pertenencia de cliente a customer en todos los endpoints
- **Ordenamiento**: Los pedidos se ordenan primero por finalizados, luego por fecha de finalización y fecha de entrega

## 📝 Licencia

 Xmart 2025

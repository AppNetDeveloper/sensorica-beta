# Dashboard Homepage - Cambios y Mejoras

## Resumen de Modificaciones

Este documento detalla todas las mejoras realizadas en el dashboard principal (`/dashboard`).

---

## 1. Cambios en la Navegación

### Logo
- **Archivo**: `resources/views/partial/nav-builder.blade.php`
- Tamaño del logo aumentado de 45px a **80px**

### Botón IA
- **Archivo**: `resources/views/partial/nav-builder.blade.php`
- Icono cambiado de `ti ti-robot` a `fas fa-wand-magic-sparkles` (icono de estrellas de IA)

---

## 2. KPIs del Dashboard

### Archivos Modificados
- `resources/views/dashboard/homepage.blade.php`
- `app/Http/Controllers/HomeController.php`
- `routes/web.php`
- `resources/lang/es.json`
- `resources/lang/en.json`

### KPIs Actuales (10 en total)

| KPI | Descripción | Permiso | Acción |
|-----|-------------|---------|--------|
| **Maintenance** | Mantenimientos últimos 7 días | `maintenance-show` | Modal selector de cliente |
| **Total Workers** | Total de trabajadores/operarios | `workers-show` | Link a workers-admin |
| **Order Organizer** | Grupos y máquinas por cliente | `productionline-kanban` | Modal selector de cliente |
| **Pending Orders** | Pedidos pendientes (en progreso/no iniciados) | `productionline-orders` | Modal selector de cliente |
| **QC Confirmations** | Confirmaciones de calidad últimas 24h | `productionline-incidents` | Modal selector de cliente |
| **Production Incidents** | Incidencias de producción activas | `productionline-incidents` | Modal selector de cliente |
| **Quality Issues** | Incidencias de calidad últimas 24h | `productionline-incidents` | Modal selector de cliente |
| **Production Lines** | Total líneas de producción | `shift-show` | Link a shift.index |
| **Active Lines** | Líneas activas | `shift-show` | Link a shift.index |
| **Paused/Stopped Lines** | Líneas pausadas o paradas | `shift-show` | Link a shift.index |

### KPIs Eliminados
- Total Role
- Total Module
- Total Languages
- Total Users (reemplazado por Maintenance)

### Layout Responsivo
Los KPIs se adaptan automáticamente según el tamaño de pantalla:
- **≥1400px**: 5 KPIs por fila (20% cada uno)
- **1200-1399px**: 4 KPIs por fila (25% cada uno)
- **992-1199px**: 3 KPIs por fila (33.33% cada uno)
- **768-991px**: 2 KPIs por fila (50% cada uno)
- **<768px**: 1 KPI por fila (100%)

---

## 3. Gráfica de Pedidos Finalizados

### Descripción
- Reemplaza la antigua gráfica de "Users Growth"
- Muestra pedidos finalizados por día
- Opciones: últimos 7 días / últimos 30 días

### Archivos
- **Vista**: `resources/views/dashboard/homepage.blade.php`
- **Controlador**: `app/Http/Controllers/HomeController.php` → método `productionChart()`
- **Ruta**: `routes/web.php` → `POST /production-chart`
- **JS**: `public/js/main.js` (label cambiado a "Total Orders")

---

## 4. Sistema de Auto-Refresh de KPIs

### Funcionalidad
- Los KPIs se actualizan automáticamente cada **2 minutos**
- Si un valor cambia, el KPI **parpadea 3 veces**

### Endpoint API
- **Ruta**: `GET /kpi-data`
- **Controlador**: `HomeController@getKpiData`
- **Respuesta**: JSON con valores actuales, tendencias, sparklines y alertas

### Ejemplo de Respuesta
```json
{
  "maintenance": {
    "value": 5,
    "previous": 3,
    "trend": "up",
    "sparkline": [1, 0, 2, 1, 0, 0, 1],
    "isAlert": true,
    "notClosed": 2
  },
  "workers": {
    "value": 45,
    "trend": "same",
    "sparkline": [45, 45, 45, 45, 45, 45, 45]
  }
}
```

---

## 5. Indicadores de Tendencia (Flechas)

### Descripción
Cada KPI muestra una flecha indicando la tendencia comparada con el período anterior:

| Tendencia | Icono | Color |
|-----------|-------|-------|
| Subió | ↑ `ti-arrow-up` | Verde (#28a745) |
| Bajó | ↓ `ti-arrow-down` | Rojo (#dc3545) |
| Igual | − `ti-minus` | Gris claro |

### Comparaciones
- **Maintenance**: últimos 7 días vs 7 días anteriores
- **Pending Orders**: pedidos actuales vs hace 7 días
- **QC Confirmations**: últimas 24h vs 24h anteriores
- **Quality Issues**: últimas 24h vs 24h anteriores

---

## 6. Mini Sparklines

### Descripción
Cada KPI muestra un mini gráfico de barras con los últimos 7 días de datos.

### Características
- 7 barras representando cada día
- La última barra (día actual) es más clara/brillante
- Altura proporcional al valor máximo del período
- Tooltip con el valor exacto al pasar el mouse

### CSS
```css
.kpi-sparkline {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 25px;
}
.kpi-sparkline .spark-bar {
    background: rgba(255, 255, 255, 0.4);
    border-radius: 2px;
}
.kpi-sparkline .spark-bar:last-child {
    background: rgba(255, 255, 255, 0.9);
}
```

---

## 7. Sistema de Alertas Visuales (Pulso)

### Descripción
Los KPIs muestran un efecto de pulso continuo cuando requieren atención.

### Alertas Configuradas

| KPI | Condición de Alerta | Color del Pulso |
|-----|---------------------|-----------------|
| **Maintenance** | Mantenimientos sin cerrar (`end_datetime` = NULL) | Naranja |
| **QC Confirmations** | 0 confirmaciones hoy (mal, no hay controles) | Cyan |
| **Quality Issues** | Incidencias de calidad del día actual > 0 | Amarillo |
| **Production Incidents** | Incidencias de producción activas > 0 | Rojo |

### Animaciones CSS
```css
@keyframes alertPulseRed { /* Production Incidents */ }
@keyframes alertPulseOrange { /* Maintenance */ }
@keyframes alertPulseYellow { /* Quality Issues */ }
@keyframes alertPulseCyan { /* QC Confirmations */ }
```

---

## 8. Modales de Selección de Cliente

### Descripción
Los KPIs que requieren seleccionar un centro de producción muestran un modal con la lista de clientes disponibles.

### Modales Implementados
1. `#selectCustomerModal` - Order Organizer
2. `#selectCustomerOrdersModal` - Pending Orders
3. `#selectCustomerQcModal` - QC Confirmations
4. `#selectCustomerProductionIncidentsModal` - Production Incidents
5. `#selectCustomerQualityIssuesModal` - Quality Issues
6. `#selectCustomerMaintenanceModal` - Maintenance

---

## 9. Tabla de Estado de Líneas de Producción

### Descripción
Tabla que muestra el estado actual de todas las líneas de producción.

### Estados Posibles
| Estado | Badge | Icono | Condición |
|--------|-------|-------|-----------|
| Active | Verde | `ti-player-play` | `action = start` o `type = stop, action = end` |
| Paused | Amarillo | `ti-player-pause` | `action = pause` |
| Stopped | Rojo | `ti-player-stop` | `action = stop/end` o `type = shift, action = end` |
| Incident | Naranja | `ti-alert-triangle` | `action = incident` o `type = incident` |
| Inactive | Gris | `ti-point` | Sin historial de turno |

---

## 10. Traducciones Añadidas

### Archivo: `resources/lang/es.json`
```json
{
  "Total Workers": "Total Trabajadores",
  "Order Organizer": "Organizador de Pedidos",
  "groups": "grupos",
  "machines": "máquinas",
  "Pending Orders": "Pedidos Pendientes",
  "pending": "pendientes",
  "in progress": "en progreso",
  "not started": "no iniciados",
  "QC Confirmations": "Confirmaciones QC",
  "last 24 hours": "últimas 24 horas",
  "Production Incidents": "Incidencias Producción",
  "active": "activas",
  "Quality Issues": "Incidencias Calidad",
  "Maintenance": "Mantenimiento",
  "last 7 days": "últimos 7 días",
  "Completed Orders": "Pedidos Finalizados",
  "Last 7 days": "Últimos 7 días",
  "Last 30 days": "Últimos 30 días"
}
```

### Archivo: `resources/lang/en.json`
```json
{
  "Total Workers": "Total Workers",
  "Order Organizer": "Order Organizer",
  "groups": "groups",
  "machines": "machines",
  "Pending Orders": "Pending Orders",
  "pending": "pending",
  "in progress": "in progress",
  "not started": "not started",
  "QC Confirmations": "QC Confirmations",
  "last 24 hours": "last 24 hours",
  "Production Incidents": "Production Incidents",
  "active": "active",
  "Quality Issues": "Quality Issues",
  "Maintenance": "Maintenance",
  "last 7 days": "last 7 days",
  "Completed Orders": "Completed Orders",
  "Last 7 days": "Last 7 days",
  "Last 30 days": "Last 30 days"
}
```

---

## 11. Rutas Añadidas

### Archivo: `routes/web.php`
```php
// API para obtener datos de KPIs en tiempo real
Route::get('/kpi-data', [HomeController::class, 'getKpiData'])
    ->name('get.kpi.data')
    ->middleware(['auth', 'XSS']);

// API para obtener datos de la gráfica de pedidos
Route::post('/production-chart', [HomeController::class, 'productionChart'])
    ->name('get.production.chart.data')
    ->middleware(['auth', 'XSS']);
```

---

## 12. Métodos del Controlador

### Archivo: `app/Http/Controllers/HomeController.php`

#### `index()`
Método principal que carga todos los datos para el dashboard:
- `$maintenanceStats` - Estadísticas de mantenimiento
- `$operatorsCount` - Conteo de trabajadores
- `$orderOrganizerStats` - Grupos y máquinas
- `$originalOrdersStats` - Pedidos pendientes
- `$incidentsStats` - QC, incidencias producción, calidad
- `$productionLineStats` - Estados de líneas
- `$productionLines` - Lista de líneas para la tabla
- `$customersFor*` - Listas de clientes para modales

#### `getKpiData()`
Endpoint API que retorna datos actualizados de todos los KPIs con:
- Valor actual
- Valor período anterior
- Tendencia (up/down/same)
- Datos sparkline (7 días)
- Flag de alerta (isAlert)

#### `productionChart()`
Retorna datos para la gráfica de pedidos finalizados:
- Acepta tipo: 'week' (7 días) o 'month' (30 días)
- Retorna: labels (fechas) y values (conteos)

---

## Estructura de Archivos Modificados

```
/var/www/html/
├── app/
│   └── Http/
│       └── Controllers/
│           └── HomeController.php          # Métodos index(), getKpiData(), productionChart()
├── resources/
│   ├── views/
│   │   ├── dashboard/
│   │   │   └── homepage.blade.php          # Vista principal del dashboard
│   │   └── partial/
│   │       └── nav-builder.blade.php       # Logo y botón IA
│   └── lang/
│       ├── es.json                         # Traducciones español
│       └── en.json                         # Traducciones inglés
├── routes/
│   └── web.php                             # Rutas /kpi-data, /production-chart
└── public/
    └── js/
        └── main.js                         # Label "Total Orders" para gráfica
```

---

## Configuración

### Intervalo de Auto-Refresh
El intervalo de actualización de KPIs se puede modificar en `homepage.blade.php`:
```javascript
var KPI_REFRESH_INTERVAL = 120000; // 2 minutos en milisegundos
```

### Permisos Requeridos
- `maintenance-show` - Ver KPI de mantenimiento
- `workers-show` - Ver KPI de trabajadores
- `productionline-kanban` - Ver KPI de Order Organizer
- `productionline-orders` - Ver KPI de pedidos y gráfica
- `productionline-incidents` - Ver KPIs de incidencias y calidad
- `shift-show` - Ver KPIs de líneas de producción y tabla

---

*Última actualización: Noviembre 2024*

# 📊 Sistema de Historial WT/WTM

Sistema para registrar histórico de tiempos de espera (Wait Time) por línea de producción.

## 🎯 Qué hace

- **WT (Wait Time)**: Tiempo medio de espera desde `estimated_start_datetime`
- **WTM (Wait Time Median)**: Tiempo mediano de espera (más robusto ante outliers)
- Captura cada **1 hora** automáticamente
- Guarda historial en tabla `production_line_wait_time_history`

## 📦 Archivos creados

1. **Migración**: `database/migrations/2025_10_21_210400_create_production_line_wait_time_history_table.php`
2. **Modelo**: `app/Models/ProductionLineWaitTimeHistory.php`
3. **Comando**: `app/Console/Commands/CaptureProductionLineWaitTimes.php`
4. **Supervisor**: `laravel-production-line-wait-times.conf`

## 🚀 Instalación

### 1. Ejecutar migración

```bash
cd /var/www/html
php artisan migrate
```

### 2. Probar comando manualmente (una sola ejecución)

```bash
php artisan production:capture-line-wait-times --once
```

Deberías ver salida como:
```
📊 Capturando WT/WTM para la hora: 2025-10-21 21:00:00
✅ Línea 1 (Línea A) | Órdenes: 5 | WT: 45.23m | WTM: 38.50m
✅ Línea 2 (Línea B) | Órdenes: 3 | WT: 62.10m | WTM: 60.00m
✅ Captura de WT/WTM finalizada.
```

### 3. Configurar Supervisor

```bash
# Copiar archivo de configuración
sudo cp /var/www/html/laravel-production-line-wait-times.conf /etc/supervisor/conf.d/

# Recargar configuración de Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Verificar que está corriendo
sudo supervisorctl status laravel-production-line-wait-times
```

Deberías ver:
```
laravel-production-line-wait-times:laravel-production-line-wait-times_00   RUNNING   pid 12345, uptime 0:00:05
```

### 4. Comandos útiles de Supervisor

```bash
# Ver logs en tiempo real
sudo tail -f /var/www/html/storage/logs/production-line-wait-times.out.log

# Reiniciar el proceso
sudo supervisorctl restart laravel-production-line-wait-times

# Detener el proceso
sudo supervisorctl stop laravel-production-line-wait-times

# Iniciar el proceso
sudo supervisorctl start laravel-production-line-wait-times
```

## 📊 Estructura de la tabla

```sql
production_line_wait_time_history
├── id
├── production_line_id         → FK a production_lines
├── order_count                → Número de órdenes consideradas
├── wait_time_mean            → WT: Tiempo medio (minutos)
├── wait_time_median          → WTM: Tiempo mediano (minutos)
├── wait_time_min             → Tiempo mínimo (minutos)
├── wait_time_max             → Tiempo máximo (minutos)
├── captured_at               → Momento de captura (hora exacta)
├── created_at
└── updated_at
```

## 🔍 Consultas útiles

### Ver últimos registros de una línea

```php
use App\Models\ProductionLineWaitTimeHistory;

$history = ProductionLineWaitTimeHistory::where('production_line_id', 1)
    ->orderBy('captured_at', 'desc')
    ->limit(24) // Últimas 24 horas
    ->get();
```

### Historial de las últimas 7 días

```php
$history = ProductionLineWaitTimeHistory::where('production_line_id', 1)
    ->where('captured_at', '>=', now()->subDays(7))
    ->orderBy('captured_at', 'asc')
    ->get();
```

### Comparar todas las líneas en la última hora

```php
$lastCapture = ProductionLineWaitTimeHistory::max('captured_at');

$comparison = ProductionLineWaitTimeHistory::with('productionLine')
    ->where('captured_at', $lastCapture)
    ->orderBy('wait_time_mean', 'desc')
    ->get();
```

## 📈 Ideas para gráficas

### 1. Gráfica de tendencia WT/WTM por línea

Similar a "Carga horaria de líneas activas", puedes crear gráficas con:

- **Eje X**: Tiempo (horas del día, últimos 7 días, etc.)
- **Eje Y**: Minutos de espera
- **Líneas**: WT (media) y WTM (mediana)

### 2. Comparación entre líneas

- Gráfica de barras comparando WT promedio de cada línea
- Heatmap de esperas por hora y línea

### 3. Alertas

- Si `wait_time_mean > X minutos` → Alerta de cuello de botella
- Si diferencia entre `wait_time_max` y `wait_time_min` es muy grande → Alerta de inconsistencia

## 🛠️ Opciones del comando

```bash
# Ejecución normal (bucle infinito, captura cada hora)
php artisan production:capture-line-wait-times

# Una sola captura y sale
php artisan production:capture-line-wait-times --once

# Sobrescribir registro existente para la hora actual
php artisan production:capture-line-wait-times --force

# Ambas opciones combinadas
php artisan production:capture-line-wait-times --once --force
```

## 🎨 Integración en Kanban

En la vista `order-kanban.blade.php`, en la sección "Carga horaria de líneas activas", podrías añadir:

```blade
<div class="card mt-3">
    <div class="card-header">
        <h5><i class="fas fa-clock"></i> Historial WT/WTM por Línea</h5>
    </div>
    <div class="card-body">
        <canvas id="waitTimeHistoryChart"></canvas>
    </div>
</div>
```

Luego con Chart.js puedes renderizar el historial desde el backend:

```javascript
const ctx = document.getElementById('waitTimeHistoryChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: historyData.map(h => h.captured_at),
        datasets: [
            {
                label: 'WT (Media)',
                data: historyData.map(h => h.wait_time_mean),
                borderColor: 'rgb(75, 192, 192)',
            },
            {
                label: 'WTM (Mediana)',
                data: historyData.map(h => h.wait_time_median),
                borderColor: 'rgb(255, 99, 132)',
            }
        ]
    }
});
```

## ✅ Checklist de instalación

- [ ] Ejecutar migración
- [ ] Probar comando con `--once`
- [ ] Copiar .conf a `/etc/supervisor/conf.d/`
- [ ] `sudo supervisorctl reread && sudo supervisorctl update`
- [ ] Verificar que está corriendo con `sudo supervisorctl status`
- [ ] Verificar logs: `tail -f storage/logs/production-line-wait-times.out.log`
- [ ] Esperar 1 hora y verificar nuevos registros en la BD
- [ ] Crear endpoint para gráficas (opcional)
- [ ] Integrar visualización en Kanban (opcional)

## 🐛 Troubleshooting

### El proceso no arranca

```bash
# Ver logs de error
sudo tail -f /var/www/html/storage/logs/production-line-wait-times.err.log

# Ver status detallado
sudo supervisorctl tail -f laravel-production-line-wait-times stderr
```

### El proceso se detiene constantemente

Verificar permisos:
```bash
sudo chown -R www-data:www-data /var/www/html/storage/logs
sudo chmod -R 775 /var/www/html/storage/logs
```

### No hay datos en la tabla

Verificar que las órdenes tengan `estimated_start_datetime`:
```sql
SELECT COUNT(*) FROM production_orders 
WHERE estimated_start_datetime IS NOT NULL 
AND status IN (0, 1);
```

## 📝 Notas

- El comando captura al **inicio de cada hora** (`startOfHour()`)
- Solo considera órdenes con `status` 0 (Pendiente) o 1 (En progreso)
- Solo considera órdenes que tienen `estimated_start_datetime` definido
- Los valores **negativos** en WT/WTM indican que aún no ha llegado la hora programada
- Los valores **positivos** indican retraso (ya pasó la hora de inicio)

---

**Autor**: Sistema WT/WTM v1.0  
**Fecha**: 2025-10-21

# Campos del Archivo .env No Visibles en la Interfaz de Configuración

## Análisis Comparativo

Tras revisar el archivo `.env` actual y compararlo con la interfaz de configuración existente en `resources/views/settings/setting.blade.php`, se han identificado múltiples variables de entorno que no tienen representación visual en la interfaz de usuario.

## Campos Faltantes por Categoría

### 1. Configuración de AWS (Amazon Web Services)
**Estado**: No visible en interfaz
**Variables**:
- `AWS_ACCESS_KEY_ID` (línea 48)
- `AWS_SECRET_ACCESS_KEY` (línea 49)
- `AWS_DEFAULT_REGION` (línea 50)
- `AWS_BUCKET` (línea 51)
- `AWS_USE_PATH_STYLE_ENDPOINT` (línea 52)

**Impacto**: Configuración para almacenamiento en S3, servicios cloud de AWS

### 2. Configuración de PUSHER (WebSockets)
**Estado**: No visible en interfaz
**Variables**:
- `PUSHER_APP_ID` (línea 54)
- `PUSHER_APP_KEY` (línea 55)
- `PUSHER_APP_SECRET` (línea 56)
- `PUSHER_APP_CLUSTER` (línea 57)
- `MIX_PUSHER_APP_KEY` (línea 59)
- `MIX_PUSHER_APP_CLUSTER` (línea 60)

**Impacto**: Configuración para comunicación en tiempo real, notificaciones push

### 3. Configuración de Memcached
**Estado**: No visible en interfaz
**Variables**:
- `MEMCACHED_HOST` (línea 33)

**Impacto**: Sistema de caché alternativo a Redis

### 4. Configuración de Mantenimiento y Alertas
**Estado**: Parcialmente visible
**Variables faltantes**:
- `WHATSAPP_PHONE_MANTENIMIENTO` (línea 101)
- `TELEGRAM_MANTENIMIENTO_PEERS` (línea 103)
- `WHATSAPP_PHONE_ORDEN_INCIDENCIA` (línea 153)

**Impacto**: Notificaciones específicas para mantenimiento e incidencias

### 5. Configuración de Procesamiento de Órdenes
**Estado**: No visible en interfaz
**Variables**:
- `PROCESS_ORDERS_OUT_OF_STOCK` (línea 139)
- `CREATE_ALL_PROCESSORDERS` (línea 140)
- `PRODUCTION_BREAK_TIME` (línea 142)
- `PRODUCTION_OEE_HISTORY_DAYS` (línea 144)
- `PRODUCTION_OEE_MINIMUM` (línea 145)
- `ORDER_MIN_ACTIVE_SECONDS` (línea 154)
- `PRODUCTION_FILTER_NOT_READY_KANBAN` (línea 157)

**Impacto**: Control del comportamiento del sistema de producción y órdenes

### 6. Configuración de Seguridad y Callbacks
**Estado**: No visible en interfaz
**Variables**:
- `READY_AFTER_SAFETY_HOURS` (línea 151)
- `CALLBACK_MAX_ATTEMPTS` (línea 152)

**Impacto**: Configuración de seguridad y reintentos de callbacks

### 7. Configuración de IA / Ollama
**Estado**: No visible en interfaz
**Variables**:
- `AI_URL` (línea 148)
- `AI_TOKEN` (línea 149)

**Impacto**: Integración con servicios de inteligencia artificial

### 8. Configuración de Sistema (Adicionales)
**Estado**: Parcialmente visible
**Variables faltantes**:
- `APP_DEBUG` (línea 9)
- `FORCE_HTTPS` (línea 10)
- `LOG_CHANNEL` (línea 7)
- `LOG_LEVEL` (línea 8)
- `APP_LOG_LEVEL` (línea 4)
- `APP_ENV` (línea 2)

**Impacto**: Configuración avanzada del sistema

## Campos con Diferencias entre .env y Interfaz

### 1. PRODUCTION_MAX_TIME
- **Valor en .env**: `'120'` (línea 92)
- **Valor mostrado en interfaz**: `'5'` (por defecto)
- **Problema**: La interfaz no carga el valor real del .env

### 2. CLEAR_DB_DAY
- **Valor en .env**: `'740'` (línea 113)
- **Valor mostrado en interfaz**: `'40'` (por defecto)
- **Problema**: La interfaz no carga el valor real del .env

## Propuesta de Nuevas Secciones para la Interfaz

### 1. Sección: Configuración AWS
```html
<div id="useradd-3-9" class="card mb-4">
    <div class="card-header">
        <h5>{{ __('AWS Configuration') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('aws_access_key_id', __('AWS Access Key ID'), ['class' => 'form-label']) }}
                    {{ Form::text('aws_access_key_id', env('AWS_ACCESS_KEY_ID'), ['class' => 'form-control']) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('aws_secret_access_key', __('AWS Secret Access Key'), ['class' => 'form-label']) }}
                    {{ Form::password('aws_secret_access_key', env('AWS_SECRET_ACCESS_KEY'), ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        <!-- Más campos AWS -->
    </div>
</div>
```

### 2. Sección: Configuración PUSHER
```html
<div id="useradd-3-10" class="card mb-4">
    <div class="card-header">
        <h5>{{ __('PUSHER Configuration') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('pusher_app_id', __('PUSHER App ID'), ['class' => 'form-label']) }}
                    {{ Form::text('pusher_app_id', env('PUSHER_APP_ID'), ['class' => 'form-control']) }}
                </div>
            </div>
            <!-- Más campos PUSHER -->
        </div>
    </div>
</div>
```

### 3. Sección: Configuración de Producción Avanzada
```html
<div id="useradd-3-11" class="card mb-4">
    <div class="card-header">
        <h5>{{ __('Advanced Production Settings') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {{ Form::label('production_break_time', __('Break Time (minutes)'), ['class' => 'form-label']) }}
                    {{ Form::number('production_break_time', env('PRODUCTION_BREAK_TIME', '30'), ['class' => 'form-control']) }}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {{ Form::label('production_oee_history_days', __('OEE History Days'), ['class' => 'form-label']) }}
                    {{ Form::number('production_oee_history_days', env('PRODUCTION_OEE_HISTORY_DAYS', '10'), ['class' => 'form-control']) }}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {{ Form::label('production_oee_minimum', __('OEE Minimum (%)'), ['class' => 'form-label']) }}
                    {{ Form::number('production_oee_minimum', env('PRODUCTION_OEE_MINIMUM', '60'), ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        <!-- Más campos de producción -->
    </div>
</div>
```

### 4. Sección: Configuración de IA
```html
<div id="useradd-3-12" class="card mb-4">
    <div class="card-header">
        <h5>{{ __('AI Configuration') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('ai_url', __('AI Service URL'), ['class' => 'form-label']) }}
                    {{ Form::url('ai_url', env('AI_URL'), ['class' => 'form-control']) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('ai_token', __('AI Token'), ['class' => 'form-label']) }}
                    {{ Form::password('ai_token', env('AI_TOKEN'), ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
    </div>
</div>
```

## Métodos del Controlador Requeridos

### 1. Método para guardar configuración AWS
```php
public function saveAwsSettings(Request $request)
{
    $arrEnv = [
        'AWS_ACCESS_KEY_ID' => $request->aws_access_key_id,
        'AWS_SECRET_ACCESS_KEY' => $request->aws_secret_access_key,
        'AWS_DEFAULT_REGION' => $request->aws_default_region,
        'AWS_BUCKET' => $request->aws_bucket,
        'AWS_USE_PATH_STYLE_ENDPOINT' => $request->aws_use_path_style_endpoint,
    ];
    UtilityFacades::setEnvironmentValue($arrEnv);
    return redirect()->back()->with('success', __('AWS settings updated successfully.'));
}
```

### 2. Método para guardar configuración PUSHER
```php
public function savePusherSettings(Request $request)
{
    $arrEnv = [
        'PUSHER_APP_ID' => $request->pusher_app_id,
        'PUSHER_APP_KEY' => $request->pusher_app_key,
        'PUSHER_APP_SECRET' => $request->pusher_app_secret,
        'PUSHER_APP_CLUSTER' => $request->pusher_app_cluster,
    ];
    UtilityFacades::setEnvironmentValue($arrEnv);
    return redirect()->back()->with('success', __('PUSHER settings updated successfully.'));
}
```

### 3. Método para guardar configuración de Producción Avanzada
```php
public function saveAdvancedProductionSettings(Request $request)
{
    $arrEnv = [
        'PROCESS_ORDERS_OUT_OF_STOCK' => $request->has('process_orders_out_of_stock') ? 'true' : 'false',
        'CREATE_ALL_PROCESSORDERS' => $request->has('create_all_processorders') ? 'true' : 'false',
        'PRODUCTION_BREAK_TIME' => $request->production_break_time,
        'PRODUCTION_OEE_HISTORY_DAYS' => $request->production_oee_history_days,
        'PRODUCTION_OEE_MINIMUM' => $request->production_oee_minimum,
        'ORDER_MIN_ACTIVE_SECONDS' => $request->order_min_active_seconds,
        'PRODUCTION_FILTER_NOT_READY_KANBAN' => $request->has('production_filter_not_ready_kanban') ? 'true' : 'false',
    ];
    UtilityFacades::setEnvironmentValue($arrEnv);
    return redirect()->back()->with('success', __('Advanced production settings updated successfully.'));
}
```

### 4. Método para guardar configuración de IA
```php
public function saveAiSettings(Request $request)
{
    $arrEnv = [
        'AI_URL' => $request->ai_url,
        'AI_TOKEN' => $request->ai_token,
    ];
    UtilityFacades::setEnvironmentValue($arrEnv);
    return redirect()->back()->with('success', __('AI settings updated successfully.'));
}
```

## Rutas Adicionales Requeridas

```php
// Añadir al grupo de rutas existente
Route::post('settings/aws', [SettingController::class, 'saveAwsSettings'])->name('settings.aws');
Route::post('settings/pusher', [SettingController::class, 'savePusherSettings'])->name('settings.pusher');
Route::post('settings/advanced-production', [SettingController::class, 'saveAdvancedProductionSettings'])->name('settings.advanced-production');
Route::post('settings/ai', [SettingController::class, 'saveAiSettings'])->name('settings.ai');
```

## Actualización del Menú Lateral

```html
<!-- Añadir al menú existente en resources/views/settings/setting.blade.php -->
<a href="settings#useradd-3-9" class="list-group-item list-group-item-action useradd-3-9">
    {{ __('AWS Configuration') }} <div class="float-end"></div>
</a>
<a href="settings#useradd-3-10" class="list-group-item list-group-item-action useradd-3-10">
    {{ __('PUSHER Configuration') }} <div class="float-end"></div>
</a>
<a href="settings#useradd-3-11" class="list-group-item list-group-item-action useradd-3-11">
    {{ __('Advanced Production') }} <div class="float-end"></div>
</a>
<a href="settings#useradd-3-12" class="list-group-item list-group-item-action useradd-3-12">
    {{ __('AI Configuration') }} <div class="float-end"></div>
</a>
```

## Problemas Críticos Identificados

### 1. Valores No Cargados Correctamente
Los siguientes campos no cargan sus valores reales del .env:
- `PRODUCTION_MAX_TIME` (muestra 5 en lugar de 120)
- `CLEAR_DB_DAY` (muestra 40 en lugar de 740)

**Causa**: El controlador no está leyendo estos valores del .env correctamente
**Solución**: Actualizar el método `index()` para que lea estos valores

### 2. Campos Críticos Sin Interfaz
Configuraciones importantes sin acceso visual:
- Tokens de API (AI, AWS)
- Configuración de producción avanzada
- Sistema de notificaciones de mantenimiento

**Impacto**: Los administradores no pueden modificar estas configuraciones sin acceso directo al servidor

## Prioridades de Implementación

### Alta Prioridad
1. Corregir carga de valores existentes (PRODUCTION_MAX_TIME, CLEAR_DB_DAY)
2. Implementar configuración de Producción Avanzada
3. Agregar configuración de IA

### Media Prioridad
1. Implementar configuración AWS
2. Agregar configuración PUSHER
3. Completar configuración de notificaciones

### Baja Prioridad
1. Configuración de Memcached
2. Configuración avanzada del sistema (APP_DEBUG, etc.)

## Conclusiones

Existen aproximadamente **25 variables de entorno** que no tienen representación en la interfaz actual, algunas de ellas críticas para el funcionamiento del sistema. La implementación de estas secciones mejoraría significativamente la capacidad de gestión de los administradores y reduciría la necesidad de acceso directo al servidor para configuraciones comunes.

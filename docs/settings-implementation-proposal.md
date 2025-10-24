# Implementación Completa de Secciones Faltantes del Sistema de Configuración

## Resumen Ejecutivo

Este documento proporciona una implementación completa para añadir las secciones faltantes al sistema de configuración `/settings`, incluyendo código para vistas, controladores y rutas.

## 1. Corrección de Problemas Existentes

### Problema: Valores no cargados correctamente

Algunos campos no cargan sus valores reales del archivo .env:
- `PRODUCTION_MAX_TIME` (muestra 5 en lugar de 120)
- `CLEAR_DB_DAY` (muestra 40 en lugar de 740)

### Solución: Actualizar método `index()` en SettingController

```php
// En app/Http/Controllers/SettingController.php - método index()

// Reemplazar las líneas existentes con estas correcciones:

// Línea 389-391 (Production Settings)
{{ Form::number('production_max_time', env('PRODUCTION_MAX_TIME', '120'), ['class' => 'form-control', 'min' => '1']) }}

// Línea 400-402 (System Settings)
{{ Form::number('clear_db_day', env('CLEAR_DB_DAY', '740'), ['class' => 'form-control', 'min' => '1']) }}

// Línea 404-406 (Production Settings)
{{ Form::number('production_min_time_weight', env('PRODUCTION_MIN_TIME_WEIGHT', '30'), ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
```

## 2. Nuevas Secciones de Configuración

### 2.1. AWS Configuration

#### Vista: resources/views/settings/partials/aws-configuration.blade.php

```blade.php
{{-- Sección: AWS Configuration --}}
<div id="useradd-3-9" class="card mb-4">
    {{ Form::open(['route' => 'settings.aws', 'method' => 'post']) }}
    <div class="card-header">
        <h5>{{ __('AWS Configuration') }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            {{ __('Configure AWS services for S3 storage and other AWS integrations.') }}
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('aws_access_key_id', __('AWS Access Key ID'), ['class' => 'form-label']) }}
                    {{ Form::text('aws_access_key_id', env('AWS_ACCESS_KEY_ID'), ['class' => 'form-control', 'placeholder' => 'AKIAIOSFODNN7EXAMPLE']) }}
                    <small class="form-text text-muted">{{ __('Your AWS access key ID') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('aws_secret_access_key', __('AWS Secret Access Key'), ['class' => 'form-label']) }}
                    <div class="input-group">
                        {{ Form::password('aws_secret_access_key', env('AWS_SECRET_ACCESS_KEY'), ['class' => 'form-control', 'placeholder' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY']) }}
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">{{ __('Your AWS secret access key') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('aws_default_region', __('AWS Default Region'), ['class' => 'form-label']) }}
                    {{ Form::select('aws_default_region', [
                        'us-east-1' => 'US East (N. Virginia)',
                        'us-west-2' => 'US West (Oregon)',
                        'eu-west-1' => 'EU (Ireland)',
                        'eu-central-1' => 'EU (Frankfurt)',
                        'ap-southeast-1' => 'Asia Pacific (Singapore)',
                    ], env('AWS_DEFAULT_REGION', 'us-east-1'), ['class' => 'form-control']) }}
                    <small class="form-text text-muted">{{ __('AWS region for your services') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('aws_bucket', __('AWS S3 Bucket'), ['class' => 'form-label']) }}
                    {{ Form::text('aws_bucket', env('AWS_BUCKET'), ['class' => 'form-control', 'placeholder' => 'my-bucket-name']) }}
                    <small class="form-text text-muted">{{ __('S3 bucket name for file storage') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="form-check form-switch">
                    @php
                        $usePathStyle = env('AWS_USE_PATH_STYLE_ENDPOINT', 'false');
                        $isPathStyleChecked = ($usePathStyle === 'true' || $usePathStyle === true);
                    @endphp
                    {{ Form::checkbox('aws_use_path_style_endpoint', '1', $isPathStyleChecked, ['class' => 'form-check-input', 'id' => 'aws_use_path_style_endpoint']) }}
                    {{ Form::label('aws_use_path_style_endpoint', __('Use Path Style Endpoint'), ['class' => 'form-check-label']) }}
                </div>
                <small class="form-text text-muted">{{ __('Enable path-style endpoint for S3 compatibility') }}</small>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="button" class="btn btn-info" id="test-aws-connection">
                    <i class="fas fa-plug"></i> {{ __('Test AWS Connection') }}
                </button>
                <div id="aws-connection-status" class="mt-3"></div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-whitesmoke">
        <span class="float-end">
            <button class="btn btn-primary" type="submit" id="save-aws-btn">{{ __('Save AWS Settings') }}</button>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary me-1">{{ __('Cancel') }}</a>
        </span>
    </div>
    {{ Form::close() }}
</div>
```

#### Controlador: Método en SettingController

```php
/**
 * Guarda la configuración de AWS.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function saveAwsSettings(Request $request)
{
    $request->validate([
        'aws_access_key_id' => 'required|string|max:255',
        'aws_secret_access_key' => 'required|string|max:255',
        'aws_default_region' => 'required|string|max:255',
        'aws_bucket' => 'nullable|string|max:255',
        'aws_use_path_style_endpoint' => 'nullable',
    ]);

    // Actualizar el archivo .env con las nuevas configuraciones
    $arrEnv = [
        'AWS_ACCESS_KEY_ID' => $request->aws_access_key_id,
        'AWS_SECRET_ACCESS_KEY' => $request->aws_secret_access_key,
        'AWS_DEFAULT_REGION' => $request->aws_default_region,
        'AWS_BUCKET' => $request->aws_bucket ?? '',
        'AWS_USE_PATH_STYLE_ENDPOINT' => $request->has('aws_use_path_style_endpoint') ? 'true' : 'false',
    ];
    
    UtilityFacades::setEnvironmentValue($arrEnv);

    return redirect()->back()->with('success', __('AWS settings updated successfully.'));
}

/**
 * Prueba la conexión a AWS.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function testAwsConnection(Request $request)
{
    try {
        // Configuración temporal de AWS
        config([
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => $request->aws_access_key_id ?? env('AWS_ACCESS_KEY_ID'),
                'secret' => $request->aws_secret_access_key ?? env('AWS_SECRET_ACCESS_KEY'),
                'region' => $request->aws_default_region ?? env('AWS_DEFAULT_REGION'),
                'bucket' => $request->aws_bucket ?? env('AWS_BUCKET'),
                'use_path_style_endpoint' => $request->has('aws_use_path_style_endpoint') ? true : false,
            ]
        ]);

        // Intentar listar archivos para probar conexión
        Storage::disk('s3')->files();
        
        return response()->json([
            'success' => true,
            'message' => __('AWS connection successful.')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('AWS connection failed: ') . $e->getMessage()
        ], 500);
    }
}
```

### 2.2. PUSHER Configuration

#### Vista: resources/views/settings/partials/pusher-configuration.blade.php

```blade.php
{{-- Sección: PUSHER Configuration --}}
<div id="useradd-3-10" class="card mb-4">
    {{ Form::open(['route' => 'settings.pusher', 'method' => 'post']) }}
    <div class="card-header">
        <h5>{{ __('PUSHER Configuration') }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            {{ __('Configure PUSHER for real-time notifications and WebSockets.') }}
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('pusher_app_id', __('PUSHER App ID'), ['class' => 'form-label']) }}
                    {{ Form::text('pusher_app_id', env('PUSHER_APP_ID'), ['class' => 'form-control', 'placeholder' => '123456']) }}
                    <small class="form-text text-muted">{{ __('Your PUSHER application ID') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('pusher_app_key', __('PUSHER App Key'), ['class' => 'form-label']) }}
                    {{ Form::text('pusher_app_key', env('PUSHER_APP_KEY'), ['class' => 'form-control', 'placeholder' => 'abcdefg123456']) }}
                    <small class="form-text text-muted">{{ __('Your PUSHER application key') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('pusher_app_secret', __('PUSHER App Secret'), ['class' => 'form-label']) }}
                    <div class="input-group">
                        {{ Form::password('pusher_app_secret', env('PUSHER_APP_SECRET'), ['class' => 'form-control', 'placeholder' => 'secret123456']) }}
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">{{ __('Your PUSHER application secret') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('pusher_app_cluster', __('PUSHER App Cluster'), ['class' => 'form-label']) }}
                    {{ Form::select('pusher_app_cluster', [
                        'mt1' => 'US East',
                        'eu' => 'EU',
                        'ap2' => 'Asia Pacific',
                        'sa1' => 'South America',
                    ], env('PUSHER_APP_CLUSTER', 'mt1'), ['class' => 'form-control']) }}
                    <small class="form-text text-muted">{{ __('PUSHER cluster for your application') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="button" class="btn btn-info" id="test-pusher-connection">
                    <i class="fas fa-plug"></i> {{ __('Test PUSHER Connection') }}
                </button>
                <div id="pusher-connection-status" class="mt-3"></div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-whitesmoke">
        <span class="float-end">
            <button class="btn btn-primary" type="submit" id="save-pusher-btn">{{ __('Save PUSHER Settings') }}</button>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary me-1">{{ __('Cancel') }}</a>
        </span>
    </div>
    {{ Form::close() }}
</div>
```

#### Controlador: Métodos en SettingController

```php
/**
 * Guarda la configuración de PUSHER.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function savePusherSettings(Request $request)
{
    $request->validate([
        'pusher_app_id' => 'required|string|max:255',
        'pusher_app_key' => 'required|string|max:255',
        'pusher_app_secret' => 'required|string|max:255',
        'pusher_app_cluster' => 'required|string|max:255',
    ]);

    // Actualizar el archivo .env con las nuevas configuraciones
    $arrEnv = [
        'PUSHER_APP_ID' => $request->pusher_app_id,
        'PUSHER_APP_KEY' => $request->pusher_app_key,
        'PUSHER_APP_SECRET' => $request->pusher_app_secret,
        'PUSHER_APP_CLUSTER' => $request->pusher_app_cluster,
        'MIX_PUSHER_APP_KEY' => $request->pusher_app_key,
        'MIX_PUSHER_APP_CLUSTER' => $request->pusher_app_cluster,
    ];
    
    UtilityFacades::setEnvironmentValue($arrEnv);

    return redirect()->back()->with('success', __('PUSHER settings updated successfully.'));
}

/**
 * Prueba la conexión a PUSHER.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function testPusherConnection(Request $request)
{
    try {
        $options = [
            'cluster' => $request->pusher_app_cluster ?? env('PUSHER_APP_CLUSTER'),
            'useTLS' => true
        ];
        
        $pusher = new \Pusher\Pusher(
            $request->pusher_app_key ?? env('PUSHER_APP_KEY'),
            $request->pusher_app_secret ?? env('PUSHER_APP_SECRET'),
            $request->pusher_app_id ?? env('PUSHER_APP_ID'),
            $options
        );
        
        // Intentar autenticar para probar conexión
        $socket_id = $pusher->socket_auth('test-channel', 'test-event');
        
        return response()->json([
            'success' => true,
            'message' => __('PUSHER connection successful.')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('PUSHER connection failed: ') . $e->getMessage()
        ], 500);
    }
}
```

### 2.3. Advanced Production Settings

#### Vista: resources/views/settings/partials/advanced-production.blade.php

```blade.php
{{-- Sección: Advanced Production Settings --}}
<div id="useradd-3-11" class="card mb-4">
    {{ Form::open(['route' => 'settings.advanced-production', 'method' => 'post']) }}
    <div class="card-header">
        <h5>{{ __('Advanced Production Settings') }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ __('These settings affect production behavior. Change with caution.') }}
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="form-check form-switch">
                        @php
                            $processOrders = env('PROCESS_ORDERS_OUT_OF_STOCK', 'true');
                            $isProcessOrdersChecked = ($processOrders === 'true' || $processOrders === true);
                        @endphp
                        {{ Form::checkbox('process_orders_out_of_stock', '1', $isProcessOrdersChecked, ['class' => 'form-check-input', 'id' => 'process_orders_out_of_stock']) }}
                        {{ Form::label('process_orders_out_of_stock', __('Process Orders Out of Stock'), ['class' => 'form-check-label']) }}
                    </div>
                    <small class="form-text text-muted">{{ __('Process orders even when items are out of stock') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="form-check form-switch">
                        @php
                            $createAllOrders = env('CREATE_ALL_PROCESSORDERS', 'true');
                            $isCreateAllOrdersChecked = ($createAllOrders === 'true' || $createAllOrders === true);
                        @endphp
                        {{ Form::checkbox('create_all_processorders', '1', $isCreateAllOrdersChecked, ['class' => 'form-check-input', 'id' => 'create_all_processorders']) }}
                        {{ Form::label('create_all_processorders', __('Create All Process Orders'), ['class' => 'form-check-label']) }}
                    </div>
                    <small class="form-text text-muted">{{ __('Automatically create all process orders') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    {{ Form::label('production_break_time', __('Break Time (minutes)'), ['class' => 'form-label']) }}
                    {{ Form::number('production_break_time', env('PRODUCTION_BREAK_TIME', '30'), ['class' => 'form-control', 'min' => '0', 'max' => '120']) }}
                    <small class="form-text text-muted">{{ __('Standard break time between production cycles') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {{ Form::label('production_oee_history_days', __('OEE History Days'), ['class' => 'form-label']) }}
                    {{ Form::number('production_oee_history_days', env('PRODUCTION_OEE_HISTORY_DAYS', '10'), ['class' => 'form-control', 'min' => '1', 'max' => '365']) }}
                    <small class="form-text text-muted">{{ __('Days to keep OEE history data') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {{ Form::label('production_oee_minimum', __('OEE Minimum (%)'), ['class' => 'form-label']) }}
                    {{ Form::number('production_oee_minimum', env('PRODUCTION_OEE_MINIMUM', '60'), ['class' => 'form-control', 'min' => '0', 'max' => '100']) }}
                    <small class="form-text text-muted">{{ __('Minimum OEE percentage for alerts') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('order_min_active_seconds', __('Min Active Order Seconds'), ['class' => 'form-label']) }}
                    {{ Form::number('order_min_active_seconds', env('ORDER_MIN_ACTIVE_SECONDS', '60'), ['class' => 'form-control', 'min' => '10', 'max' => '3600']) }}
                    <small class="form-text text-muted">{{ __('Minimum seconds for order to be considered active') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="form-check form-switch">
                        @php
                            $filterNotReady = env('PRODUCTION_FILTER_NOT_READY_KANBAN', 'true');
                            $isFilterNotReadyChecked = ($filterNotReady === 'true' || $filterNotReady === true);
                        @endphp
                        {{ Form::checkbox('production_filter_not_ready_kanban', '1', $isFilterNotReadyChecked, ['class' => 'form-check-input', 'id' => 'production_filter_not_ready_kanban']) }}
                        {{ Form::label('production_filter_not_ready_kanban', __('Filter Not Ready in Kanban'), ['class' => 'form-check-label']) }}
                    </div>
                    <small class="form-text text-muted">{{ __('Hide not-ready items in Kanban view') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-whitesmoke">
        <span class="float-end">
            <button class="btn btn-primary" type="submit" id="save-advanced-production-btn">{{ __('Save Advanced Production Settings') }}</button>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary me-1">{{ __('Cancel') }}</a>
        </span>
    </div>
    {{ Form::close() }}
</div>
```

#### Controlador: Método en SettingController

```php
/**
 * Guarda la configuración avanzada de producción.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function saveAdvancedProductionSettings(Request $request)
{
    $request->validate([
        'production_break_time' => 'required|integer|min:0|max:120',
        'production_oee_history_days' => 'required|integer|min:1|max:365',
        'production_oee_minimum' => 'required|integer|min:0|max:100',
        'order_min_active_seconds' => 'required|integer|min:10|max:3600',
    ]);

    // Actualizar el archivo .env con las nuevas configuraciones
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

### 2.4. AI Configuration

#### Vista: resources/views/settings/partials/ai-configuration.blade.php

```blade.php
{{-- Sección: AI Configuration --}}
<div id="useradd-3-12" class="card mb-4">
    {{ Form::open(['route' => 'settings.ai', 'method' => 'post']) }}
    <div class="card-header">
        <h5>{{ __('AI Configuration') }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            {{ __('Configure AI services for intelligent processing and automation.') }}
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('ai_url', __('AI Service URL'), ['class' => 'form-label']) }}
                    {{ Form::url('ai_url', env('AI_URL'), ['class' => 'form-control', 'placeholder' => 'https://api.openai.com/v1']) }}
                    <small class="form-text text-muted">{{ __('URL of the AI service endpoint') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('ai_token', __('AI Token'), ['class' => 'form-label']) }}
                    <div class="input-group">
                        {{ Form::password('ai_token', env('AI_TOKEN'), ['class' => 'form-control', 'placeholder' => 'sk-...']) }}
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">{{ __('Authentication token for AI service') }}</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="button" class="btn btn-info" id="test-ai-connection">
                    <i class="fas fa-plug"></i> {{ __('Test AI Connection') }}
                </button>
                <div id="ai-connection-status" class="mt-3"></div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-whitesmoke">
        <span class="float-end">
            <button class="btn btn-primary" type="submit" id="save-ai-btn">{{ __('Save AI Settings') }}</button>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary me-1">{{ __('Cancel') }}</a>
        </span>
    </div>
    {{ Form::close() }}
</div>
```

#### Controlador: Métodos en SettingController

```php
/**
 * Guarda la configuración de IA.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function saveAiSettings(Request $request)
{
    $request->validate([
        'ai_url' => 'required|url',
        'ai_token' => 'required|string|max:255',
    ]);

    // Actualizar el archivo .env con las nuevas configuraciones
    $arrEnv = [
        'AI_URL' => $request->ai_url,
        'AI_TOKEN' => $request->ai_token,
    ];
    
    UtilityFacades::setEnvironmentValue($arrEnv);

    return redirect()->back()->with('success', __('AI settings updated successfully.'));
}

/**
 * Prueba la conexión al servicio AI.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function testAiConnection(Request $request)
{
    try {
        $client = new \GuzzleHttp\Client([
            'timeout' => 10,
            'verify' => false,
        ]);

        $response = $client->post($request->ai_url ?? env('AI_URL'), [
            'headers' => [
                'Authorization' => 'Bearer ' . ($request->ai_token ?? env('AI_TOKEN')),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello, this is a connection test.']
                ],
                'max_tokens' => 10
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return response()->json([
                'success' => true,
                'message' => __('AI connection successful.')
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('AI service returned error: ') . $response->getStatusCode()
            ], $response->getStatusCode());
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('AI connection failed: ') . $e->getMessage()
        ], 500);
    }
}
```

## 3. Actualización de Rutas

### Actualización: routes/web.php

```php
// Añadir al grupo de rutas existente (después de la línea 614)

// AWS Configuration
Route::post('settings/aws', [SettingController::class, 'saveAwsSettings'])->name('settings.aws');
Route::post('settings/test-aws-connection', [SettingController::class, 'testAwsConnection'])->name('settings.test-aws-connection');

// PUSHER Configuration
Route::post('settings/pusher', [SettingController::class, 'savePusherSettings'])->name('settings.pusher');
Route::post('settings/test-pusher-connection', [SettingController::class, 'testPusherConnection'])->name('settings.test-pusher-connection');

// Advanced Production Settings
Route::post('settings/advanced-production', [SettingController::class, 'saveAdvancedProductionSettings'])->name('settings.advanced-production');

// AI Configuration
Route::post('settings/ai', [SettingController::class, 'saveAiSettings'])->name('settings.ai');
Route::post('settings/test-ai-connection', [SettingController::class, 'testAiConnection'])->name('settings.test-ai-connection');
```

## 4. Actualización de la Vista Principal

### Actualización: resources/views/settings/setting.blade.php

```blade.php
{{-- Actualizar el menú lateral (después de la línea 61) --}}

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

{{-- Añadir las nuevas secciones (después de la línea 979) --}}

@include('settings.partials.aws-configuration')
@include('settings.partials.pusher-configuration')
@include('settings.partials.advanced-production')
@include('settings.partials.ai-configuration')
```

## 5. JavaScript Adicional

### Actualización: resources/views/settings/setting.blade.php (sección @push('scripts'))

```javascript
{{-- Añadir después de la línea 1350 --}}

// Test AWS Connection
$('#test-aws-connection').on('click', function() {
    const $btn = $(this);
    const $status = $('#aws-connection-status');
    
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Testing...") }}');
    $status.removeClass('text-success text-danger').html('');
    
    const formData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        aws_access_key_id: $('input[name="aws_access_key_id"]').val(),
        aws_secret_access_key: $('input[name="aws_secret_access_key"]').val(),
        aws_default_region: $('select[name="aws_default_region"]').val(),
        aws_bucket: $('input[name="aws_bucket"]').val(),
        aws_use_path_style_endpoint: $('input[name="aws_use_path_style_endpoint"]').is(':checked')
    };
    
    $.ajax({
        url: '/settings/test-aws-connection',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $status.html('<i class="fas fa-check-circle"></i> ' + response.message).addClass('text-success');
            } else {
                $status.html('<i class="fas fa-times-circle"></i> ' + response.message).addClass('text-danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Error testing AWS connection')).addClass('text-danger');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-plug"></i> {{ __("Test AWS Connection") }}');
        }
    });
});

// Test PUSHER Connection
$('#test-pusher-connection').on('click', function() {
    const $btn = $(this);
    const $status = $('#pusher-connection-status');
    
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Testing...") }}');
    $status.removeClass('text-success text-danger').html('');
    
    const formData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        pusher_app_id: $('input[name="pusher_app_id"]').val(),
        pusher_app_key: $('input[name="pusher_app_key"]').val(),
        pusher_app_secret: $('input[name="pusher_app_secret"]').val(),
        pusher_app_cluster: $('select[name="pusher_app_cluster"]').val()
    };
    
    $.ajax({
        url: '/settings/test-pusher-connection',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $status.html('<i class="fas fa-check-circle"></i> ' + response.message).addClass('text-success');
            } else {
                $status.html('<i class="fas fa-times-circle"></i> ' + response.message).addClass('text-danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Error testing PUSHER connection')).addClass('text-danger');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-plug"></i> {{ __("Test PUSHER Connection") }}');
        }
    });
});

// Test AI Connection
$('#test-ai-connection').on('click', function() {
    const $btn = $(this);
    const $status = $('#ai-connection-status');
    
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Testing...") }}');
    $status.removeClass('text-success text-danger').html('');
    
    const formData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        ai_url: $('input[name="ai_url"]').val(),
        ai_token: $('input[name="ai_token"]').val()
    };
    
    $.ajax({
        url: '/settings/test-ai-connection',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $status.html('<i class="fas fa-check-circle"></i> ' + response.message).addClass('text-success');
            } else {
                $status.html('<i class="fas fa-times-circle"></i> ' + response.message).addClass('text-danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Error testing AI connection')).addClass('text-danger');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-plug"></i> {{ __("Test AI Connection") }}');
        }
    });
});

// Actualizar eventos de menú para nuevas secciones
$(document).on("click", ".useradd-3-9", function(){
    $(".useradd-3-9").addClass("active");
    $(".useradd-1, .useradd-2, .useradd-3, .useradd-4, .useradd-3-5, .useradd-3-6, .useradd-3-7, .useradd-3-8, .useradd-3-10, .useradd-3-11, .useradd-3-12").removeClass("active");
});

$(document).on("click", ".useradd-3-10", function(){
    $(".useradd-3-10").addClass("active");
    $(".useradd-1, .useradd-2, .useradd-3, .useradd-4, .useradd-3-5, .useradd-3-6, .useradd-3-7, .useradd-3-8, .useradd-3-9, .useradd-3-11, .useradd-3-12").removeClass("active");
});

$(document).on("click", ".useradd-3-11", function(){
    $(".useradd-3-11").addClass("active");
    $(".useradd-1, .useradd-2, .useradd-3, .useradd-4, .useradd-3-5, .useradd-3-6, .useradd-3-7, .useradd-3-8, .useradd-3-9, .useradd-3-10, .useradd-3-12").removeClass("active");
});

$(document).on("click", ".useradd-3-12", function(){
    $(".useradd-3-12").addClass("active");
    $(".useradd-1, .useradd-2, .useradd-3, .useradd-4, .useradd-3-5, .useradd-3-6, .useradd-3-7, .useradd-3-8, .useradd-3-9, .useradd-3-10, .useradd-3-11").removeClass("active");
});
```

## 6. Archivos de Traducción

### Actualización: resources/lang/es.json (añadir entradas)

```json
{
    "AWS Configuration": "Configuración AWS",
    "PUSHER Configuration": "Configuración PUSHER",
    "Advanced Production": "Producción Avanzada",
    "AI Configuration": "Configuración IA",
    "AWS Access Key ID": "ID de Clave de Acceso AWS",
    "AWS Secret Access Key": "Clave Secreta de Acceso AWS",
    "AWS Default Region": "Región por Defecto AWS",
    "AWS S3 Bucket": "Bucket S3 de AWS",
    "Use Path Style Endpoint": "Usar Endpoint Style Path",
    "PUSHER App ID": "ID de App PUSHER",
    "PUSHER App Key": "Clave de App PUSHER",
    "PUSHER App Secret": "Secreto de App PUSHER",
    "PUSHER App Cluster": "Cluster de App PUSHER",
    "Process Orders Out of Stock": "Procesar Órdenes sin Stock",
    "Create All Process Orders": "Crear Todas las Órdenes de Proceso",
    "Break Time (minutes)": "Tiempo de Descanso (minutos)",
    "OEE History Days": "Días de Historial OEE",
    "OEE Minimum (%)": "Mínimo OEE (%)",
    "Min Active Order Seconds": "Segundos Mínimos de Orden Activa",
    "Filter Not Ready in Kanban": "Filtrar No Listos en Kanban",
    "AI Service URL": "URL del Servicio IA",
    "AI Token": "Token IA",
    "Test AWS Connection": "Probar Conexión AWS",
    "Test PUSHER Connection": "Probar Conexión PUSHER",
    "Test AI Connection": "Probar Conexión IA",
    "Save AWS Settings": "Guardar Configuración AWS",
    "Save PUSHER Settings": "Guardar Configuración PUSHER",
    "Save Advanced Production Settings": "Guardar Configuración Avanzada de Producción",
    "Save AI Settings": "Guardar Configuración IA"
}
```

## 7. Dependencias Requeridas

### Instalación de Paquetes

```bash
# Para PUSHER
composer require pusher/pusher-php-server

# Para AWS S3
composer require league/flysystem-aws-s3-v3

# Para pruebas de conexión (si no está instalado)
composer require guzzlehttp/guzzle
```

## 8. Pruebas de Implementación

### Script de Prueba

```php
// tests/Feature/SettingsTest.php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_save_aws_settings()
    {
        $response = $this->post('/settings/aws', [
            'aws_access_key_id' => 'test-key-id',
            'aws_secret_access_key' => 'test-secret-key',
            'aws_default_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'name' => 'aws_access_key_id',
            'value' => 'test-key-id',
        ]);
    }

    /** @test */
    public function it_can_test_aws_connection()
    {
        $response = $this->post('/settings/test-aws-connection', [
            'aws_access_key_id' => 'invalid-key',
            'aws_secret_access_key' => 'invalid-secret',
            'aws_default_region' => 'us-east-1',
        ]);

        $response->assertJsonStructure(['success', 'message']);
    }

    // Más pruebas para otras secciones...
}
```

## 9. Plan de Implementación

### Fase 1: Preparación (1 día)
1. Backup del sistema actual
2. Instalación de dependencias
3. Creación de estructura de archivos

### Fase 2: Implementación (3-4 días)
1. Crear vistas parciales
2. Implementar métodos del controlador
3. Actualizar rutas
4. Integrar en vista principal

### Fase 3: Testing (2 días)
1. Probar funcionalidad básica
2. Probar validaciones
3. Probar pruebas de conexión
4. Corregir errores

### Fase 4: Despliegue (1 día)
1. Revisión de código
2. Documentación
3. Despliegue en producción
4. Monitoreo post-despliegue

## 10. Consideraciones de Seguridad

1. **Validación de Entrada**: Todos los datos deben ser validados
2. **Sanitización**: Limpiar datos antes de guardar
3. **Permisos**: Verificar que solo usuarios autorizados puedan acceder
4. **Auditoría**: Registrar cambios de configuración sensible
5. **HTTPS**: Forzar HTTPS para todas las páginas de configuración

## 11. Monitoreo y Mantenimiento

1. **Logs**: Implementar logs detallados de cambios
2. **Alertas**: Notificar cambios críticos
3. **Backup**: Backup automático de configuraciones
4. **Validación**: Verificación periódica de configuraciones

Esta implementación completa proporciona una solución robusta y escalable para gestionar todas las configuraciones del sistema a través de la interfaz web, eliminando la necesidad de acceso directo al servidor para configuraciones comunes.

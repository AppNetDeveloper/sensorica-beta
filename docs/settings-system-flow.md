# Diagrama de Flujo del Sistema de Configuración

## Flujo General del Sistema

```mermaid
graph TD
    A[Usuario accede a /settings] --> B{Autenticado?}
    B -->|No| C[Redirección a Login]
    B -->|Sí| D[SettingController@index]
    
    D --> E[Cargar configuraciones]
    E --> F[UtilityFacades::settings]
    F --> G[Consultar tabla settings]
    G --> H[Aplicar valores por defecto]
    H --> I[Renderizar vista settings/setting.blade.php]
    
    I --> J[Mostrar menú lateral]
    J --> K[Usuario selecciona sección]
    
    K --> L{Tipo de configuración}
    L -->|App Settings| M[Formulario de logos y nombre]
    L -->|General| N[Formulario de configuración general]
    L -->|RFID| O[Formulario de configuración RFID]
    L -->|Redis| P[Formulario de configuración Redis]
    L -->|BD Réplica| Q[Formulario de BD réplica]
    L -->|Upload Stats| R[Formulario de Upload Stats]
    L -->|Email| S[Formulario de email]
    L -->|Finish Shift| T[Formulario de emails de turno]
    
    M --> U[Usuario guarda cambios]
    N --> U
    O --> U
    P --> U
    Q --> U
    R --> U
    S --> U
    T --> U
    
    U --> V{Validación}
    V -->|Error| W[Mostrar errores de validación]
    V -->|OK| X[Procesar guardado]
    
    X --> Y{Tipo de almacenamiento}
    Y -->|Base de Datos| Z[DB::insert ON DUPLICATE KEY]
    Y -->|Archivo .env| AA[UtilityFacades::setEnvironmentValue]
    
    Z --> BB[Actualizar tabla settings]
    AA --> CC[Modificar archivo .env]
    
    BB --> DD[Limpiar caché de configuración]
    CC --> DD
    
    DD --> EE[Redirigir con mensaje de éxito]
    W --> FF[Volver al formulario]
```

## Flujo de Carga de Configuración

```mermaid
sequenceDiagram
    participant U as Usuario
    participant C as SettingController
    participant F as UtilityFacades
    participant DB as Base de Datos
    participant ENV as Archivo .env
    
    U->>C: GET /settings
    C->>F: settings()
    F->>DB: SELECT * FROM settings WHERE created_by = ?
    DB-->>F: Resultados de configuración
    F->>F: Aplicar valores por defecto
    F-->>C: Array de configuraciones
    C->>C: Cargar variables de entorno
    C-->>U: Vista renderizada con configuraciones
```

## Flujo de Guardado de Configuración

```mermaid
sequenceDiagram
    participant U as Usuario
    participant C as SettingController
    participant V as Validador
    participant DB as Base de Datos
    participant ENV as Archivo .env
    participant A as Artisan
    
    U->>C: POST configuración
    C->>V: validate($request)
    V-->>C: Datos validados
    C->>C: Separar configuraciones
    
    alt Configuraciones de BD
        C->>DB: INSERT ON DUPLICATE KEY UPDATE
        DB-->>C: Confirmación
    else Configuraciones de .env
        C->>ENV: UtilityFacades::setEnvironmentValue()
        ENV->>ENV: Leer archivo
        ENV->>ENV: Reemplazar/modificar líneas
        ENV->>ENV: Guardar archivo
        ENV-->>C: Confirmación
    end
    
    C->>A: config:clear
    C->>A: config:cache
    C-->>U: Respuesta con mensaje de éxito
```

## Flujo de Modificación del Archivo .env

```mermaid
flowchart TD
    A[setEnvironmentValue(array)] --> B[Leer archivo .env]
    B --> C[Para cada clave-valor]
    C --> D[Buscar línea existente]
    D --> E{¿Existe la clave?}
    E -->|Sí| F[Reemplazar línea existente]
    E -->|No| G[Agregar nueva línea]
    F --> H[Siguiente clave]
    G --> H
    H --> I{¿Más claves?}
    I -->|Sí| C
    I -->|No| J[Asegurar salto de línea final]
    J --> K[Guardar archivo]
    K --> L{¿Guardado exitoso?}
    L -->|Sí| M[Retornar true]
    L -->|No| N[Retornar false]
```

## Flujo de Prueba de Conexión (BD Réplica)

```mermaid
sequenceDiagram
    participant U as Usuario
    participant C as SettingController
    participant DB as Base de Datos
    participant R as Base de Datos Réplica
    
    U->>C: POST /settings/test-replica-db-connection
    C->>C: Configurar conexión temporal
    C->>R: Intentar conexión PDO
    alt Conexión exitosa
        R-->>C: PDO connection
        C->>R: SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA
        R-->>C: Lista de bases de datos
        C->>C: Verificar si existe BD específica
        C-->>U: JSON con success: true, database_exists: boolean
    else Error de conexión
        R-->>C: Exception
        C-->>U: JSON con success: false, message: error
    end
```

## Flujo de Navegación en la Interfaz

```mermaid
stateDiagram-v2
    [*] --> AppSettings: Carga inicial
    AppSettings --> General: Click menú
    AppSettings --> RFID: Click menú
    AppSettings --> Redis: Click menú
    AppSettings --> BDReplica: Click menú
    AppSettings --> UploadStats: Click menú
    AppSettings --> Email: Click menú
    AppSettings --> FinishShift: Click menú
    
    General --> AppSettings: Click menú
    RFID --> AppSettings: Click menú
    Redis --> AppSettings: Click menú
    BDReplica --> AppSettings: Click menú
    UploadStats --> AppSettings: Click menú
    Email --> AppSettings: Click menú
    FinishShift --> AppSettings: Click menú
    
    state AppSettings {
        [*] --> MostrarFormulario
        MostrarFormulario --> EsperarAcción
        EsperarAcción --> ValidarDatos: Submit
        ValidarDatos --> GuardarConfiguración: OK
        ValidarDatos --> MostrarErrores: Error
        GuardarConfiguración --> MostrarMensaje: Éxito
        MostrarMensaje --> [*]
        MostrarErrores --> EsperarAcción
    }
```

## Flujo de Gestión de Cache

```mermaid
graph LR
    A[Modificación de configuración] --> B{Tipo de cambio}
    B -->|Base de Datos| C[Invalidar cache de settings]
    B -->|Archivo .env| D[Invalidar cache de config]
    C --> E[Artisan config:clear]
    D --> E
    E --> F[Artisan config:cache]
    F --> G[Recargar configuración]
    G --> H[Aplicar cambios inmediatos]
```

## Flujo de Seguridad

```mermaid
graph TD
    A[Acceso a /settings] --> B[Middleware auth]
    B --> C{¿Autenticado?}
    C -->|No| D[Redirección a login]
    C -->|Sí| E[Middleware XSS]
    E --> F[Sanitización de input]
    F --> G[Validación de datos]
    G --> H{¿Datos válidos?}
    H -->|No| I[Retornar errores]
    H -->|Sí| J[Procesar solicitud]
    J --> K{¿Configuración sensible?}
    K -->|Sí| L[Guardar en .env]
    K -->|No| M[Guardar en BD]
    L --> N[Log de cambios]
    M --> N
    N --> O[Respuesta segura]
```

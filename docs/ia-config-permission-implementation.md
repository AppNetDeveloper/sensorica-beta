# Implementación de Permiso Específico para Configuración de IA

## Resumen
Se ha implementado un sistema de permisos más granular para la configuración de IA, reemplazando el middleware `role:admin` por un permiso específico `ia-config.update`.

## Cambios Realizados

### 1. Creación del Seeder de Permisos
- **Archivo**: `database/seeders/IaConfigPermissionsSeeder.php`
- **Descripción**: Nuevo seeder que crea el permiso `ia-config.update` y lo asigna automáticamente al rol `admin`.
- **Patón seguido**: Se ha seguido el mismo patrón que otros seeders de permisos del proyecto como `ProductionOrderCallbackPermissionsSeeder` y `FleetPermissionsSeeder`.

### 2. Modificación del DatabaseSeeder
- **Archivo**: `database/seeders/DatabaseSeeder.php`
- **Cambios**: Se ha agregado `IaConfigPermissionsSeeder::class` a la lista de seeders que se ejecutan automáticamente.
- **Ubicación**: Línea 97, después de `IaPromptsTableSeeder::class`.

### 3. Actualización del Controlador
- **Archivo**: `app/Http/Controllers/AiConfigController.php`
- **Cambios**: Se ha modificado el middleware en el constructor:
  - Antes: `$this->middleware('role:admin')->only(['update']);`
  - Después: `$this->middleware('permission:ia-config.update')->only(['update']);`
- **Impacto**: Ahora solo usuarios con el permiso específico `ia-config.update` pueden actualizar la configuración de IA.

## Verificación

### Ejecución del Seeder
```bash
php artisan db:seed --class=IaConfigPermissionsSeeder --force
```
**Resultado**: ✓ IA Config permissions seeded successfully

### Verificación del Permiso
```bash
php artisan tinker --execute="echo 'Permiso ia-config.update: '; var_dump(\Spatie\Permission\Models\Permission::where('name', 'ia-config.update')->exists());"
```
**Resultado**: bool(true)

### Verificación de Asignación al Rol Admin
```bash
php artisan tinker --execute="echo 'Rol admin tiene permiso ia-config.update: '; var_dump(\Spatie\Permission\Models\Role::where('name', 'admin')->first()->hasPermissionTo('ia-config.update'));"
```
**Resultado**: bool(true)

## Instrucciones de Uso

### Para ejecutar todos los seeders (incluyendo el nuevo):
```bash
php artisan db:seed --force
```

### Para ejecutar solo el seeder de permisos de IA:
```bash
php artisan db:seed --class=IaConfigPermissionsSeeder --force
```

### Para asignar el permiso a otros roles:
```php
// En un seeder o código
$role = Role::where('name', 'nombre_del_rol')->first();
$role->givePermissionTo('ia-config.update');
```

## Beneficios

1. **Granularidad**: Ahora es posible asignar permisos de configuración de IA sin necesidad de dar todos los permisos de administrador.
2. **Seguridad**: Mayor control sobre quién puede modificar la configuración crítica de IA.
3. **Flexibilidad**: Permite crear roles personalizados con permisos específicos para la configuración de IA.
4. **Consistencia**: Sigue el patrón establecido en el proyecto para la gestión de permisos.

## Notas Importantes

- El permiso se aplica únicamente al método `update()` del controlador, manteniendo el acceso público al método `index()` (solo requiere autenticación).
- El rol `admin` tiene asignado automáticamente este permiso al ejecutar el seeder.
- No se requiere modificar las rutas existentes ya que el controlador maneja la autorización internamente.
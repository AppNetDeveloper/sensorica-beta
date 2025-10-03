# Seeders para Activos/Almacén

Se han creado varios seeders para facilitar la creación de activos de prueba:

## 1. SimpleAssetSeeder
Crea activos básicos de prueba con diferentes estados y campos personalizados mínimos.

**Uso:**
```bash
php artisan db:seed --class=SimpleAssetSeeder
# o usando el comando personalizado:
php artisan seed:assets simple
```

## 2. AssetSeeder
Crea un conjunto completo de datos incluyendo:
- Categorías de activos
- Centros de costo
- Ubicaciones
- Activos detallados con campos personalizados

**Uso:**
```bash
php artisan db:seed --class=AssetSeeder
# o usando el comando personalizado:
php artisan seed:assets full
```

## 3. AssetCustomFieldsSeeder
Agrega campos personalizados adicionales a activos existentes usando los campos `attributes` y `metadata`.

**Uso:**
```bash
php artisan db:seed --class=AssetCustomFieldsSeeder
# o usando el comando personalizado:
php artisan seed:assets custom-fields
```

## 4. InventarioSeeder
Crea un inventario completo y realista con categorías específicas de gestión de stock:

**Características:**
- 5 categorías específicas de inventario
- 3 centros de costo especializados
- 4 ubicaciones diferenciadas
- 10 activos detallados simulando un inventario real

**Categorías creadas:**
- Materias Primas (aluminio, PVC, aceites)
- Productos Semielaborados (perfiles extrusionados)
- Productos Terminados (ventanas, puertas, fachadas)
- Envases y Embalajes (cajas, embalajes)
- Repuestos y Consumibles (rodamientos, filamentos)

**Datos incluidos:**
- Información técnica detallada
- Datos de proveedores y precios
- Control de stock (mínimos, máximos, disponibles)
- Fechas de caducidad y próximos pedidos
- Información de calidad y certificaciones

**Uso:**
```bash
php artisan db:seed --class=InventarioSeeder
# o usando el comando personalizado:
php artisan seed:assets inventario
```

## Ejemplos de activos creados:
- **Aluminio en lingotes** con pureza 99.7%
- **Ventanas correderas** con vidrio doble y certificación
- **Rodamientos industriales** con especificaciones técnicas
- **Filamento para impresión 3D** con datos de consumo
- **Cajas de embalaje** con especificaciones de capacidad

Este seeder es ideal para demostrar funcionalidades de gestión de inventario real en una empresa.

## Comando personalizado
También se creó un comando personalizado para facilitar el uso:

```bash
php artisan seed:assets [tipo]

# Tipos disponibles:
# - simple: Activos básicos de prueba
# - full: Todo completo (categorías + activos generales)
# - inventario: Inventario completo con datos realistas
# - custom-fields: Solo campos adicionales a activos existentes
```

## Campos personalizados incluidos

Los activos creados incluyen campos personalizados en `attributes` y `metadata`:

### Attributes (técnicos):
- Marca, modelo, especificaciones técnicas
- Estado actual, mantenimiento requerido
- Configuración y ajustes
- Información de seguridad

### Metadata (administrativos):
- Información de compra (precio, proveedor, garantía)
- Información de mantenimiento (fechas, técnicos)
- Información de usuarios (asignación, departamento)
- Información financiera (costos, depreciación)

## Estados disponibles
- `active`: Activo disponible
- `inactive`: Inactivo/temporalmente fuera de servicio
- `maintenance`: En mantenimiento

## Uso en desarrollo
Estos seeders son ideales para:
- Probar la funcionalidad de activos
- Desarrollar nuevas características
- Hacer demostraciones
- Testing de la aplicación

**Nota**: Los seeders están diseñados para crear datos de prueba y no afectar datos reales de producción.

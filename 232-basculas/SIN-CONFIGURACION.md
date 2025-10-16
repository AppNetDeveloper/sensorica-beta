# 🛡️ Modo Sin Configuración

El servicio `232-basculas-rs232` está diseñado para **no generar errores** cuando no se requiere en un cliente específico.

---

## 🎯 Problema Resuelto

En un entorno con múltiples clientes donde el archivo `.conf` de Supervisor se distribuye a todos, no todos los clientes necesitan el servicio de básculas RS232.

**Sin esta protección:**
- ❌ El servicio falla al no encontrar `.env`
- ❌ Supervisor intenta reiniciarlo constantemente
- ❌ Genera errores en los logs
- ❌ Estado "FATAL" en `supervisorctl status`

**Con esta protección:**
- ✅ El servicio se inicia correctamente
- ✅ Muestra un mensaje informativo
- ✅ Permanece activo sin hacer nada
- ✅ Estado "RUNNING" en Supervisor
- ✅ Sin errores ni intentos de reinicio

---

## 📊 Comportamiento

### Si `.env` NO existe:

```
═══════════════════════════════════════════════════════════════
  ℹ️  SERVICIO 232-BASCULAS - SIN CONFIGURACIÓN
═══════════════════════════════════════════════════════════════

📋 No se encontró el archivo .env

Este cliente no requiere el servicio de básculas RS232.
El proceso permanecerá activo sin realizar ninguna acción.

Si deseas activar el servicio de básculas:
  1. Copia el archivo: cp env.template .env
  2. Edita la configuración: nano .env
  3. Reinicia el servicio: sudo supervisorctl restart 232-basculas-rs232

═══════════════════════════════════════════════════════════════
```

El proceso se mantiene vivo indefinidamente sin consumir recursos significativos.

### Si `.env` SÍ existe:

El servicio funciona normalmente:
- ✅ Lee la configuración
- ✅ Conecta al puerto RS232
- ✅ Publica en MQTT
- ✅ Muestra logs de debug

---

## 🔧 Para Activar el Servicio

Si inicialmente no tenías `.env` y ahora quieres activar el servicio:

```bash
cd /var/www/html/232-basculas

# 1. Crear configuración desde la plantilla
cp env.template .env

# 2. Editar con tu configuración
nano .env

# 3. Reiniciar el servicio
sudo supervisorctl restart 232-basculas-rs232

# 4. Verificar que funciona
sudo supervisorctl status 232-basculas-rs232
sudo tail -f /var/www/html/storage/logs/232-basculas-rs232.log
```

---

## 🧪 Probar el Comportamiento

```bash
cd /var/www/html/232-basculas
./test-sin-env.sh
```

---

## ✅ Ventajas

- ✅ **Despliegue universal**: El mismo `.conf` funciona en todos los clientes
- ✅ **Sin errores**: No genera fallos en Supervisor
- ✅ **Fácil activación**: Solo copiar `.env` y reiniciar
- ✅ **Logs limpios**: Mensaje claro de por qué no está activo
- ✅ **Estado correcto**: Muestra "RUNNING" en lugar de "FATAL"

---

## 📋 Estado en Supervisor

### Cliente SIN báscula:
```bash
$ sudo supervisorctl status 232-basculas-rs232
232-basculas-rs232:232-basculas-rs232_00   RUNNING   pid 12345, uptime 1:23:45
```

### Cliente CON báscula:
```bash
$ sudo supervisorctl status 232-basculas-rs232
232-basculas-rs232:232-basculas-rs232_00   RUNNING   pid 67890, uptime 2:34:56
```

Ambos muestran "RUNNING" ✅

---

## 🔍 Verificar en Logs

```bash
# Ver el mensaje de "sin configuración"
sudo cat /var/www/html/storage/logs/232-basculas-rs232.log

# Ver logs en tiempo real
sudo tail -f /var/www/html/storage/logs/232-basculas-rs232.log
```

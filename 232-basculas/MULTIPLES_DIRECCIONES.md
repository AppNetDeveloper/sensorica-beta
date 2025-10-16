# 📡 Sistema de Múltiples Direcciones

El sistema ahora soporta monitorizar **hasta 10 básculas simultáneamente** (direcciones 1-10) a través de un solo puerto RS232.

---

## 🎯 Configuración

### `.env`

```env
# ===== DIRECCIONES A MONITORIZAR =====
RS232_ADDRESSES=1,2,3          # Monitoriza 3 básculas
RS232_ADDRESS_PREFIX=          # Sin prefijo (vacío por defecto)

# ===== CONFIGURACIÓN GLOBAL (valores por defecto) =====
RS232_OFFSET=0                 # Offset por defecto
RS232_TARA=0.0                 # Tara por defecto
RS232_SCALE=0.001              # Escala por defecto

# ===== CONFIGURACIÓN ESPECÍFICA POR DIRECCIÓN =====
# Cada dirección puede tener sus propios valores (sobrescribe los globales)

# Dirección 1:
RS232_OFFSET_1=760695          # Offset específico para báscula 1
RS232_TARA_1=0.300             # Tara de contenedor báscula 1
RS232_SCALE_1=0.001            # Escala báscula 1

# Dirección 2:
RS232_OFFSET_2=761200          # Offset específico para báscula 2
RS232_TARA_2=0.250             # Tara de contenedor báscula 2
RS232_SCALE_2=0.001            # Escala báscula 2

# Dirección 3:
RS232_OFFSET_3=760800          # Offset específico para báscula 3
RS232_TARA_3=0.350             # Tara de contenedor báscula 3
RS232_SCALE_3=0.001            # Escala báscula 3

# Si no especificas RS232_OFFSET_X, RS232_TARA_X o RS232_SCALE_X,
# se usarán los valores globales (RS232_OFFSET, RS232_TARA, RS232_SCALE)
```

---

## 🔄 Cómo funciona

### 1. **Polling Cíclico**
El sistema consulta automáticamente cada dirección configurada de forma cíclica:

```
Tiempo: 0ms    → Envía comando a dirección 1
Tiempo: 300ms  → Envía comando a dirección 2
Tiempo: 600ms  → Envía comando a dirección 3
Tiempo: 900ms  → Envía comando a dirección 1 (reinicia ciclo)
```

### 2. **Topics MQTT Separados**
Cada dirección publica en su propio topic:

```
sensorica/bascula/peso/address/1/smart_utilcell
sensorica/bascula/peso/address/2/smart_utilcell
sensorica/bascula/peso/address/3/smart_utilcell
...
sensorica/bascula/peso/address/10/smart_utilcell
```

### 3. **Tara Independiente**
Cada dirección tiene su propia tara almacenada internamente:

```javascript
RS232_TARA_BY_ADDRESS = {
  1: 0.300,  // Dirección 1: contenedor de 0.3 kg
  2: 0.250,  // Dirección 2: contenedor de 0.25 kg
  3: 0.000,  // Dirección 3: sin tara
  ...
}
```

---

## 📊 Logs de Debug

Los logs muestran claramente la dirección de cada báscula:

```
[DEBUG] ══════════════════════════ Dirección 1 ══════════════════════════
[DEBUG] Peso CRUDO:       1222168 (lectura báscula)
[DEBUG] Offset aplicado:  760695 (calibración a 0)
[DEBUG] Escala aplicada:  0.001
[DEBUG] Peso BRUTO:       0.461 kg (después de offset y escala)
[DEBUG] Tara actual:      0.300 kg
[DEBUG] Peso NETO calc:   0.161 kg
[DEBUG] Peso PUBLICADO:   0.161 kg (max(neto, 0))
[DEBUG] ═══════════════════════════════════════════════════════════════
[MQTT->PUBLICADO][Addr 1] Topic: sensorica/bascula/peso/address/1/smart_utilcell, Payload: {"value":0.161}

[DEBUG] ══════════════════════════ Dirección 2 ══════════════════════════
[DEBUG] Peso CRUDO:       980450 (lectura báscula)
[DEBUG] Offset aplicado:  760695 (calibración a 0)
[DEBUG] Escala aplicada:  0.001
[DEBUG] Peso BRUTO:       0.220 kg (después de offset y escala)
[DEBUG] Tara actual:      0.250 kg
[DEBUG] Peso NETO calc:   -0.030 kg
[DEBUG] Peso PUBLICADO:   0.000 kg (max(neto, 0))
[DEBUG] ═══════════════════════════════════════════════════════════════
[MQTT->PUBLICADO][Addr 2] Topic: sensorica/bascula/peso/address/2/smart_utilcell, Payload: {"value":0.000}
```

---

## 🚀 Ejemplos de Uso

### Ejemplo 1: Una sola báscula
```env
RS232_ADDRESSES=1
RS232_ADDRESS_PREFIX=
```

Publica en: `sensorica/bascula/peso/address/1/smart_utilcell`

---

### Ejemplo 2: Tres básculas
```env
RS232_ADDRESSES=1,2,3
RS232_ADDRESS_PREFIX=
```

Publica en:
- `sensorica/bascula/peso/address/1/smart_utilcell`
- `sensorica/bascula/peso/address/2/smart_utilcell`
- `sensorica/bascula/peso/address/3/smart_utilcell`

El sistema consultará cíclicamente: 1 → 2 → 3 → 1 → 2 → 3...

---

### Ejemplo 3: Todas las direcciones (1-10)
```env
RS232_ADDRESSES=1,2,3,4,5,6,7,8,9,10
RS232_ADDRESS_PREFIX=
```

Publica en 10 topics diferentes, uno por cada dirección.

Con `POLL_INTERVAL_MS=300`, cada báscula se consulta cada 3 segundos (10 direcciones × 300ms).

---

## ⚡ Auto-Tara por Dirección

El sistema de auto-tara funciona **independientemente para cada dirección**:

```
[AUTO-TARA][Addr 1] ✓ Peso bruto de contenedor estable: 0.300 kg durante 30s
[AUTO-TARA][Addr 1] ✓ Tara establecida: 0.300 kg

[AUTO-TARA][Addr 2] ✓ Peso bruto de contenedor estable: 0.250 kg durante 30s
[AUTO-TARA][Addr 2] ✓ Tara establecida: 0.250 kg
```

---

## 📋 Comandos MQTT por Dirección

### Resetear tara de dirección específica
```bash
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"address":1, "reset_tara":true}'
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"address":2, "reset_tara":true}'
```

### Establecer tara manual por dirección
```bash
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"address":1, "set_tara":0.5}'
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"address":2, "set_tara":0.3}'
```

---

## 🔧 Consideraciones de Rendimiento

### Tiempo de ciclo completo

Con `POLL_INTERVAL_MS=300` y `N` direcciones:
- **1 dirección**: consulta cada 300ms
- **3 direcciones**: cada dirección se consulta cada 900ms (300ms × 3)
- **10 direcciones**: cada dirección se consulta cada 3000ms (300ms × 10)

### Recomendaciones

- **1-3 básculas**: `POLL_INTERVAL_MS=300` (óptimo)
- **4-6 básculas**: `POLL_INTERVAL_MS=200` (más rápido)
- **7-10 básculas**: `POLL_INTERVAL_MS=100` (máxima velocidad)

---

## ✅ Sistema Listo

El sistema está completamente implementado y funcional para monitorizar hasta 10 básculas simultáneamente, cada una con:

- ✅ Su propio topic MQTT
- ✅ Su propia tara independiente
- ✅ Auto-tara inteligente por dirección
- ✅ Logs de debug detallados
- ✅ Protección contra negativos
- ✅ Polling cíclico automático

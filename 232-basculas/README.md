# Servicio RS232 para Báscula UTILCELL SMART

Sistema Node.js para lectura de peso por RS232 y publicación MQTT.

## ✅ Configuración detectada y funcionando

### Parámetros del puerto serie
```
Puerto: /dev/ttyUSB0
Baudios: 115200
Bits de datos: 8
Paridad: none
Bits de stop: 1
```

### Protocolo de comunicación
- **Comando**: `'A'` (petición de peso en formato F4)
- **Terminador**: `CR+LF` (0x0D 0x0A)
- **Factor de escala**: `0.1` (báscula envía en décimas, ej: 1224330 = 122433.0 kg)

### Respuesta de la báscula
```
Formato: " <peso><CR><LF>"
Ejemplo HEX: 20 31 32 32 34 33 33 30 0D 0A
Ejemplo ASCII: " 1224330\r\n"
Peso interpretado: 122433.0 kg
```

## Instalación

1. **Instalar dependencias**:
```bash
cd /var/www/html/232-basculas
chmod +x install.sh
./install.sh
```

2. **Configurar `.env`**:
```bash
# Copia el template como base
cp env.template .env

# Edita .env con tu configuración
nano .env
```

Variables principales (ver `env.template` completo):
```env
# Puerto y parámetros serie
SERIAL_PORT=/dev/ttyUSB0
BAUD_RATE=115200
DATA_BITS=8
STOP_BITS=1
PARITY=none

# Comando y terminadores (detectado: A + CR+LF)
RS232_COMMAND=A
RS232_APPEND_CR=true
RS232_APPEND_LF=true

# FACTOR DE ESCALA - DIVIDE PESO
# 0.1  = divide entre 10  (1224330 → 122433.0)
# 0.01 = divide entre 100 (1224330 → 12243.30)
RS232_SCALE=0.1

# DECIMALES PUBLICADOS EN MQTT
# 0 = sin decimales, 2 = dos decimales, etc. (máx 8)
# 'auto' = infiere según RS232_SCALE
RS232_DECIMALS=auto

# UMBRAL DE CERO (elimina ruido)
# Valores entre -0.01 y 0.01 se publican como 0
RS232_ZERO_THRESHOLD=0.01

# FILTRO DE VALORES REPETIDOS
# true = solo publica cuando el peso cambia
# false = publica siempre (cada POLL_INTERVAL_MS)
RS232_PUBLISH_UNCHANGED=false

# OFFSET - CALIBRACIÓN A 0
# Valor a restar del peso crudo ANTES de aplicar la escala
# Si sin carga lee 760000, configura RS232_OFFSET=760000
# Fórmula paso 1: peso_bruto = (peso_crudo - OFFSET) * SCALE
RS232_OFFSET=0

# TARA - PESO DE CONTENEDOR
# Valor en kg a restar DESPUÉS de escalar
# Se usa para ignorar el peso del contenedor/recipiente
# Fórmula paso 2: peso_neto = peso_bruto - TARA
RS232_TARA=0.0

# AUTO-TARA INTELIGENTE (desactivada por defecto)
# Si true, cuando detecta peso estable < threshold durante X segundos, hace tara automática
RS232_AUTO_TARA_ENABLED=false
RS232_AUTO_TARA_THRESHOLD=0.4  # Peso máximo para auto-tara (kg)
RS232_AUTO_TARA_TIME=30         # Tiempo de estabilidad (segundos)

# Intervalo de sondeo en milisegundos
POLL_INTERVAL_MS=300

# MQTT
MQTT_BROKER_URL=mqtt://localhost
MQTT_TOPIC_BASE=sensorica/bascula/peso
MQTT_TOPIC_TARA=sensorica/bascula/tara
MQTT_TOPIC_ZERO=sensorica/bascula/zero

# Logs detallados
LOG_VERBOSE=true
```

## Uso

### Ejecución en primer plano (desarrollo/pruebas)
```bash
node index.js
```

### Ejecución como servicio (producción con PM2)
```bash
# Iniciar
pm2 start index.js --name 232-basculas

# Guardar configuración para auto-inicio
pm2 save
pm2 startup

# Ver logs
pm2 logs 232-basculas

# Detener
pm2 stop 232-basculas

# Reiniciar
pm2 restart 232-basculas
```

## Tópicos MQTT

### Publicación de peso
- **Tópico**: `sensorica/bascula/peso/smart_utilcell`
- **Formato**: `{"value": 122433.0}`
- **Frecuencia**: cada 300ms (configurable con `POLL_INTERVAL_MS`)

### Comandos de control

#### TARA
- **Tópico**: `sensorica/bascula/tara/smart_utilcell`
- **Comando**: `{"value": true}`
- **Acción**: Ejecuta tara en la báscula (establece el peso actual como referencia)

Ejemplo:
```bash
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"value":true}'
```

#### CERO
- **Tópico**: `sensorica/bascula/zero/smart_utilcell`
- **Comando**: `{"value": true}`
- **Acción**: Ejecuta cero en la báscula (pone a cero cuando no hay peso)

Ejemplo:
```bash
mosquitto_pub -t 'sensorica/bascula/zero/smart_utilcell' -m '{"value":true}'
```

## Verificación

### Ver publicaciones MQTT (peso)
```bash
mosquitto_sub -t 'sensorica/bascula/peso/#' -v
```

Deberías ver:
```
sensorica/bascula/peso/smart_utilcell {"value":122433.0}
sensorica/bascula/peso/smart_utilcell {"value":122433.5}
...
```

### Probar comandos de control

#### Hacer TARA
```bash
# En otra terminal, publica comando de tara
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"value":true}'

# En los logs del servicio verás:
# [MQTT→RS232] Ejecutando TARA...
# [RS232] ✓ Comando TARA enviado
```

#### Hacer CERO
```bash
# Publica comando de cero
mosquitto_pub -t 'sensorica/bascula/zero/smart_utilcell' -m '{"value":true}'

# En los logs del servicio verás:
# [MQTT→RS232] Ejecutando CERO...
# [RS232] ✓ Comando CERO enviado
```

### Monitorear todos los topics
```bash
# Ver peso + comandos
mosquitto_sub -t 'sensorica/bascula/#' -v
```

## Autodetección

El sistema incluye un modo de autodetección que prueba automáticamente 16 combinaciones de comandos y terminadores:

```bash
# En .env o por CLI:
RS232_COMMAND=AUTO

# Ejecutar:
node index.js
```

El sistema probará:
- Comandos: A, P, $, SYN (0x16)
- Terminadores: ninguno, CR, LF, CRLF

Cuando detecte respuesta, mostrará la configuración válida y continuará usándola.

## Configuración del indicador UTILCELL SMART

El indicador debe estar configurado en su menú:

```
SERIAL 1 (o el puerto correspondiente):
├─ MODO (TYPE): DEMAND (de)       ← CRÍTICO
├─ BAUD: 115200
├─ PARIDAD (PAr): 8-none
├─ FORMATO (For): F4
└─ TERMINACIÓN (tEr): CR+LF
```

## Troubleshooting

### No llegan datos
1. Verifica que el indicador esté en modo **DEMAND**
2. Comprueba el cableado RS232:
   - TX del indicador → RX del adaptador (pin 2 ↔ pin 3)
   - RX del indicador → TX del adaptador (pin 3 ↔ pin 2)
   - GND común (pin 5 ↔ pin 5)
3. Verifica que el puerto sea correcto: `ls -l /dev/ttyUSB*`
4. Comprueba permisos: el usuario debe estar en el grupo `dialout`

### Peso incorrecto (factor de escala)
- Ajusta `RS232_SCALE` en `.env`
- Ejemplo: si ves 1224330 pero debería ser 1224.330, usa `RS232_SCALE=0.001`

---

## 🎯 Sistema de TARA (Peso de Contenedor)

### Diferencia entre OFFSET y TARA

**RS232_OFFSET** (calibración a 0):
- Se resta **antes** de escalar
- Calibra la báscula a 0 cuando no hay nada
- Ejemplo: `760695` (valor crudo sin carga)

**RS232_TARA** (peso de contenedor):
- Se resta **después** de escalar
- Ignora el peso del contenedor/recipiente
- Ejemplo: `0.350` kg (peso del contenedor)

**Fórmula completa:**
```javascript
peso_bruto = (peso_crudo - RS232_OFFSET) * RS232_SCALE
peso_neto_calculado = peso_bruto - RS232_TARA

// Para publicar MQTT (no muestra negativos):
peso_publicado = max(peso_neto_calculado, 0)
```

**Protección contra negativos:**
- Si `peso_bruto <= RS232_TARA` → publica `0` (no negativo para no marear al usuario)
- Ejemplo: Tara=0.3 kg, quitas contenedor (peso bruto=0.05 kg) → publica `0` ✅

---

### AUTO-AJUSTE INTELIGENTE (SISTEMA DE 3 RANGOS) ⚡

El sistema ajusta automáticamente OFFSET o TARA según el peso detectado y estable.

**Configuración:**
```env
RS232_AUTO_TARA_ENABLED=true          # Activar auto-ajuste inteligente
RS232_AUTO_OFFSET_MAX=0.05            # RANGO 1: Máximo para ajuste de offset
RS232_AUTO_TARA_MIN=0.05              # RANGO 2: Mínimo para tara
RS232_AUTO_TARA_MAX=0.4               # RANGO 2: Máximo para tara
RS232_AUTO_TARA_TIME=30               # Tiempo estable (segundos)
```

---

### 📊 Sistema de 3 Rangos

**⚡ Importante:** El sistema usa el **PESO BRUTO** (sin restar tara) para detectar los rangos.

#### **RANGO 1: [0 - 0.05 kg] → Ajusta OFFSET (calibración a 0)**
Si el **peso bruto** se mantiene estable en este rango durante 30s, ajusta el offset.

**Ejemplo:**
- Peso bruto: `0.03 kg` (sin nada encima)
- Después de 30s estable → Ajusta `RS232_OFFSET`
- Ahora peso bruto: `0.00 kg` ✅

**Logs:**
```
[AUTO-OFFSET] ✓ Peso bruto estable: 0.030 kg durante 30s
[AUTO-OFFSET] ✓ Offset ajustado: 760695 → 760725
```

---

#### **RANGO 2: [0.05 - 0.4 kg] → Establece TARA (peso contenedor)**
Si el **peso bruto** se mantiene estable en este rango durante 30s, establece como tara.

**⚠️ IMPORTANTE:** 
- Usa el **peso BRUTO** para detectar
- Si pones otro contenedor en este rango, **REEMPLAZA** la tara anterior (no suma)
- Si quitas el contenedor y peso bruto < tara → publica `0` (no negativo)

**Ejemplos:**

**Caso A: Primer contenedor**
- Pones contenedor de `0.3 kg`
- Después de 30s estable → `RS232_TARA = 0.3 kg`
- Ahora lee: `0.0 kg` ✅

**Caso B: Cambias de contenedor**
- Tara actual: `0.3 kg`
- Cambias a contenedor de `0.25 kg`
- Después de 30s estable → `RS232_TARA = 0.25 kg` (REEMPLAZA)
- Ahora lee: `0.0 kg` ✅

**Caso C: Añades más contenedores**
- Tara actual: `0.3 kg`
- Añades otro contenedor: peso bruto total `0.35 kg`
- Después de 30s estable → `RS232_TARA = 0.35 kg` (REEMPLAZA)
- Ahora lee: `0.0 kg` ✅

**Caso D: Quitas el contenedor (protección contra negativos)**
- Tara actual: `0.3 kg`
- Quitas el contenedor: peso bruto `0.05 kg`
- Peso neto sería: `0.05 - 0.3 = -0.25 kg`
- **Publica: `0.0 kg`** (no negativo para no marear al usuario) ✅

**Logs:**
```
[AUTO-TARA] ✓ Peso de contenedor estable: 0.300 kg durante 30s
[AUTO-TARA] ✓ Tara establecida: 0.300 kg
[AUTO-TARA] ℹ Si pones otro contenedor (0.05-0.4 kg), la tara se reemplazará
```

---

#### **RANGO 3: [> 0.4 kg] → Peso real (no hace ajustes)**
Si el peso supera 0.4 kg, se considera peso real de producto. No se hacen ajustes automáticos.

**Ejemplo:**
- Tara actual: `0.3 kg`
- Añades producto: peso bruto `1.5 kg`
- Peso neto publicado: `1.2 kg` (1.5 - 0.3)
- ✅ No se hace auto-tara porque supera el límite

---

### Comandos MQTT para TARA

**Resetear tara a 0:**
```bash
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"reset_tara":true}'
```

**Establecer tara manual (ej: 0.5 kg):**
```bash
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"set_tara":0.5}'
```

**Tara por hardware (comando a la báscula):**
```bash
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"value":true}'
```

---

### Báscula muestra peso cuando no hay carga (offset)
Si la báscula muestra ~760000 cuando debería mostrar 0:

**Opción 1: AUTO-CORRECCIÓN CONTINUA (automático, siempre activo) ✨**

El sistema se auto-corrige **automáticamente en tiempo real**:

**Si detecta pesos NEGATIVOS** (ej: -7.6 kg):
- Tras **50 lecturas negativas consecutivas** (~15 segundos a 300ms), ajusta el offset automáticamente
- Esto da seguridad: evita correcciones por ruido momentáneo
- Recalcula el peso inmediatamente
- Muestra: `[AUTO-CORRECT] ⚠ Peso negativo durante 15.0s (50 lecturas)`
- Muestra: `[AUTO-CORRECT] ✓ Offset ajustado: 761500 → 768500`

**Si detecta pesos MUY BAJOS** de forma consistente (0-10 kg durante 100 muestras ~30s):
- Ajusta el offset para llevar el peso más cerca de cero
- Útil para compensar deriva térmica o desajustes mínimos

**No necesitas hacer nada**, el sistema se ajusta solo mientras funciona.

---

**Opción 2: AUTO-CALIBRACIÓN al arrancar (por defecto) ✨**

El sistema también se auto-calibra automáticamente al arrancar:

```bash
# 1. Asegúrate de que NO haya carga en la báscula
# 2. Ejecuta el servicio:
node index.js

# El sistema mostrará:
# [AUTO-OFFSET] Recolectando muestra 1/10: 761255
# [AUTO-OFFSET] Recolectando muestra 2/10: 761258
# ...
# [AUTO-OFFSET] ✓ Offset calibrado automáticamente: 761255
# [AUTO-OFFSET] Fórmula aplicada: (peso_crudo - 761255) * 0.1
```

**Cómo funciona:**
- Toma las primeras 10 lecturas al arrancar
- Si todas están > 100000 (configurable), usa el promedio como offset
- Si hay carga baja (< 100000), omite la calibración

**Recalibrar en cualquier momento:**
```bash
# Retira toda la carga y publica:
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"calibrate":true}'

# El sistema tomará nuevas 10 muestras y recalibrará
```

**Configuración en .env:**
```env
RS232_AUTO_OFFSET=true                # Activar auto-calibración al arrancar
RS232_MIN_OFFSET=100000               # Mínimo para activar (si < 100000, no calibra)

# AUTO-CORRECCIÓN CONTINUA (ajuste de seguridad)
RS232_AUTO_CORRECT_THRESHOLD=50       # Lecturas negativas antes de corregir
                                      # 50 lecturas × 300ms = 15 segundos
                                      # Aumenta para más seguridad

RS232_LOW_WEIGHT_SAMPLES=100          # Muestras de peso bajo antes de ajustar
                                      # 100 lecturas × 300ms = 30 segundos
```

**Tabla de seguridad** (con POLL_INTERVAL_MS=300):

| Threshold | Tiempo | Uso recomendado |
|-----------|--------|-----------------|
| 10 | 3s | Entorno controlado, ajustes rápidos |
| 20 | 6s | Uso normal |
| **50** | **15s** | **Recomendado (por defecto)** ✓ |
| 100 | 30s | Máxima seguridad, entorno ruidoso |

---

**Opción 3: TARA por hardware**
```bash
# Ejecutar comando de tara via MQTT (comando nativo de la báscula)
mosquitto_pub -t 'sensorica/bascula/tara/smart_utilcell' -m '{"value":true}'
```

---

**Opción 4: TARA por software (offset manual)**
```bash
# Edita .env y añade el offset a restar
nano .env

# Añade esta línea (ejemplo con 760000):
RS232_OFFSET=760000
RS232_AUTO_OFFSET=false  # Desactivar auto-calibración

# Reinicia el servicio
pm2 restart 232-basculas  # o Ctrl+C y node index.js
```

**Fórmula aplicada:**
```
peso_final = (peso_crudo - RS232_OFFSET) * RS232_SCALE

Ejemplo con RS232_OFFSET=761255 y RS232_SCALE=0.1:
- Sin carga: (761255 - 761255) * 0.1 = 0.0 kg ✓
- Con 1000 kg: (771255 - 761255) * 0.1 = 1000.0 kg ✓
```

### Test de puerto serie
```bash
# Ver si el adaptador se detecta
dmesg | grep tty

# Test de loopback (conecta TX con RX físicamente)
echo "TEST" > /dev/ttyUSB0 & cat /dev/ttyUSB0
```

## Archivos

- `index.js` - Servicio principal con autodetección y soporte tara/cero
- `install.sh` - Script de instalación de dependencias
- `env.template` - Plantilla de configuración con todas las variables
- `.env` - Tu configuración (crear desde env.template, no incluido en git)
- `package.json` - Dependencias Node.js
- `README.md` - Esta documentación

## Dependencias

- `dotenv` - Variables de entorno
- `mqtt` - Cliente MQTT
- `serialport` - Comunicación RS232

## Logs

El servicio muestra:
- `[RS232] ← DATO CRUDO: HEX=[...] ASCII=[...]` - Bytes recibidos
- `[RS232] Línea cruda PROCESADA: ...` - Línea parseada
- `[RS232->MQTT] Publicado OK: ...` - Publicación MQTT exitosa
- `[AUTODETECT] ...` - Proceso de autodetección (si está activo)

## Creado por

Sistema desarrollado para lectura de báscula UTILCELL SMART por RS232 con publicación MQTT para integración con Sensorica.

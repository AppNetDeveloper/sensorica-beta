#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');

// Verificar si existe el archivo .env
const envPath = path.join(__dirname, '.env');
if (!fs.existsSync(envPath)) {
  console.log('');
  console.log('═══════════════════════════════════════════════════════════════');
  console.log('  ℹ️  SERVICIO 232-BASCULAS - SIN CONFIGURACIÓN');
  console.log('═══════════════════════════════════════════════════════════════');
  console.log('');
  console.log('📋 No se encontró el archivo .env');
  console.log('');
  console.log('Este cliente no requiere el servicio de básculas RS232.');
  console.log('El proceso permanecerá activo sin realizar ninguna acción.');
  console.log('');
  console.log('Si deseas activar el servicio de básculas:');
  console.log('  1. Copia el archivo: cp env.template .env');
  console.log('  2. Edita la configuración: nano .env');
  console.log('  3. Reinicia el servicio: sudo supervisorctl restart 232-basculas-rs232');
  console.log('');
  console.log('═══════════════════════════════════════════════════════════════');
  console.log('');
  
  // Mantener el proceso vivo sin hacer nada
  setInterval(() => {
    // Nada, solo mantener el proceso activo
  }, 60000); // Cada 60 segundos
  
  return; // No continuar con la ejecución
}

// Carga de variables de entorno desde .env
require('dotenv').config();
const mqtt = require('mqtt');
const { SerialPort, ReadlineParser } = require('serialport');

// --- Configuración desde el .env (o valores por defecto) ---
const SERIAL_PORT = process.env.SERIAL_PORT || '/dev/ttyS0';
// Parámetros de la báscula Utilcell SMART (115200, 8, N, 1)
const BAUD_RATE = parseInt(process.env.BAUD_RATE || '115200', 10);
const DATA_BITS = parseInt(process.env.DATA_BITS || '8', 10);
const STOP_BITS = parseInt(process.env.STOP_BITS || '1', 10);
const PARITY = (process.env.PARITY || 'none'); // 'none' | 'even' | 'odd'

// Parámetros de comunicación ASCII
const RS232_COMMAND = (process.env.RS232_COMMAND || 'AUTO'); // AUTO = autodetección
const RS232_DELIMITER = (process.env.RS232_DELIMITER || 'LF').toUpperCase(); // CRLF se lee con LF
const RS232_SCALE = parseFloat(process.env.RS232_SCALE || '0.1'); // Factor de escala: Utilcell envía en décimas (ej: 760917 = 76091.7)
const rawDecimalsEnv = (process.env.RS232_DECIMALS || 'auto').trim().toLowerCase();
const inferDecimalsFromScale = (scale) => {
  if (!Number.isFinite(scale) || scale <= 0) return 3;
  const decimals = Math.ceil(Math.abs(Math.log10(scale)));
  return Math.min(Math.max(decimals, 0), 8);
};
const RS232_DECIMALS = (() => {
  if (rawDecimalsEnv === '' || rawDecimalsEnv === 'auto') {
    return inferDecimalsFromScale(RS232_SCALE);
  }
  const parsed = Number.parseInt(rawDecimalsEnv, 10);
  if (Number.isNaN(parsed) || parsed < 0) return inferDecimalsFromScale(RS232_SCALE);
  return Math.min(parsed, 8);
})();
// Configuración global (por defecto para todas las direcciones)
let RS232_OFFSET = parseFloat(process.env.RS232_OFFSET || '0'); // Offset a restar antes de escalar (calibración a 0)
let RS232_TARA = parseFloat(process.env.RS232_TARA || '0'); // Tara (peso de contenedor) a restar después de escalar

// Configuración específica por dirección (sobrescribe la global si existe)
const RS232_OFFSET_BY_ADDRESS = {}; // { address: offset }
const RS232_SCALE_BY_ADDRESS = {};  // { address: scale }
const RS232_AUTO_OFFSET = /^(true|1)$/i.test(process.env.RS232_AUTO_OFFSET || 'true'); // Auto-calibración de offset
const RS232_MIN_OFFSET = parseFloat(process.env.RS232_MIN_OFFSET || '100000'); // Offset mínimo para activar auto-calibración
const RS232_AUTO_CORRECT_ENABLED = /^(true|1)$/i.test(process.env.RS232_AUTO_CORRECT_ENABLED || 'false'); // Activar auto-corrección continua
const RS232_AUTO_CORRECT_THRESHOLD = parseInt(process.env.RS232_AUTO_CORRECT_THRESHOLD || '50', 10); // Lecturas negativas antes de corregir
const RS232_LOW_WEIGHT_SAMPLES = parseInt(process.env.RS232_LOW_WEIGHT_SAMPLES || '100', 10); // Muestras de peso bajo antes de ajustar
const RS232_MAX_OFFSET_CHANGE = parseFloat(process.env.RS232_MAX_OFFSET_CHANGE || '50000'); // Cambio máximo de offset por corrección
const RS232_ZERO_THRESHOLD = parseFloat(process.env.RS232_ZERO_THRESHOLD || '0.01'); // Umbral para considerar peso como 0
const RS232_PUBLISH_UNCHANGED = /^(true|1)$/i.test(process.env.RS232_PUBLISH_UNCHANGED || 'false'); // No publicar si el valor no cambió
const RS232_AUTO_TARA_ENABLED = /^(true|1)$/i.test(process.env.RS232_AUTO_TARA_ENABLED || 'false'); // Auto-tara inteligente
const RS232_AUTO_TARA_MIN = parseFloat(process.env.RS232_AUTO_TARA_MIN || '0.05'); // Mínimo para considerar tara (kg)
const RS232_AUTO_TARA_MAX = parseFloat(process.env.RS232_AUTO_TARA_MAX || '0.4'); // Máximo para auto-tara (kg)
const RS232_AUTO_TARA_TIME = parseFloat(process.env.RS232_AUTO_TARA_TIME || '30'); // Tiempo estable para auto-tara (segundos)
const RS232_AUTO_OFFSET_MAX = parseFloat(process.env.RS232_AUTO_OFFSET_MAX || '0.05'); // Máximo para ajuste de offset (kg)
const RS232_PERSIST_OFFSET = /^(true|1)$/i.test(process.env.RS232_PERSIST_OFFSET || 'true'); // Guardar offset en .env tras ajustes
const POLL_INTERVAL_MS = parseInt(process.env.POLL_INTERVAL_MS || '1000', 10); // Intervalo de sondeo
const AUTODETECT_ENABLED = RS232_COMMAND === 'AUTO';

// Configuración MQTT
const MQTT_BROKER_URL = process.env.MQTT_BROKER_URL || 'mqtt://localhost';
const MQTT_TOPIC_BASE = process.env.MQTT_TOPIC_BASE || 'sensorica/bascula/peso';
const MQTT_TOPIC_TARA = process.env.MQTT_TOPIC_TARA || 'sensorica/bascula/tara';
const MQTT_TOPIC_ZERO = process.env.MQTT_TOPIC_ZERO || 'sensorica/bascula/zero';
const LOG_VERBOSE = /^(true|1)$/i.test(process.env.LOG_VERBOSE || 'true');

// Configuración de direcciones de básculas
const RS232_ADDRESSES = (process.env.RS232_ADDRESSES || '1').split(',').map(a => parseInt(a.trim(), 10)).filter(a => a >= 1 && a <= 10);
const RS232_ADDRESS_PREFIX = process.env.RS232_ADDRESS_PREFIX || ''; // Prefijo del comando de dirección (ej: '@' para '@01')

// Constantes
const BASSCULA_ID = 'smart_utilcell'; // ID base para MQTT
let currentAddressIndex = 0; // Índice de dirección actual para polling cíclico
const vlog = (...args) => { if (LOG_VERBOSE) console.log(...args); };

// --- Inicialización MQTT ---
const mqttClient = mqtt.connect(MQTT_BROKER_URL);
const ENV_FILE_PATH = path.join(__dirname, '.env');
let lastPersistedOffset = Number.isFinite(RS232_OFFSET) ? RS232_OFFSET : 0;

function persistOffset(newOffset, reason = 'auto') {
  if (!RS232_PERSIST_OFFSET) return;
  if (!Number.isFinite(newOffset)) return;
  const rounded = Math.round(newOffset);
  if (rounded === lastPersistedOffset) return;
  try {
    let content = '';
    if (fs.existsSync(ENV_FILE_PATH)) {
      content = fs.readFileSync(ENV_FILE_PATH, 'utf8');
    }
    if (content.includes('RS232_OFFSET=')) {
      content = content.replace(/^(RS232_OFFSET=).*$/m, `$1${rounded}`);
    } else {
      if (content.length && !content.endsWith('\n')) content += '\n';
      content += `RS232_OFFSET=${rounded}\n`;
    }
    fs.writeFileSync(ENV_FILE_PATH, content);
    lastPersistedOffset = rounded;
    console.log(`[AUTO-CORRECT] Persistido RS232_OFFSET=${rounded} en .env (${reason})`);
  } catch (err) {
    console.error('[AUTO-CORRECT] Error al persistir offset en .env:', err.message);
  }
}

function persistTara(newTara, reason = 'manual') {
  if (!RS232_PERSIST_OFFSET) return; // Usa la misma configuración
  if (!Number.isFinite(newTara)) return;
  const rounded = parseFloat(newTara.toFixed(3)); // Mantener 3 decimales para tara
  try {
    let content = '';
    if (fs.existsSync(ENV_FILE_PATH)) {
      content = fs.readFileSync(ENV_FILE_PATH, 'utf8');
    }
    if (content.includes('RS232_TARA=')) {
      content = content.replace(/^(RS232_TARA=).*$/m, `$1${rounded}`);
    } else {
      if (content.length && !content.endsWith('\n')) content += '\n';
      content += `RS232_TARA=${rounded}\n`;
    }
    fs.writeFileSync(ENV_FILE_PATH, content);
    console.log(`[AUTO-TARA] Persistido RS232_TARA=${rounded} en .env (${reason})`);
  } catch (err) {
    console.error('[AUTO-TARA] Error al persistir tara en .env:', err.message);
  }
}

mqttClient.on('connect', () => {
  console.log(`[MQTT] Conectado a ${MQTT_BROKER_URL}. Tópico base: ${MQTT_TOPIC_BASE}/${BASSCULA_ID}`);
  // Suscribirse a comandos de control
  mqttClient.subscribe(`${MQTT_TOPIC_TARA}/${BASSCULA_ID}`, (err) => {
    if (err) console.error('[MQTT] Error suscribiendo a tara:', err.message);
    else console.log(`[MQTT] Suscrito a: ${MQTT_TOPIC_TARA}/${BASSCULA_ID}`);
  });
  mqttClient.subscribe(`${MQTT_TOPIC_ZERO}/${BASSCULA_ID}`, (err) => {
    if (err) console.error('[MQTT] Error suscribiendo a zero:', err.message);
    else console.log(`[MQTT] Suscrito a: ${MQTT_TOPIC_ZERO}/${BASSCULA_ID}`);
  });
});

mqttClient.on('error', (err) => {
  console.error('[MQTT] Error:', err.message);
});

// Manejo de comandos MQTT (tara, cero)
mqttClient.on('message', (topic, message) => {
  try {
    const payload = JSON.parse(message.toString());
    vlog('[MQTT] Comando recibido:', { topic, payload });

    if (topic === `${MQTT_TOPIC_TARA}/${BASSCULA_ID}`) {
      if (payload.value === true) {
        // Comando T para tara según manual Utilcell (tara por hardware)
        console.log('[MQTT→RS232] Ejecutando TARA por hardware...');
        const taraCmd = Buffer.from('T\r\n');
        if (asciiPort && asciiPort.isOpen) {
          asciiPort.write(taraCmd, (err) => {
            if (err) {
              console.error('[RS232] Error enviando TARA:', err.message);
            } else {
              console.log('[RS232] ✓ Comando TARA enviado a la báscula');
              vlog('[RS232] → Comando:', taraCmd.toString('hex'));
            }
          });
        } else {
          console.error('[RS232] Puerto no disponible para TARA');
        }
      } else if (payload.set_tara !== undefined) {
        // Establecer tara manual por software
        const newTara = parseFloat(payload.set_tara);
        if (Number.isFinite(newTara)) {
          const oldTara = RS232_TARA;
          RS232_TARA = newTara;
          console.log(`[MQTT→TARA] Tara establecida manualmente: ${oldTara.toFixed(3)} → ${RS232_TARA.toFixed(3)} kg`);
          persistTara(RS232_TARA, 'mqtt-manual');
        }
      } else if (payload.reset_tara === true) {
        // Resetear tara a 0
        const oldTara = RS232_TARA;
        RS232_TARA = 0;
        console.log(`[MQTT→TARA] Tara reseteada: ${oldTara.toFixed(3)} → 0 kg`);
        persistTara(RS232_TARA, 'mqtt-reset');
      } else if (payload.calibrate === true) {
        // Tara por software: recalibrar offset
        console.log('[AUTO-OFFSET] Iniciando recalibración de offset...');
        offsetCalibrationSamples = [];
        offsetCalibrated = false;
        RS232_OFFSET = 0;
        negativeWeightCount = 0;
        lowWeightSamples = [];
      }
    }
    
    if (topic === `${MQTT_TOPIC_ZERO}/${BASSCULA_ID}`) {
      if (payload.reset === true) {
        // Reset offset y contadores
        console.log('[AUTO-CORRECT] Reset completo de offset y contadores');
        RS232_OFFSET = 0;
        offsetCalibrationSamples = [];
        offsetCalibrated = false;
        negativeWeightCount = 0;
        lowWeightSamples = [];
        return;
      }
    }

    if (topic === `${MQTT_TOPIC_ZERO}/${BASSCULA_ID}` && payload.value === true) {
      console.log('[MQTT→RS232] Ejecutando CERO...');
      // Comando Z para cero según manual Utilcell
      const zeroCmd = Buffer.from('Z\r\n');
      if (asciiPort && asciiPort.isOpen) {
        asciiPort.write(zeroCmd, (err) => {
          if (err) {
            console.error('[RS232] Error enviando CERO:', err.message);
          } else {
            console.log('[RS232] ✓ Comando CERO enviado');
            vlog('[RS232] → Comando:', zeroCmd.toString('hex'));
            RS232_OFFSET = 0;
            offsetCalibrationSamples = [];
            offsetCalibrated = false;
            negativeWeightCount = 0;
            lowWeightSamples = [];
            persistOffset(RS232_OFFSET, 'mqtt-zero');
          }
        });
      } else {
        console.error('[RS232] Puerto no disponible para CERO');
      }
    }
  } catch (e) {
    console.error('[MQTT] Error procesando comando:', e.message);
  }
});

// --- Lógica de Comunicación RS232 (ASCII) ---
let asciiPort;
let asciiParser;
let asciiPollTimer;
let autodetectIndex = 0;
let autodetectConfigs = [];
let currentConfig = null;
let lastDataReceived = 0;
let autodetectAttempts = 0;

// Auto-calibración de offset
let offsetCalibrationSamples = [];
let offsetCalibrated = false;
const OFFSET_CALIBRATION_COUNT = 10; // Número de muestras para calibrar

// Auto-corrección continua de offset
let negativeWeightCount = 0;
let lowWeightSamples = [];
const LOW_WEIGHT_THRESHOLD = 10; // Umbral para considerar "peso bajo" en kg

// Control de publicación por dirección
let lastPublishedWeight = {}; // { address: weight }

// Auto-tara inteligente por dirección
let autoTaraSamples = {}; // { address: [samples] }
let autoTaraStartTime = {}; // { address: timestamp }
let RS232_TARA_BY_ADDRESS = {}; // { address: tara_value }

function initAutodetectConfigs() {
  // Genera todas las combinaciones posibles
  const commands = ['P'];
  const terminators = [
    { name: 'none', cr: false, lf: false },
    { name: 'CR', cr: true, lf: false },
    { name: 'LF', cr: false, lf: true },
    { name: 'CRLF', cr: true, lf: true }
  ];
  const configs = [];
  for (const cmd of commands) {
    for (const term of terminators) {
      configs.push({ command: cmd, ...term });
    }
  }
  return configs;
}

function buildCommandWithConfig(config) {
  let cmd = Buffer.from(config.command, 'utf8');
  if (config.cr) cmd = Buffer.concat([cmd, Buffer.from('\r')]);
  if (config.lf) cmd = Buffer.concat([cmd, Buffer.from('\n')]);
  return cmd;
}

function startAsciiReader() {
  // 1. Configuración del puerto serial
  asciiPort = new SerialPort({
    path: SERIAL_PORT,
    baudRate: BAUD_RATE,
    dataBits: DATA_BITS,
    stopBits: STOP_BITS,
    parity: PARITY,
    autoOpen: false,
  });

  // 2. Determinación del delimitador para el parser (CRLF -> LF o CR -> CR)
  let delimiter;
  if (RS232_DELIMITER === 'LF' || RS232_DELIMITER === 'CRLF') {
    delimiter = "\n";
  } else if (RS232_DELIMITER === 'CR') {
    delimiter = "\r";
  } else {
    delimiter = "\n"; // Fallback a LF
    console.warn(`[RS232] Delimitador '${RS232_DELIMITER}' no reconocido. Usando LF.`);
  }

  // 3. Configuración del parser de línea
  asciiParser = asciiPort.pipe(new ReadlineParser({ delimiter, encoding: 'ascii' }));

  // Manejo de errores y reconexión
  asciiPort.on('error', (err) => console.error('[RS232] Error en puerto:', err.message));
  asciiPort.on('close', () => {
    console.warn('[RS232] Puerto cerrado. Reintentando en 2s...');
    if (asciiPollTimer) clearInterval(asciiPollTimer);
    setTimeout(() => asciiPort.open((e) => e && console.error('[RS232] Error al reabrir:', e.message)), 2000);
  });

  // Escuchador de datos crudos para diagnóstico y autodetección
  asciiPort.on('data', (data) => {
    const hex = data.toString('hex');
    const ascii = data.toString('ascii').replace(/[\x00-\x1F]/g, (c) => `<${c.charCodeAt(0).toString(16).toUpperCase()}>`);
    console.log(`[RS232] ← DATO CRUDO: HEX=[${hex}] ASCII=[${ascii}]`);
    lastDataReceived = Date.now();
    
    if (AUTODETECT_ENABLED && !currentConfig) {
      const detectedIndex = autodetectIndex - 1; // Corregir: ya incrementamos antes
      currentConfig = autodetectConfigs[detectedIndex];
      const cmdDisplay = currentConfig.command === '\x16' ? 'SYN(0x16)' : currentConfig.command;
      console.log(`[AUTODETECT] ¡RESPUESTA DETECTADA con config #${detectedIndex + 1}!`);
      console.log(`[AUTODETECT] ✓ CONFIGURACIÓN VÁLIDA: comando='${cmdDisplay}' term='${currentConfig.name}'`);
      // Reiniciar timer con la config correcta
      if (asciiPollTimer) clearInterval(asciiPollTimer);
      asciiPollTimer = setInterval(() => {
        const cmd = buildCommandWithConfig(currentConfig);
        asciiPort.write(cmd, (werr) => {
          if (werr) {
            console.error('[RS232] Error al enviar comando:', werr.message);
          }
        });
      }, POLL_INTERVAL_MS);
    }
  });
  
  // 4. Función para construir el comando de petición (modo manual)
  const buildCommand = (address = null) => {
    if (currentConfig) {
      return buildCommandWithConfig(currentConfig, address);
    }
    // Construir comando con prefijo de dirección si es necesario
    let cmdStr = RS232_COMMAND;
    if (address && RS232_ADDRESS_PREFIX) {
      const addrStr = address.toString().padStart(2, '0');
      cmdStr = `${RS232_ADDRESS_PREFIX}${addrStr}${RS232_COMMAND}`;
    }
    let cmd = Buffer.from(cmdStr, 'utf8');
    if (process.env.RS232_APPEND_CR === 'true') cmd = Buffer.concat([cmd, Buffer.from('\r')]);
    if (process.env.RS232_APPEND_LF === 'true') cmd = Buffer.concat([cmd, Buffer.from('\n')]);
    return cmd;
  };

  // Inicializar estructuras por dirección
  console.log(`[INIT] Direcciones de básculas configuradas: ${RS232_ADDRESSES.join(', ')}`);
  RS232_ADDRESSES.forEach(addr => {
    // Cargar configuración específica por dirección desde .env
    const offsetKey = `RS232_OFFSET_${addr}`;
    const taraKey = `RS232_TARA_${addr}`;
    const scaleKey = `RS232_SCALE_${addr}`;
    
    // Usar valor específico si existe, sino usar el global
    RS232_OFFSET_BY_ADDRESS[addr] = parseFloat(process.env[offsetKey] || RS232_OFFSET);
    RS232_TARA_BY_ADDRESS[addr] = parseFloat(process.env[taraKey] || RS232_TARA);
    RS232_SCALE_BY_ADDRESS[addr] = parseFloat(process.env[scaleKey] || RS232_SCALE);
    
    console.log(`[INIT][Addr ${addr}] Offset: ${RS232_OFFSET_BY_ADDRESS[addr]}, Tara: ${RS232_TARA_BY_ADDRESS[addr].toFixed(3)}, Scale: ${RS232_SCALE_BY_ADDRESS[addr]}`);
    
    lastPublishedWeight[addr] = null;
    autoTaraSamples[addr] = [];
    autoTaraStartTime[addr] = null;
  });

  // 5. Recepción y procesamiento de datos
  asciiParser.on('data', (line) => {
    const trimmed = line.trim();
    vlog('[RS232] Línea cruda PROCESADA:', trimmed);
    
    // Extraer dirección si hay prefijo (ej: "@01 1234567" o "01 1234567")
    let address = currentAddressIndex < RS232_ADDRESSES.length ? RS232_ADDRESSES[currentAddressIndex] : 1;
    let dataLine = trimmed;
    
    if (RS232_ADDRESS_PREFIX) {
      const addressMatch = trimmed.match(new RegExp(`^${RS232_ADDRESS_PREFIX}?(\\d{1,2})\\s+(.+)$`));
      if (addressMatch) {
        address = parseInt(addressMatch[1], 10);
        dataLine = addressMatch[2];
        vlog(`[RS232] Dirección detectada: ${address}, Datos: ${dataLine}`);
      }
    }
    
    // Verificar que la dirección está en la lista configurada
    if (!RS232_ADDRESSES.includes(address)) {
      vlog(`[RS232] Dirección ${address} no configurada, ignorada`);
      return;
    }
    
    // Extraer número (entero o decimal) de la línea de datos
    const m = dataLine.match(/[-+]?\d+([.,]\d+)?/);

    if (m) {
      // Reemplaza coma por punto (si se usó coma decimal) y convierte a float
      const rawWeight = parseFloat(m[0].replace(',', '.'));

      if (!isNaN(rawWeight)) {
        // Auto-calibración de offset (solo al inicio)
        if (RS232_AUTO_OFFSET && !offsetCalibrated && RS232_OFFSET === 0) {
          offsetCalibrationSamples.push(rawWeight);
          
          if (offsetCalibrationSamples.length >= OFFSET_CALIBRATION_COUNT) {
            // Verificar que todas las muestras sean consistentes (>= mínimo)
            const allAboveMin = offsetCalibrationSamples.every(w => w >= RS232_MIN_OFFSET);
            
            if (allAboveMin) {
              const avgOffset = offsetCalibrationSamples.reduce((a, b) => a + b, 0) / offsetCalibrationSamples.length;
              RS232_OFFSET = Math.round(avgOffset);
              offsetCalibrated = true;
              console.log(`[AUTO-OFFSET] ✓ Offset calibrado automáticamente: ${RS232_OFFSET}`);
              console.log(`[AUTO-OFFSET] Promedio de ${OFFSET_CALIBRATION_COUNT} muestras: ${offsetCalibrationSamples.map(w => Math.round(w)).join(', ')}`);
              console.log(`[AUTO-OFFSET] Fórmula aplicada: (peso_crudo - ${RS232_OFFSET}) * ${RS232_SCALE}`);
              persistOffset(RS232_OFFSET, 'auto-calibration');
            } else {
              offsetCalibrated = true; // No calibrar si hay peso bajo
              console.log(`[AUTO-OFFSET] ℹ Calibración omitida: peso detectado menor a ${RS232_MIN_OFFSET}`);
            }
            offsetCalibrationSamples = [];
          } else {
            console.log(`[AUTO-OFFSET] Recolectando muestra ${offsetCalibrationSamples.length}/${OFFSET_CALIBRATION_COUNT}: ${Math.round(rawWeight)}`);
            return; // No publicar durante calibración
          }
        }
        
        // Paso 1: Restar offset (calibración a 0) y aplicar escala
        // Usar valores específicos de esta dirección
        const currentOffset = RS232_OFFSET_BY_ADDRESS[address] || RS232_OFFSET;
        const currentScale = RS232_SCALE_BY_ADDRESS[address] || RS232_SCALE;
        let weightBruto = (rawWeight - currentOffset) * currentScale;
        
        // Paso 2: Calcular peso neto usando tara de esta dirección
        // Si peso_bruto <= tara → publicar 0 (no negativo para no marear al usuario)
        const currentTara = RS232_TARA_BY_ADDRESS[address] || 0;
        let finalWeight = Math.max(weightBruto - currentTara, 0);
        
        // AUTO-CORRECCIÓN: Si el peso es negativo, ajustar offset
        if (RS232_AUTO_CORRECT_ENABLED && finalWeight < 0) {
          negativeWeightCount++;
          if (negativeWeightCount >= RS232_AUTO_CORRECT_THRESHOLD) {
            const correction = Math.min(Math.abs(finalWeight / RS232_SCALE), RS232_MAX_OFFSET_CHANGE);
            const oldOffset = RS232_OFFSET;
            RS232_OFFSET -= Math.round(correction);
            const duration = (negativeWeightCount * POLL_INTERVAL_MS / 1000).toFixed(1);
            console.log(`[AUTO-CORRECT] ⚠ Peso negativo durante ${duration}s (${negativeWeightCount} lecturas)`);
            console.log(`[AUTO-CORRECT] ℹ Corrección calculada: ${Math.abs(finalWeight / RS232_SCALE).toFixed(0)} (limitado a: ${Math.round(correction)})`);
            console.log(`[AUTO-CORRECT] ✓ Offset ajustado: ${oldOffset} → ${RS232_OFFSET}`);
            finalWeight = (rawWeight - RS232_OFFSET) * RS232_SCALE;
            negativeWeightCount = 0;
            lowWeightSamples = [];
            persistOffset(RS232_OFFSET, 'auto-correct-negative');
          }
        } else if (RS232_AUTO_CORRECT_ENABLED && finalWeight >= 0 && finalWeight < LOW_WEIGHT_THRESHOLD) {
          // AUTO-CORRECCIÓN: Si peso es positivo pero muy bajo de forma consistente
          lowWeightSamples.push(finalWeight);
          negativeWeightCount = 0; // Reset contador negativo
          
          if (lowWeightSamples.length >= RS232_LOW_WEIGHT_SAMPLES) {
            const avgLowWeight = lowWeightSamples.reduce((a, b) => a + b, 0) / lowWeightSamples.length;
            if (avgLowWeight > 0.5 && avgLowWeight < LOW_WEIGHT_THRESHOLD) {
              // Si consistentemente lee algo positivo bajo, ajustar offset
              const correction = Math.min(avgLowWeight / RS232_SCALE, RS232_MAX_OFFSET_CHANGE);
              const oldOffset = RS232_OFFSET;
              RS232_OFFSET += Math.round(correction);
              console.log(`[AUTO-CORRECT] ℹ Peso bajo consistente detectado (promedio: ${avgLowWeight.toFixed(3)} kg)`);
              console.log(`[AUTO-CORRECT] ℹ Corrección calculada: ${(avgLowWeight / RS232_SCALE).toFixed(0)} (limitado a: ${Math.round(correction)})`);
              console.log(`[AUTO-CORRECT] ✓ Offset ajustado: ${oldOffset} → ${RS232_OFFSET}`);
              finalWeight = (rawWeight - RS232_OFFSET) * RS232_SCALE;
              persistOffset(RS232_OFFSET, 'auto-correct-low');
            }
            lowWeightSamples = [];
          }
        } else {
          // Si hay peso normal, resetear contadores
          negativeWeightCount = 0;
          lowWeightSamples = [];
        }
        
        // SISTEMA INTELIGENTE DE 3 RANGOS (usa peso BRUTO para detectar):
        // 1. [0 - RS232_AUTO_OFFSET_MAX]: Ajuste de OFFSET (calibración a 0)
        // 2. [RS232_AUTO_TARA_MIN - RS232_AUTO_TARA_MAX]: Auto-TARA (peso de contenedor) - REEMPLAZA tara anterior
        // 3. [> RS232_AUTO_TARA_MAX]: Peso real, no hace nada
        if (RS232_AUTO_TARA_ENABLED && weightBruto >= 0 && weightBruto <= RS232_AUTO_TARA_MAX) {
          if (!autoTaraStartTime[address]) {
            autoTaraStartTime[address] = Date.now();
            autoTaraSamples[address] = [weightBruto];
            vlog(`[AUTO-ADJUST][Addr ${address}] Detectando peso bruto estable: ${weightBruto.toFixed(3)} kg...`);
          } else {
            autoTaraSamples[address].push(weightBruto);
            const elapsedSeconds = (Date.now() - autoTaraStartTime[address]) / 1000;
            
            if (elapsedSeconds >= RS232_AUTO_TARA_TIME) {
              // Calcular promedio de las muestras (peso BRUTO)
              const avgWeightBruto = autoTaraSamples[address].reduce((a, b) => a + b, 0) / autoTaraSamples[address].length;
              
              // RANGO 1: Peso muy bajo (0 - RS232_AUTO_OFFSET_MAX) → Ajustar OFFSET
              if (avgWeightBruto < RS232_AUTO_OFFSET_MAX && avgWeightBruto > 0.001) {
                const oldOffset = RS232_OFFSET;
                const adjustment = Math.round(avgWeightBruto / RS232_SCALE);
                RS232_OFFSET += adjustment;
                console.log(`[AUTO-OFFSET] ✓ Peso bruto estable: ${avgWeightBruto.toFixed(3)} kg durante ${RS232_AUTO_TARA_TIME}s`);
                console.log(`[AUTO-OFFSET] ✓ Offset ajustado: ${oldOffset} → ${RS232_OFFSET}`);
                persistOffset(RS232_OFFSET, 'auto-adjust-offset');
                finalWeight = 0; // Ahora muestra 0
              }
              // RANGO 2: Peso medio (RS232_AUTO_TARA_MIN - RS232_AUTO_TARA_MAX) → TARA (REEMPLAZA anterior)
              else if (avgWeightBruto >= RS232_AUTO_TARA_MIN && avgWeightBruto <= RS232_AUTO_TARA_MAX) {
                const oldTara = RS232_TARA_BY_ADDRESS[address] || 0;
                RS232_TARA_BY_ADDRESS[address] = avgWeightBruto; // REEMPLAZA (no suma) la tara anterior con peso BRUTO
                console.log(`[AUTO-TARA][Addr ${address}] ✓ Peso bruto de contenedor estable: ${avgWeightBruto.toFixed(3)} kg durante ${RS232_AUTO_TARA_TIME}s`);
                if (oldTara > 0) {
                  console.log(`[AUTO-TARA][Addr ${address}] ℹ Tara REEMPLAZADA: ${oldTara.toFixed(3)} → ${RS232_TARA_BY_ADDRESS[address].toFixed(3)} kg`);
                } else {
                  console.log(`[AUTO-TARA][Addr ${address}] ✓ Tara establecida: ${RS232_TARA_BY_ADDRESS[address].toFixed(3)} kg`);
                }
                console.log(`[AUTO-TARA][Addr ${address}] ℹ Si pones otro contenedor (${RS232_AUTO_TARA_MIN}-${RS232_AUTO_TARA_MAX} kg), la tara se reemplazará`);
                persistTara(RS232_TARA_BY_ADDRESS[address], `auto-tara-addr${address}`);
                finalWeight = 0; // Ahora el peso neto es 0
              }
              // Peso demasiado bajo para hacer nada
              else {
                vlog(`[AUTO-ADJUST] Peso demasiado bajo (${avgWeightBruto.toFixed(3)} kg), ignorado`);
              }
              
              // Reset
              autoTaraStartTime[address] = null;
              autoTaraSamples[address] = [];
            }
          }
        } else if (weightBruto > RS232_AUTO_TARA_MAX) {
          // RANGO 3: Peso real (> RS232_AUTO_TARA_MAX) → Cancelar auto-tara/offset en progreso
          if (autoTaraStartTime[address]) {
            vlog(`[AUTO-ADJUST][Addr ${address}] Cancelado: peso bruto ${weightBruto.toFixed(3)} kg > ${RS232_AUTO_TARA_MAX} kg (peso real)`);
          }
          autoTaraStartTime[address] = null;
          autoTaraSamples[address] = [];
        }
        
        let roundedWeight = parseFloat(finalWeight.toFixed(RS232_DECIMALS));
        
        // Aplicar umbral de cero: si está muy cerca de 0, considerarlo 0
        if (Math.abs(roundedWeight) < RS232_ZERO_THRESHOLD) {
          roundedWeight = 0;
        }
        
        // DEBUG: Mostrar información completa del peso
        console.log(`[DEBUG] ══════════════════════════ Dirección ${address} ══════════════════════════`);
        console.log(`[DEBUG] Peso CRUDO:       ${rawWeight.toFixed(0)} (lectura báscula)`);
        console.log(`[DEBUG] Offset aplicado:  ${currentOffset} (calibración a 0)`);
        console.log(`[DEBUG] Escala aplicada:  ${currentScale}`);
        console.log(`[DEBUG] Peso BRUTO:       ${weightBruto.toFixed(3)} kg (después de offset y escala)`);
        console.log(`[DEBUG] Tara actual:      ${currentTara.toFixed(3)} kg`);
        console.log(`[DEBUG] Peso NETO calc:   ${(weightBruto - currentTara).toFixed(3)} kg`);
        console.log(`[DEBUG] Peso PUBLICADO:   ${roundedWeight.toFixed(RS232_DECIMALS)} kg (max(neto, 0))`);
        console.log(`[DEBUG] ═══════════════════════════════════════════════════════════════`);
        
        // Evitar publicar valores repetidos si está habilitado
        if (RS232_PUBLISH_UNCHANGED && lastPublishedWeight[address] !== null && roundedWeight === lastPublishedWeight[address]) {
          vlog(`[RS232->MQTT][Addr ${address}] Valor sin cambios, no publicado:`, roundedWeight);
        } else {
          const topic = `${MQTT_TOPIC_BASE}/address/${address}/${BASSCULA_ID}`;
          // Si es 0, mantener con decimales (0.000) para mostrar que está funcionando
          let payload;
          if (roundedWeight === 0) {
            const zeroWithDecimals = (0).toFixed(RS232_DECIMALS);
            payload = `{"value":${zeroWithDecimals}}`;
          } else {
            payload = JSON.stringify({ value: roundedWeight });
          }

          mqttClient.publish(topic, payload, { qos: 0, retain: false }, (err) => {
            if (err) console.error(`[MQTT][Addr ${address}] Error publicando peso:`, err.message);
          });
          console.log(`[MQTT->PUBLICADO][Addr ${address}] Topic: ${topic}, Payload: ${payload}`);
          lastPublishedWeight[address] = roundedWeight;
        }
      } else {
         vlog('[RS232] Número detectado, pero la conversión falló:', m[0]);
      }
    } else {
      vlog('[RS232] Línea sin número detectable (ignorado)');
    }
  });

  // 6. Apertura del puerto e inicio del sondeo
  asciiPort.open((err) => {
    if (err) {
      console.error('[RS232] No se pudo abrir el puerto:', err.message);
      setTimeout(() => startAsciiReader(), 2000);
      return;
    }
    console.log(`[RS232] Puerto abierto: ${SERIAL_PORT} @ ${BAUD_RATE}, ${DATA_BITS} bits, ${PARITY}, ${STOP_BITS} stop.`);
    
    if (AUTODETECT_ENABLED) {
      autodetectConfigs = initAutodetectConfigs();
      console.log(`[AUTODETECT] Iniciando autodetección con ${autodetectConfigs.length} configuraciones...`);
      console.log(`[AUTODETECT] Probando cada config durante 3 segundos hasta encontrar respuesta.`);
      
      // Función de autodetección secuencial
      const tryNextConfig = () => {
        if (currentConfig) return; // Ya encontramos una que funciona
        
        if (autodetectIndex >= autodetectConfigs.length) {
          console.error('[AUTODETECT] ✗ Ninguna configuración funcionó. Verifica:');
          console.error('  1. Modo del indicador debe ser DEMAND');
          console.error('  2. Cableado RS232: TX→RX cruzado, GND común');
          console.error('  3. Parámetros del puerto: 115200 8N1');
          console.error('[AUTODETECT] Reiniciando desde el inicio...');
          autodetectIndex = 0;
          autodetectAttempts++;
          if (autodetectAttempts > 2) {
            console.error('[AUTODETECT] Demasiados intentos. Deteniendo.');
            process.exit(1);
          }
          setTimeout(tryNextConfig, 2000);
          return;
        }
        
        const config = autodetectConfigs[autodetectIndex];
        const cmdDisplay = config.command === '\x16' ? 'SYN(0x16)' : config.command;
        console.log(`[AUTODETECT] [${autodetectIndex + 1}/${autodetectConfigs.length}] Probando: comando='${cmdDisplay}' term='${config.name}'`);
        
        const cmd = buildCommandWithConfig(config);
        const hexCmd = cmd.toString('hex');
        console.log(`[AUTODETECT]   → Enviando: HEX=[${hexCmd}]`);
        
        asciiPort.write(cmd, (werr) => {
          if (werr) console.error('[AUTODETECT] Error escribiendo:', werr.message);
        });
        
        autodetectIndex++;
        setTimeout(tryNextConfig, 3000); // Espera 3s antes de probar siguiente
      };
      
      tryNextConfig();
    } else {
      // Modo manual con polling cíclico de direcciones
      console.log(`[RS232] Modo manual: comando='${RS232_COMMAND}'`);
      console.log(`[RS232] Polling cíclico de direcciones: ${RS232_ADDRESSES.join(', ')}`);
      if (asciiPollTimer) clearInterval(asciiPollTimer);
      asciiPollTimer = setInterval(() => {
        // Obtener la dirección actual del ciclo
        const currentAddress = RS232_ADDRESSES[currentAddressIndex];
        const cmd = buildCommand(currentAddress);
        
        asciiPort.write(cmd, (werr) => {
          if (werr) {
            console.error(`[RS232][Addr ${currentAddress}] Error al enviar comando:`, werr.message);
          } else {
            vlog(`[RS232][Addr ${currentAddress}] → Comando enviado: ${cmd.toString('hex')}`);
          }
        });
        
        // Avanzar al siguiente índice (cíclico)
        currentAddressIndex = (currentAddressIndex + 1) % RS232_ADDRESSES.length;
      }, POLL_INTERVAL_MS);
    }
  });
}

// --- Arranque de la Aplicación ---
(async () => {
  console.log('[APP] 232-basculas iniciando en modo ASCII...');
  vlog('[APP] Configuración:', {
    SERIAL_PORT,
    BAUD_RATE,
    PARITY,
    RS232_COMMAND,
    RS232_DELIMITER,
    POLL_INTERVAL_MS,
    MQTT_BROKER_URL,
  });

  startAsciiReader();
})();

// Manejo global de errores no capturados
process.on('unhandledRejection', (reason) => {
  console.error('[APP] UnhandledRejection:', reason);
});
process.on('uncaughtException', (err) => {
  console.error('[APP] UncaughtException:', err && err.stack || err);
});

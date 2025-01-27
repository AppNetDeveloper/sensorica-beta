import time
from pymodbus.client import ModbusSerialClient
import paho.mqtt.client as mqtt
import json
import threading
from queue import Queue

# Cargar la configuración desde un archivo JSON
with open('config.json') as config_file:
    config = json.load(config_file)

client = None
reconnect_interval = config.get('reconnect_interval', 5)

mqtt_broker = config['mqtt_broker']
mqtt_base_topic = config['mqtt_base_topic']
mqtt_status_topic = config['mqtt_status_topic']
mqtt_dosificador_topic = config['mqtt_dosificador_topic']
mqtt_zero_topic = config['mqtt_zero_topic']
mqtt_tara_topic = config['mqtt_tara_topic']
mqtt_cancel_topic = config['mqtt_cancel_topic']  # Nuevo tópico para cancelación

# Ignorar advertencias de deprecación para la API de callbacks de MQTT
import warnings
warnings.filterwarnings("ignore", category=DeprecationWarning)

# Crear el cliente MQTT con la versión más reciente
mqtt_client = mqtt.Client(client_id="", clean_session=True, userdata=None, protocol=mqtt.MQTTv311, transport="tcp")

modbus_address_range = range(config['modbus_address_range']['start'], config['modbus_address_range']['end'] + 1)
batch_size = config['batch_size']
scan_interval = config['scan_interval']

buffer = []
modbus_lock = threading.Lock()
scan_lock = threading.Lock()
pause_event = threading.Event()
active_threads = {}
active_addresses = set()

def on_connect(mqtt_client, userdata, flags, rc):
    if rc == 0:
        print("Conexión MQTT exitosa.")
        mqtt_client.subscribe(f"{mqtt_dosificador_topic}/#")
        mqtt_client.subscribe(f"{mqtt_zero_topic}/#")
        mqtt_client.subscribe(f"{mqtt_tara_topic}/#")
        mqtt_client.subscribe(f"{mqtt_cancel_topic}/#")  # Suscribirse al nuevo tópico de cancelación
    else:
        print(f"Conexión MQTT fallida con código {rc}")

def on_message(mqtt_client, userdata, message):
    topic = message.topic
    try:
        if message.payload:
            payload = json.loads(message.payload.decode('utf-8'))
            print(f"Mensaje recibido en tópico {topic}: {payload}")

            pause_event.clear()
            try:
                direccion_modbus = int(topic.split('/')[-1])
            except ValueError:
                print(f"Error: La dirección Modbus en el tópico {topic} no es válida.")
                pause_event.set()
                return

            if direccion_modbus in modbus_address_range:
                if mqtt_dosificador_topic in topic and 'value' in payload:
                    iniciar_dosificacion(direccion_modbus, payload['value'])
                elif mqtt_zero_topic in topic and 'value' in payload and payload['value'] is True:
                    hacer_cero(direccion_modbus)
                elif mqtt_tara_topic in topic and 'value' in payload and payload['value'] is True:
                    hacer_tara(direccion_modbus)
                elif mqtt_cancel_topic in topic and 'value' in payload and payload['value'] is True:  # Nueva condición para cancelación
                    cancelar_dosificacion(direccion_modbus)
                elif mqtt_tara_topic in topic and 'read' in payload and payload['read'] is True:
                    valor_tara = leer_valor_tara(direccion_modbus)
                    if valor_tara is not None:
                        mqtt_topic = f"{mqtt_tara_topic}/{direccion_modbus}"
                        payload_tara = json.dumps({"tara": valor_tara})
                        mqtt_client.publish(mqtt_topic, payload_tara)
                        print(f"Publicado en MQTT - Tópico: {mqtt_topic}, Payload: {payload_tara}")
            else:
                print(f"Error: Dirección Modbus fuera del rango permitido. Dirección recibida: {direccion_modbus}")

            pause_event.set()
        else:
            print("Advertencia: El payload recibido está vacío.")
    except json.JSONDecodeError:
        print("Error: El payload recibido no es un JSON válido.")

def publicar_estado_operacion(direccion_modbus, operacion, estado):
    mqtt_topic = f"sensorica/bascula/{operacion}/status"
    payload = json.dumps({"status": estado})
    mqtt_client.publish(mqtt_topic, payload)
    print(f"Publicado en MQTT - Tópico: {mqtt_topic}, Payload: {payload}")

def iniciar_dosificacion(direccion_modbus, peso_objetivo):
    publicar_estado_operacion(direccion_modbus, "dosifica", "Iniciando")
    with modbus_lock:
        try:
            print(f"Iniciando dosificación en dirección Modbus {direccion_modbus} con {peso_objetivo / 10.0} kg.")
            client.write_registers(1001, [peso_objetivo >> 16, peso_objetivo & 0xFFFF], slave=direccion_modbus)
            client.write_register(1000, 13, slave=direccion_modbus)
            print(f"Dosificación de {peso_objetivo / 10.0} kg iniciada en dirección Modbus {direccion_modbus}.")
            publicar_estado_operacion(direccion_modbus, "dosifica", "Finalizado")
        except Exception as e:
            print(f"Error al realizar la dosificación: {e}")
            publicar_estado_operacion(direccion_modbus, "dosifica", "ERROR")

def cancelar_dosificacion(direccion_modbus):
    publicar_estado_operacion(direccion_modbus, "cancel", "Iniciando")
    with modbus_lock:
        try:
            print(f"Cancelando dosificación en dirección Modbus {direccion_modbus}.")
            client.write_register(1000, 100, slave=direccion_modbus)  # Código para cancelar el proceso
            print(f"Dosificación cancelada en dirección Modbus {direccion_modbus}.")
            publicar_estado_operacion(direccion_modbus, "cancel", "Finalizado")
        except Exception as e:
            print(f"Error al cancelar la dosificación: {e}")
            publicar_estado_operacion(direccion_modbus, "cancel", "ERROR")

def hacer_cero(direccion_modbus):
    publicar_estado_operacion(direccion_modbus, "zero", "Iniciando")
    with modbus_lock:
        try:
            print(f"Haciendo cero en dirección Modbus {direccion_modbus}.")
            client.write_register(1000, 1, slave=direccion_modbus)
            print(f"Cero realizado en dirección Modbus {direccion_modbus}.")
            publicar_estado_operacion(direccion_modbus, "zero", "Finalizado")
        except Exception as e:
            print(f"Error al realizar el cero: {e}")
            publicar_estado_operacion(direccion_modbus, "zero", "ERROR")

def hacer_tara(direccion_modbus):
    publicar_estado_operacion(direccion_modbus, "tara", "Iniciando")
    with modbus_lock:
        try:
            print(f"Haciendo tara en dirección Modbus {direccion_modbus}.")
            client.write_register(1000, 2, slave=direccion_modbus)
            print(f"Tara realizada en dirección Modbus {direccion_modbus}.")
            publicar_estado_operacion(direccion_modbus, "tara", "Finalizado")
        except Exception as e:
            print(f"Error al realizar la tara: {e}")
            publicar_estado_operacion(direccion_modbus, "tara", "ERROR")

def leer_valor_tara(direccion_modbus):
    with modbus_lock:
        try:
            response = client.read_holding_registers(1002, 2, slave=direccion_modbus)
            if not response.isError():
                tara_value = (response.registers[0] << 16) + response.registers[1]
                return tara_value / 10.0
            else:
                print(f"Error al leer la tara en dirección Modbus {direccion_modbus}")
                return None
        except Exception as e:
            print(f"Error al leer la tara: {e}")
            return None

def publicar_batch():
    global buffer
    for item in buffer:
        mqtt_client.publish(item['topic'], item['payload'])
    buffer = []

def leer_peso(direccion):
    last_read = time.perf_counter()
    min_interval = 0.20  # Intervalo mínimo de lectura de 0.20 segundos
    max_interval = 0.30  # Intervalo máximo de lectura de 0.30 segundos
    while direccion in active_addresses:
        now = time.perf_counter()
        time_to_sleep = min_interval - (now - last_read)

        if time_to_sleep > 0:
            time.sleep(time_to_sleep)
        else:
            # Si el tiempo ha pasado, esperamos hasta el máximo para el próximo ciclo
            time_to_sleep = max_interval - (now - last_read)
            if time_to_sleep > 0:
                time.sleep(time_to_sleep)
            else:
                last_read = now

        try:
            with modbus_lock:
                response = client.read_input_registers(9, 2, slave=direccion)
            if not response.isError():
                peso_neto = (response.registers[0] << 16) + response.registers[1] / 10.0
                if peso_neto > 999999:
                    peso_neto = 0
                buffer.append({"topic": f"{mqtt_base_topic}/{direccion}", "payload": json.dumps({"value": peso_neto})})
                if len(buffer) >= batch_size:
                    publicar_batch()
        except Exception as e:
            print(f"Error al leer de la dirección {direccion}: {e}")
            break  # Terminar el hilo si hay un error
        last_read = time.perf_counter()  # Actualizamos last_read para el próximo ciclo

def escanear_direcciones():
    global client
    while True:
        with scan_lock:
            if client is None or not client.connect():
                reconectar_modbus()
            for direccion in modbus_address_range:
                try:
                    with modbus_lock:
                        response = client.read_input_registers(9, 1, slave=direccion)  # Solo para comprobar si la dirección responde
                    if not response.isError():
                        if direccion not in active_addresses:
                            print(f"Dispositivo en la dirección {direccion} detectado.")
                            active_addresses.add(direccion)
                            active_threads[direccion] = threading.Thread(target=leer_peso, args=(direccion,), daemon=True)
                            active_threads[direccion].start()
                    else:
                        if direccion in active_addresses:
                            print(f"Dispositivo en la dirección {direccion} no responde, marcando para detener.")
                            active_addresses.remove(direccion)
                except Exception as e:
                    print(f"Error al escanear la dirección {direccion}: {e}")
        time.sleep(scan_interval)

def reconectar_modbus():
    global client
    while True:
        try:
            client = ModbusSerialClient(
                port=config['modbus']['port'],
                baudrate=config['modbus']['baudrate'],
                timeout=config['modbus']['timeout'],
                stopbits=config['modbus']['stopbits'],
                bytesize=config['modbus']['bytesize'],
                parity=config['modbus']['parity']
            )
            if client.connect():
                print(f"Reconexión exitosa al puerto {config['modbus']['port']}")
                return
            else:
                print(f"Fallo al conectar al puerto {config['modbus']['port']}. Reintentando en {reconnect_interval} segundos...")
        except Exception as e:
            print(f"Error al intentar reconectar Modbus: {e}")
        time.sleep(reconnect_interval)

def reconectar_mqtt():
    while True:
        try:
            mqtt_client.connect(mqtt_broker)
            mqtt_client.loop_start()
            print("Conexión MQTT restablecida.")
            break
        except Exception as e:
            print(f"Error al conectar con el broker MQTT: {e}. Reintentando en 5 segundos...")
            time.sleep(5)

def enviar_estado():
    while True:
        # Verificar si las conexiones están activas
        modbus_status = "OK" if client and client.connect() else "FALLO"
        mqtt_status = "OK" if mqtt_client.is_connected() else "FALLO"

        # Enviar el estado actual por MQTT
        estado = "OK" if modbus_status == "OK" and mqtt_status == "OK" else "FALLO"
        payload = json.dumps({"status": estado})
        mqtt_client.publish(mqtt_status_topic, payload)

        print(f"Estado de comunicación publicado: {payload}")

        # Esperar 10 segundos antes de volver a verificar
        time.sleep(10)

if __name__ == "__main__":
    mqtt_client.on_connect = on_connect
    mqtt_client.on_message = on_message

    reconectar_mqtt()
    reconectar_modbus()
    pause_event.set()

    # Iniciar el hilo de escaneo de direcciones
    threading.Thread(target=escanear_direcciones, daemon=True).start()

    # Iniciar el hilo para enviar el estado de la conexión
    threading.Thread(target=enviar_estado, daemon=True).start()

    # Mantener el script en ejecución
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("Programa detenido por el usuario.")
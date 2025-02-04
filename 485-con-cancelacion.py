import time
from pymodbus.client import ModbusSerialClient
import paho.mqtt.client as mqtt
import json
import threading

# Crear un bloqueo para evitar conflictos en el uso del cliente Modbus y un evento para pausar/continuar la lectura de peso
modbus_lock = threading.Lock()
pause_event = threading.Event()

# Configuración del cliente Modbus
client = ModbusSerialClient(
    port='/dev/ttyUSB0',  # Cambia al puerto adecuado en Linux
    baudrate=9600,
    timeout=1,
    stopbits=1,
    bytesize=8,
    parity='N'
)

# Configuración del cliente MQTT
mqtt_broker = "localhost"  # IP de tu broker MQTT
mqtt_base_topic = "sensorica/bascula/peso"
mqtt_status_topic = "sensorica/bascula/status"
mqtt_dosificador_topic = "sensorica/bascula/dosifica"
mqtt_zero_topic = "sensorica/bascula/zero"
mqtt_tara_topic = "sensorica/bascula/tara"
mqtt_cancel_topic = "sensorica/bascula/cancel"

def on_connect(mqtt_client, userdata, flags, rc):
    if rc == 0:
        print("Conexión MQTT exitosa.")
        mqtt_client.subscribe(f"{mqtt_dosificador_topic}/#")
        mqtt_client.subscribe(f"{mqtt_zero_topic}/#")
        mqtt_client.subscribe(f"{mqtt_tara_topic}/#")
        mqtt_client.subscribe(f"{mqtt_cancel_topic}/#")
    else:
        print(f"Conexión MQTT fallida con código {rc}")

def on_message(mqtt_client, userdata, message):
    topic = message.topic
    try:
        # Verificar si el payload no está vacío y es decodificable
        if message.payload:
            payload = json.loads(message.payload.decode('utf-8'))
            print(f"Mensaje recibido en tópico {topic}: {payload}")

            # Pausar la lectura de pesos cuando recibes un comando
            pause_event.clear() 

            # Resto de la lógica para procesar el mensaje
            try:
                direccion_modbus = int(topic.split('/')[-1])
            except ValueError:
                print(f"Error: La dirección Modbus en el tópico {topic} no es válida.")
                pause_event.set()  
                return

            if 2 <= direccion_modbus <= 8:
                if mqtt_dosificador_topic in topic and 'value' in payload:
                    iniciar_dosificacion(direccion_modbus, payload['value'])
                elif mqtt_zero_topic in topic and 'value' in payload and payload['value'] is True:
                    hacer_cero(direccion_modbus)
                elif mqtt_tara_topic in topic and 'value' in payload and payload['value'] is True:
                    hacer_tara(direccion_modbus)
                elif mqtt_cancel_topic in topic and 'value' in payload and payload['value'] is True:
                    cancelar_dosificacion(direccion_modbus)
                elif mqtt_tara_topic in topic and 'read' in payload and payload['read'] is True:
                    valor_tara = leer_valor_tara(direccion_modbus)
                    if valor_tara is not None:
                        mqtt_topic = f"{mqtt_tara_topic}/{direccion_modbus}"
                        payload_tara = json.dumps({"tara": valor_tara})
                        mqtt_client.publish(mqtt_topic, payload_tara)
                        print(f"Publicado en MQTT - Tópico: {mqtt_topic}, Payload: {payload_tara}")
            else:
                print(f"Error: Dirección Modbus fuera del rango permitido (2-8). Dirección recibida: {direccion_modbus}")

            pause_event.set()

        else:
            print("Advertencia: El payload recibido está vacío.")
    
    except json.JSONDecodeError:
        print("Error: El payload recibido no es un JSON válido.")

def publicar_estado_operacion(direccion_modbus, operacion, estado):
    """ Publica el estado de la operación en el tópico MQTT correspondiente """
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
            # Enviar 12 primero
            client.write_register(1000, 12, slave=direccion_modbus)
            # Luego enviar 15
            client.write_register(1000, 15, slave=direccion_modbus)
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

def reconectar_modbus():
    while not client.connect():
        print("Error al conectar con el puerto Modbus. Reintentando en 5 segundos...")
        time.sleep(5)
    print("Conexión Modbus restablecida.")

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
        modbus_status = "OK" if client.connect() else "FALLO"
        mqtt_status = "OK" if mqtt_client.is_connected() else "FALLO"
        
        # Enviar el estado actual por MQTT
        estado = "OK" if modbus_status == "OK" and mqtt_status == "OK" else "FALLO"
        payload = json.dumps({"status": estado})
        mqtt_client.publish(mqtt_status_topic, payload)
        
        print(f"Estado de comunicación publicado: {payload}")
        
        # Esperar 10 segundos antes de volver a verificar
        time.sleep(10)

def escanear_y_leer_peso():
    reconectar_modbus()
    while True:
        pause_event.wait()
        with modbus_lock:
            try:
                registro_peso_base = 9
                for direccion in range(2, 8):
                    try:
                        response = client.read_input_registers(registro_peso_base, 2, slave=direccion)
                        if response.isError():
                            print(f"Sin respuesta en la dirección: {direccion}")
                        else:
                            peso_neto = (response.registers[0] << 16) + response.registers[1]
                            peso_neto /= 10.0

                            # Validar si el valor es mayor al límite permitido
                            if peso_neto > 999999:
                                peso_neto = 0  # Ajustar a 0 si el valor es inválido
                                print(f"Valor inválido recibido: {peso_neto}. Se ajustará a 0.")

                            mqtt_topic = f"{mqtt_base_topic}/{direccion}"
                            payload = json.dumps({"value": peso_neto})
                            mqtt_client.publish(mqtt_topic, payload)
                    except Exception as e:
                        print(f"Error al escanear la dirección {direccion}: {e}")
                time.sleep(0.1)
            except Exception as e:
                print(f"Error general en el proceso: {e}. Reintentando en 5 segundos...")
                reconectar_modbus()

if __name__ == "__main__":
    mqtt_client = mqtt.Client()
    mqtt_client.on_connect = on_connect
    mqtt_client.on_message = on_message

    # Iniciar la reconexión MQTT y el hilo de estado
    reconectar_mqtt()
    threading.Thread(target=enviar_estado, daemon=True).start()
    pause_event.set()

    # Iniciar la lectura de peso
    escanear_y_leer_peso()
import socket
import logging
import json
import time
import argparse
import sqlite3
import os
import requests

# Iniciar la base de datos local
LOCAL_DB = 'local_data.db'

def init_local_db():
    conn = sqlite3.connect(LOCAL_DB)
    cursor = conn.cursor()
    cursor.execute('''CREATE TABLE IF NOT EXISTS barcodes
                      (timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, 
                       barcode TEXT, 
                       order_id TEXT)''')
    conn.commit()
    conn.close()

init_local_db()

def save_local_data(barcode_data, order_id):
    conn = sqlite3.connect(LOCAL_DB)
    cursor = conn.cursor()
    cursor.execute('INSERT INTO barcodes (barcode, order_id) VALUES (?, ?)', (barcode_data, order_id))
    conn.commit()
    conn.close()

# Crear el parser para extraer los argumentos desde la línea de comandos
parser = argparse.ArgumentParser(description='Configurar el escáner.')

# Añadir los argumentos
parser.add_argument('SCANNER_IP', type=str, help='IP del escáner')
parser.add_argument('SCANNER_PORT', type=int, help='Puerto del escáner')
parser.add_argument('API_TOKEN', type=str, help='Token Api')
parser.add_argument('MACHINE_ID', type=str, help='Id de maquina')

# Parsear los argumentos
args = parser.parse_args()

# Extraer los argumentos
SCANNER_IP = args.SCANNER_IP
SCANNER_PORT = args.SCANNER_PORT
API_TOKEN = args.API_TOKEN
MACHINE_ID = args.MACHINE_ID

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# Variables para control de reconexión
RECONNECT_DELAY = 5  # Segundos

# URL y token para la API
API_URL = "https://boisolo.dev/api/barcode"


def connect_to_scanner():
    while True:
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.connect((SCANNER_IP, SCANNER_PORT))
            logging.info(f"Conexión exitosa al lector de código de barras en {SCANNER_IP}:{SCANNER_PORT}")
            s.settimeout(10)  # Establecer un timeout para la recepción de datos
            return s
        except (ConnectionRefusedError, OSError) as e:
            logging.error(f"Error al conectar con el lector de código de barras: {e}")
            logging.info(f"Reintentando conexión en {RECONNECT_DELAY} segundos...")
            time.sleep(RECONNECT_DELAY)

def check_connection(socket):
    try:
        socket.sendall(b'\0')
        return True
    except OSError:
        return False

def main_loop():
    while True:
        scanner_socket = connect_to_scanner()
        last_data_time = time.time()

        while True:
            try:
                if not check_connection(scanner_socket):
                    logging.warning("Conexión perdida con el lector de código de barras")
                    scanner_socket.close()
                    break  # Salir del bucle interno y reconectar

                data = scanner_socket.recv(1024)
                if data:
                    last_data_time = time.time()  # Resetear el temporizador al recibir datos

                    barcode_data = data.decode('utf-8').strip()
                    logging.info(f"Código de barras recibido: {barcode_data}")
                    save_local_data(barcode_data, None)
                    logging.info(f"Se ha salvado en Sqlite3: {barcode_data}")

                    # Enviar datos a la API
                    payload = {
                        "barcoder": barcode_data,
                        "token": API_TOKEN,
                        "machine_id": MACHINE_ID
                    }
                    response = requests.post(API_URL, headers={"Content-Type": "application/json"}, data=json.dumps(payload))
                    if response.status_code == 200:
                        logging.info(f"Datos enviados exitosamente a la API: {payload}")
                    else:
                        logging.error(f"Error al enviar datos a la API. Código de estado: {response.status_code}, Respuesta: {response.text}")

                if time.time() - last_data_time > 30:
                    logging.warning("Timeout: No se recibieron datos del lector de código de barras en 30 segundos")
                    scanner_socket.close()
                    break  # Salir del bucle interno y reconectar

            except socket.timeout:
                logging.warning("Timeout de socket: No se recibieron datos del lector de código de barras")
            except (OSError, ConnectionError) as e:
                logging.error(f"Error en la conexión: {e}")
                scanner_socket.close()
                break  # Salir del bucle interno y reconectar

if __name__ == "__main__":
    try:
        main_loop()
    except KeyboardInterrupt:
        logging.info("Interrupción del usuario. Saliendo...")

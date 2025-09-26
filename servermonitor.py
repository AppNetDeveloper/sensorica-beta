import os
import psutil
import requests
import time
import urllib3

# Deshabilitar warnings de certificados no verificados
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Configuración de la API (datos que funcionan con el comando curl)
API_URL = "https://aixmart.net/api/server-monitor-store"  # URL correcta
TOKEN_FILE = "/var/www/html/storage/api_token.txt"  # Ruta del archivo donde se guarda el token

def get_api_token():
    """Lee y devuelve el token almacenado en TOKEN_FILE."""
    if os.path.exists(TOKEN_FILE):
        with open(TOKEN_FILE, 'r') as f:
            return f.read().strip()
    return ""

def collect_metrics():
    cpu_usage = psutil.cpu_percent(interval=1)
    memoria = psutil.virtual_memory()
    total_memory = memoria.total
    memory_free = memoria.free
    memory_used = memoria.used
    memory_used_percent = memoria.percent
    disco = psutil.disk_usage('/')
    disk_usage_percent = disco.percent

    # Leer el token desde el archivo
    api_token = get_api_token()

    payload = {
        "token": api_token,
        "total_memory": total_memory,
        "memory_free": memory_free,
        "memory_used": memory_used,
        "memory_used_percent": memory_used_percent,
        "disk": disk_usage_percent,
        "cpu": cpu_usage
    }
    return payload

def send_data(payload):
    headers = {"Content-Type": "application/json"}
    try:
        # Se añade verify=False para ignorar la verificación del certificado
        response = requests.post(API_URL, json=payload, headers=headers, verify=False)
        return response
    except Exception as e:
        print("Error al enviar la petición:", e)
        return None

def main():
    data = collect_metrics()
    print("Enviando los siguientes datos a la API:")
    print(data)
    response = send_data(data)
    if response:
        if response.status_code == 201:
            print("Datos almacenados exitosamente.")
        else:
            print("Error al almacenar datos. Código de estado:", response.status_code)
            print("Respuesta:", response.text)
    else:
        print("No se pudo enviar la petición a la API.")

if __name__ == '__main__':
    try:
        while True:
            main()
            print("Esperando 30 segundos para el siguiente envío...")
            time.sleep(30)
    except KeyboardInterrupt:
        print("Script interrumpido por el usuario. Saliendo...")

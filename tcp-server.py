import socket
import threading
import time

clients = []

def handle_client(conn, addr):
    print(f"Nuevo cliente conectado desde {addr}")
    while True:
        try:
            data = conn.recv(1024)
            if not data:
                break
            message = data.decode('utf-8')
            print(f"Mensaje recibido de {addr}: {message}")
            
            # Enviar el mensaje a todos los clientes conectados excepto al emisor
            for client in clients:
                if client != conn:
                    try:
                        client.sendall(data)
                    except:
                        clients.remove(client)
        except:
            break
    clients.remove(conn)
    conn.close()

def iniciar_servidor():
    HOST = '0.0.0.0'  # Escucha en todas las interfaces de red disponibles
    PORT = 8000      # Puerto en el que escuchará el servidor

    while True:
        try:
            with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                s.bind((HOST, PORT))
                s.listen()
                print(f'Servidor escuchando en {HOST}:{PORT}')
                
                while True:
                    conn, addr = s.accept()
                    clients.append(conn)
                    client_thread = threading.Thread(target=handle_client, args=(conn, addr))
                    client_thread.start()
        except Exception as e:
            print(f"Error en el servidor: {e}")
            print("Reiniciando el servidor en 5 segundos...")
            time.sleep(5)  # Esperar 5 segundos antes de reiniciar

if __name__ == "__main__":
    iniciar_servidor()
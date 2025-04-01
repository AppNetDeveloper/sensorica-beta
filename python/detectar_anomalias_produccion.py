import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
import pymysql
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import load_model
import joblib
import time

from dotenv import dotenv_values

# -------------------------------------------------------------------------
# 1. Carga de variables del .env
# -------------------------------------------------------------------------
CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
env_path = os.path.join(CURRENT_DIR, '../.env')
config = dotenv_values(env_path)

DB_HOST = config.get("DB_HOST", "127.0.0.1")
DB_PORT = int(config.get("DB_PORT", 3306))
DB_NAME = config.get("DB_DATABASE", "boisolo")
DB_USER = config.get("DB_USERNAME", "root")
DB_PASS = config.get("DB_PASSWORD", "")

def conectar_db():
    """
    Crea la conexión a MySQL usando la configuración del .env
    """
    return pymysql.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        port=DB_PORT
    )

def detectar_anomalias():
    """
    Monitorea los últimos 15 minutos:
      - tipo=0 => usa time_11 y forzar time_00=0
      - tipo>0 => usa time_00 y forzar time_11=0
      - Si no hay registros (o <2) => inactivo (solo avisa para tipo=0)
      - Si el MSE excede el threshold => anomalía
      - Si no, OK
    """
    conn = conectar_db()
    fecha_inicio = datetime.now() - timedelta(minutes=15)

    # 1) Carga de sensores
    sensores_df = pd.read_sql("""
        SELECT id, production_line_id, sensor_type
        FROM sensors
        WHERE sensor_type IN (0,1,2,3,4)
    """, conn)

    # 2) Carga de registros de sensor_counts (últimos 15 min)
    counts_df = pd.read_sql(f"""
        SELECT 
            sensor_id, 
            time_11, 
            time_00, 
            created_at
        FROM sensor_counts
        WHERE created_at >= '{fecha_inicio.strftime('%Y-%m-%d %H:%M:%S')}'
    """, conn)
    conn.close()

    # Unir con info de línea y tipo
    counts_df = counts_df.merge(sensores_df, left_on='sensor_id', right_on='id', how='inner')
    counts_df.drop(columns=['id'], inplace=True)

    # Convertir a numérico
    counts_df['time_11'] = pd.to_numeric(counts_df['time_11'], errors='coerce')
    counts_df['time_00'] = pd.to_numeric(counts_df['time_00'], errors='coerce')

    # Para llevar control de sensores que sí tuvieron registros
    sensores_analizados = []

    # -----------------------------
    # PROCESAR POR (LÍNEA, TIPO)
    # -----------------------------
    for (line_id, s_type), group_line_type in counts_df.groupby(['production_line_id', 'sensor_type']):
        # Rutas de modelo y scaler
        model_path = f"models/line_{line_id}_type_{s_type}_autoencoder.h5"
        scaler_path = f"models/line_{line_id}_type_{s_type}_scaler.pkl"

        # Verificar existencia
        if not os.path.exists(model_path) or not os.path.exists(scaler_path):
            print(f"⚠️  No hay modelo para Línea={line_id}, Tipo={s_type}")
            continue

        # Cargar modelo y scaler
        model = load_model(model_path, compile=False)
        scaler = joblib.load(scaler_path)

        # -------------
        # Por cada sensor en este (line_id, tipo)
        # -------------
        for sensor_id, sensor_data in group_line_type.groupby('sensor_id'):
            # Marcamos que este sensor sí apareció
            sensores_analizados.append(sensor_id)

            # Si NO hay suficientes registros => inactivo (solo avisar si es tipo=0)
            if len(sensor_data) < 2:
                if s_type == 0:
                    print(f"🚫 Inactivo | Línea {line_id} | Sensor {sensor_id} (pocos registros)")
                # Si NO es tipo 0, simplemente no avisamos
                continue

            # Calcular estadísticos
            mean_11 = sensor_data['time_11'].mean()
            std_11  = sensor_data['time_11'].std(ddof=1) or 0.0
            mean_00 = sensor_data['time_00'].mean()
            std_00  = sensor_data['time_00'].std(ddof=1) or 0.0

            # Ajustar según tipo:
            if s_type == 0:
                # Mantener time_11, forzar 00
                mean_00 = 0.0
                std_00  = 0.0
            else:
                # Mantener time_00, forzar 11
                mean_11 = 0.0
                std_11  = 0.0

            # Empaquetar features
            row = {
                'mean_time_11': mean_11,
                'std_time_11': std_11,
                'mean_time_00': mean_00,
                'std_time_00': std_00
            }
            X = pd.DataFrame([row])

            # Escalar
            X_scaled = scaler.transform(X)

            # Predicción con autoencoder
            pred = model.predict(X_scaled, verbose=0)
            mse = np.mean((X_scaled - pred)**2)

            # Umbral (puedes ajustar según tu entrenamiento)
            threshold = 0.01

            if mse > threshold:
                print(f"🚨 Anomalía | Línea {line_id} | Sensor {sensor_id}, MSE={mse:.5f}")
            else:
                print(f"✅ OK       | Línea {line_id} | Sensor {sensor_id}, MSE={mse:.5f}")

    # -----------------------------
    # Revisar si hay sensores TIPO 0 que NO aparecieron en los últimos 15 min
    # -----------------------------
    # Tomamos solo los de tipo 0
    sensores_tipo0 = sensores_df[sensores_df['sensor_type'] == 0]
    # filtramos los que NO están en sensores_analizados
    inactivos = sensores_tipo0[~sensores_tipo0['id'].isin(sensores_analizados)]

    for _, row_sens in inactivos.iterrows():
        sensor_id = row_sens['id']
        line_id = row_sens['production_line_id']
        print(f"🚫 Inactivo | Línea {line_id} | Sensor {sensor_id} (sin actividad en 15 min)")

# -------------------------------------------------------------------------
# 2. Bucle infinito
# -------------------------------------------------------------------------
if __name__ == "__main__":
    print("🚀 Iniciando DETECCIÓN: solo avisa inactividad si type=0 ...")
    while True:
        detectar_anomalias()
        time.sleep(60)

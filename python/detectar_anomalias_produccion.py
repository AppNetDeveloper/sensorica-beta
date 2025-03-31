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

# Cargar variables del archivo .env de Laravel
env_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '../.env'))
config = dotenv_values(env_path)

DB_HOST = config.get("DB_HOST", "127.0.0.1")
DB_PORT = int(config.get("DB_PORT", 3306))
DB_NAME = config.get("DB_DATABASE", "boisolo")
DB_USER = config.get("DB_USERNAME", "root")
DB_PASS = config.get("DB_PASSWORD", "")

def conectar_db():
    return pymysql.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        port=DB_PORT
    )

# Identifica la causa de la anomalía
def causa_principal(X_scaled, pred, columnas):
    errores = np.abs(X_scaled - pred)
    error_por_campo = dict(zip(columnas, errores[0]))

    campo_max = max(error_por_campo, key=error_por_campo.get)
    valor = error_por_campo[campo_max]

    causas = {
        "avg_time_11": "tiempo promedio de actividad muy alto",
        "std_time_11": "variabilidad anormal en actividad",
        "avg_time_01": "transición de 0 a 1 anormalmente larga",
        "avg_time_10": "transición de 1 a 0 anormalmente larga",
        "conteos_porcentaje": "porcentaje de conteos fuera de lo normal",
    }

    descripcion = causas.get(campo_max, "desviación en comportamiento")
    return campo_max, valor, descripcion

# Función principal
def detectar_anomalias():
    conn = conectar_db()
    fecha_inicio = datetime.now() - timedelta(minutes=15)

    sensores_df = pd.read_sql("""
        SELECT id, production_line_id, sensor_type
        FROM sensors
        WHERE sensor_type IN (0,1,2,3,4)
    """, conn)

    counts_df = pd.read_sql(f"""
        SELECT sensor_id, value, time_11, time_01, time_10, created_at
        FROM sensor_counts
        WHERE created_at >= '{fecha_inicio.strftime('%Y-%m-%d %H:%M:%S')}'
    """, conn)
    conn.close()

    counts_df = counts_df.merge(sensores_df, left_on='sensor_id', right_on='id', how='inner')
    counts_df.drop(columns=['id'], inplace=True)
    counts_df['created_at'] = pd.to_datetime(counts_df['created_at'])

    sensores_analizados = []

    for (line_id, tipo), group in counts_df.groupby(['production_line_id', 'sensor_type']):
        model_path = f"models/line_{line_id}_type_{tipo}_autoencoder.h5"
        scaler_path = f"models/line_{line_id}_type_{tipo}_scaler.save"

        if not os.path.exists(model_path) or not os.path.exists(scaler_path):
            print(f"⚠️  Modelo no encontrado para Línea {line_id} / Tipo {tipo}")
            continue

        model = load_model(model_path, compile=False)
        scaler = joblib.load(scaler_path)

        sensores = group['sensor_id'].unique()
        for sensor_id in sensores:
            sensores_analizados.append(sensor_id)

            sensor_data = group[group['sensor_id'] == sensor_id]
            if len(sensor_data) < 5:
                continue

            values = sensor_data['value'].astype(int)
            time_11 = sensor_data['time_11'].dropna()
            time_01 = sensor_data['time_01'].dropna()
            time_10 = sensor_data['time_10'].dropna()

            row = {
                'avg_time_11': time_11.mean(),
                'std_time_11': time_11.std(),
                'avg_time_01': time_01.mean() if not time_01.empty else 0,
                'avg_time_10': time_10.mean() if not time_10.empty else 0,
                'conteos_porcentaje': values.sum() / len(values)
            }

            X = pd.DataFrame([row]).fillna(0)
            columnas = X.columns
            X_scaled = scaler.transform(X)
            pred = model.predict(X_scaled, verbose=0)
            error = np.mean(np.power(X_scaled - pred, 2))

            threshold = 0.02
            if error > threshold:
                campo, valor, causa = causa_principal(X_scaled, pred, columnas)
                print(f"🚨 Anomalía | Línea Id: {line_id} | Tipo {tipo} | Sensor {sensor_id} | Error: {error:.5f}")
                print(f"   ↪️  Causa probable: {causa} ({campo}, desviación: {valor:.4f})")
            else:
                print(f"✅ OK       | Línea Id: {line_id} | Tipo {tipo} | Sensor {sensor_id} | Error: {error:.5f}")

    # 🚫 Verificar sensores de tipo 0 sin actividad
    sensores_tipo_0 = sensores_df[sensores_df['sensor_type'] == 0]
    sensores_0_analizados = set(sensores_analizados)

    for _, row in sensores_tipo_0.iterrows():
        sensor_id = row['id']
        line_id = row['production_line_id']
        if sensor_id not in sensores_0_analizados:
            print(f"🚫 Inactivo | Línea {line_id} | Sensor {sensor_id} | sin actividad en los últimos 15 minutos")

# ⏱️ Bucle infinito cada 60 segundos
print("🧠 Monitor de sensores en tiempo real iniciado (cada 60 segundos)...")
while True:
    detectar_anomalias()
    time.sleep(60)

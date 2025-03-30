import pymysql
import pandas as pd
import numpy as np
import time
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import load_model
import joblib
from datetime import datetime, timedelta

from dotenv import load_dotenv
import os

# Cargar las variables desde el .env de Laravel
load_dotenv(dotenv_path='../.env')

# Obtener credenciales desde las variables de entorno
db_config = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'boisolo'),
    'port': int(os.getenv('DB_PORT', 3306))
}

# Cargar modelo y scaler entrenados previamente
model = load_model("models/shift_autoencoder.h5", compile=False)

scaler = joblib.load("models/shift_scaler.save")

def detectar_anomalias():
    conn = pymysql.connect(**db_config)
    query = """
    SELECT production_line_id, created_at, action
    FROM shift_history
    WHERE type = 'shift'
      AND created_at >= NOW() - INTERVAL 1 DAY
    ORDER BY production_line_id, created_at
    """
    df = pd.read_sql(query, conn)
    conn.close()

    shift_sessions = []
    for line_id, group in df.groupby('production_line_id'):
        events = group.sort_values('created_at')
        shift_start = None
        for _, row in events.iterrows():
            if row['action'] == 'start':
                shift_start = row['created_at']
            elif row['action'] == 'end' and shift_start:
                shift_end = row['created_at']
                duration = (shift_end - shift_start).total_seconds() / 3600
                shift_sessions.append({
                    'production_line_id': line_id,
                    'shift_start_hour': shift_start.hour + shift_start.minute / 60,
                    'shift_end_hour': shift_end.hour + shift_end.minute / 60,
                    'duration': duration,
                    'start': shift_start,
                    'end': shift_end
                })
                shift_start = None

    df_shift = pd.DataFrame(shift_sessions)
    if df_shift.empty:
        print(f"[{datetime.now()}] No hay turnos para analizar.")
        return

    X = df_shift[['shift_start_hour', 'shift_end_hour', 'duration']]
    X_scaled = scaler.transform(X)
    X_pred = model.predict(X_scaled)
    mse = np.mean(np.power(X_scaled - X_pred, 2), axis=1)

    threshold = np.percentile(mse, 95)  # Umbral dinámico
    df_shift['anomaly_score'] = mse
    df_shift['is_anomaly'] = df_shift['anomaly_score'] > threshold

    for _, row in df_shift[df_shift['is_anomaly']].iterrows():
        print(f"[{datetime.now()}] ⚠️ Anomalía detectada en línea {row['production_line_id']}:")
        print(f"   Inicio: {row['start']} | Fin: {row['end']} | Duración: {row['duration']:.2f}h")
        print(f"   Score: {row['anomaly_score']:.6f} (umbral: {threshold:.6f})")

# Loop cada 60 segundos
print("🧠 Monitor de turnos iniciado. Escaneando cada 60 segundos...")
while True:
    detectar_anomalias()
    time.sleep(60)

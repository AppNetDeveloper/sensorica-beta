import pymysql
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense
from tensorflow.keras.callbacks import EarlyStopping
import joblib
import os

# Configuración de conexión a base de datos
conn = pymysql.connect(
    host='localhost',
    user='root',
    password='Cvlss2101281613',
    database='boisolo',
    port=3306
)

# Usar los últimos 15 días
fecha_limite = datetime.now() - timedelta(days=15)

# Cargar sensores de tipo 0 a 4
sensores_query = """
SELECT id, production_line_id, sensor_type
FROM sensors
WHERE sensor_type IN (0, 1, 2, 3, 4)
"""
sensores_df = pd.read_sql(sensores_query, conn)

# Cargar eventos de sensor_counts
counts_query = f"""
SELECT sensor_id, value, time_11, time_01, time_10, created_at
FROM sensor_counts
WHERE created_at >= '{fecha_limite.strftime('%Y-%m-%d %H:%M:%S')}'
"""
counts_df = pd.read_sql(counts_query, conn)
conn.close()

# Unir sensores con sus líneas y tipo
counts_df = counts_df.merge(sensores_df, left_on='sensor_id', right_on='id', how='inner')
counts_df.drop(columns=['id'], inplace=True)

# Asegurar formato datetime
counts_df['created_at'] = pd.to_datetime(counts_df['created_at'])

# Crear carpeta para guardar modelos
os.makedirs("models", exist_ok=True)

# Configuración de ventana temporal (15 minutos)
ventana_min = 15
ventana = timedelta(minutes=ventana_min)

# Entrenar modelo por línea de producción + tipo de sensor
for (line_id, tipo), group in counts_df.groupby(['production_line_id', 'sensor_type']):
    sensores = group['sensor_id'].unique()
    features = []

    for sensor_id in sensores:
        sensor_data = group[group['sensor_id'] == sensor_id].sort_values(by='created_at')
        start = sensor_data['created_at'].min()
        end = sensor_data['created_at'].max()
        actual = start

        while actual < end:
            ventana_data = sensor_data[(sensor_data['created_at'] >= actual) &
                                       (sensor_data['created_at'] < actual + ventana)]
            if len(ventana_data) < 5:
                actual += ventana
                continue

            values = ventana_data['value'].astype(int)
            time_11 = ventana_data['time_11'].dropna()
            time_01 = ventana_data['time_01'].dropna()
            time_10 = ventana_data['time_10'].dropna()

            features.append({
                'sensor_id': sensor_id,
                'avg_time_11': time_11.mean(),
                'std_time_11': time_11.std(),
                'avg_time_01': time_01.mean() if not time_01.empty else 0,
                'avg_time_10': time_10.mean() if not time_10.empty else 0,
                'conteos': values.sum(),
                'total_registros': len(values),
                'conteos_porcentaje': values.sum() / len(values)
            })

            actual += ventana

    df_feat = pd.DataFrame(features)
    if df_feat.empty:
        print(f"❌ Línea {line_id} / Tipo {tipo}: sin datos suficientes.")
        continue

    # Normalizar datos
    X = df_feat[['avg_time_11', 'std_time_11', 'avg_time_01', 'avg_time_10', 'conteos_porcentaje']].fillna(0)
    scaler = MinMaxScaler()
    X_scaled = scaler.fit_transform(X)

    # Crear modelo autoencoder
    input_dim = X_scaled.shape[1]
    model = Sequential([
        Dense(8, activation='relu', input_shape=(input_dim,)),
        Dense(4, activation='relu'),
        Dense(8, activation='relu'),
        Dense(input_dim, activation='linear')
    ])
    model.compile(optimizer='adam', loss='mse')
    early_stop = EarlyStopping(monitor='loss', patience=10, restore_best_weights=True)
    model.fit(X_scaled, X_scaled, epochs=100, batch_size=32, verbose=1, callbacks=[early_stop])

    # Guardar modelo y scaler
    model_path = f"models/line_{line_id}_type_{tipo}_autoencoder.h5"
    scaler_path = f"models/line_{line_id}_type_{tipo}_scaler.save"
    model.save(model_path)
    joblib.dump(scaler, scaler_path)

    print(f"✅ Modelo entrenado para Línea {line_id} / Tipo {tipo} con {len(df_feat)} muestras.")

print("🎯 Entrenamiento completo para todos los sensores por ventana.")

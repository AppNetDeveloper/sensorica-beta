import os
import logging
from dotenv import load_dotenv
import pymysql
import pandas as pd
import numpy as np
from datetime import datetime
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense
from tensorflow.keras.callbacks import EarlyStopping
import joblib

# -------------------------------------------------------------------------
# 1. Configuración de logging
# -------------------------------------------------------------------------
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

# -------------------------------------------------------------------------
# 2. Carga de variables de entorno
# -------------------------------------------------------------------------
load_dotenv(dotenv_path='../.env')  # Ajusta ruta si tu .env está en otro lugar

db_config = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'boisolo'),
    'port': int(os.getenv('DB_PORT', 3306))
}

# -------------------------------------------------------------------------
# 3. Función de conexión y carga de datos
# -------------------------------------------------------------------------
def cargar_datos():
    """
    Conecta a la DB y carga:
      - Los sensores (id, production_line_id, sensor_type).
      - La tabla sensor_counts con time_11, time_00, created_at (sin filtrar).
    Converte time_11 y time_00 a numéricos, eliminando filas donde ambos
    son NaN.
    """
    connection = pymysql.connect(**db_config)
    try:
        # A) Cargar sensores
        sensores_query = """
        SELECT id, production_line_id, sensor_type
        FROM sensors
        WHERE sensor_type IN (0, 1, 2, 3, 4)
        """
        sensores_df = pd.read_sql(sensores_query, connection)

        # B) Cargar sensor_counts (todo el histórico)
        counts_query = """
        SELECT sensor_id, time_11, time_00, created_at
        FROM sensor_counts
        """
        counts_df = pd.read_sql(counts_query, connection)
    finally:
        connection.close()

    # Convertir a numérico
    for col in ['time_11', 'time_00']:
        counts_df[col] = pd.to_numeric(counts_df[col], errors='coerce')

    # Eliminar filas donde ambos sean NaN
    counts_df.dropna(subset=['time_11', 'time_00'], how='all', inplace=True)

    return sensores_df, counts_df

# -------------------------------------------------------------------------
# 4. Preprocesamiento y generación de features
# -------------------------------------------------------------------------
def generar_features(sensores_df, counts_df):
    """
    Une sensors con sensor_counts y crea agregaciones diarias.
    Luego, si sensor_type = 0 => solo dejamos time_11 (time_00 a 0).
                 sensor_type != 0 => solo dejamos time_00 (time_11 a 0).
    Final: Las columnas resultantes (mean_time_11, std_time_11,
    mean_time_00, std_time_00) están definidas para todos.
    """
    # Merge
    df = counts_df.merge(sensores_df, left_on='sensor_id', right_on='id', how='inner')
    df.drop(columns=['id'], inplace=True)

    # Asegurar datetime
    df['created_at'] = pd.to_datetime(df['created_at'], errors='coerce')
    # Dia para la agregación
    df['fecha'] = df['created_at'].dt.date

    feature_rows = []
    for (line_id, s_type), group_line_type in df.groupby(['production_line_id', 'sensor_type']):
        for sensor_id, sensor_data in group_line_type.groupby('sensor_id'):
            # Agregar por día
            daily_agg = sensor_data.groupby('fecha').agg({
                'time_11': ['mean', 'std'],
                'time_00': ['mean', 'std']
            }).reset_index()

            daily_agg.columns = [
                'fecha',
                'mean_time_11', 'std_time_11',
                'mean_time_00', 'std_time_00'
            ]

            # Asignar IDs
            daily_agg['sensor_id'] = sensor_id
            daily_agg['production_line_id'] = line_id
            daily_agg['sensor_type'] = s_type

            # ================ CLAVE: Ajustar según el tipo ================
            # Si el sensor es tipo 0, dejamos time_11 y forzamos a 0 time_00
            if s_type == 0:
                daily_agg['mean_time_00'] = 0.0
                daily_agg['std_time_00']  = 0.0
            else:
                # Si NO es tipo 0, forzamos a 0 time_11
                daily_agg['mean_time_11'] = 0.0
                daily_agg['std_time_11']  = 0.0
            # =============================================================

            feature_rows.append(daily_agg)

    if not feature_rows:
        logging.warning("No se generaron datos de features.")
        return pd.DataFrame()

    df_feat = pd.concat(feature_rows, ignore_index=True)

    # Rellenar NaN (e.g. std_time_11 si 1 solo registro)
    df_feat.fillna(0, inplace=True)

    return df_feat

# -------------------------------------------------------------------------
# 5. Entrenar modelos
# -------------------------------------------------------------------------
def entrenar_modelos(df_feat, output_dir="models"):
    """
    Entrena un autoencoder para cada (line_id, sensor_type), usando
    mean_time_11, std_time_11, mean_time_00, std_time_00 como columnas
    (4 features).
    """
    os.makedirs(output_dir, exist_ok=True)

    grouped = df_feat.groupby(['production_line_id', 'sensor_type'])

    for (line_id, s_type), group in grouped:
        # Extraer features que ahora SIEMPRE son 4
        X = group[['mean_time_11', 'std_time_11',
                   'mean_time_00', 'std_time_00']]

        if len(X) < 10:
            logging.warning(f"Línea {line_id}, Tipo {s_type}: muy pocos datos ({len(X)}) para entrenar.")
            continue

        # Escalado
        scaler = MinMaxScaler()
        X_scaled = scaler.fit_transform(X)

        # Partición train/val
        X_train, X_val = train_test_split(X_scaled, test_size=0.2, random_state=42)

        # Arquitectura simple autoencoder
        input_dim = X_train.shape[1]
        model = Sequential([
            Dense(8, activation='relu', input_shape=(input_dim,)),
            Dense(4, activation='relu'),
            Dense(8, activation='relu'),
            Dense(input_dim, activation='linear')
        ])
        model.compile(optimizer='adam', loss='mse')

        # EarlyStopping
        early_stop = EarlyStopping(monitor='val_loss', patience=10, restore_best_weights=True)

        # Entrenar
        history = model.fit(
            X_train, X_train,
            epochs=100,
            batch_size=32,
            validation_data=(X_val, X_val),
            callbacks=[early_stop],
            verbose=1
        )

        # Guardar con nombres fijos (sin timestamp)
        model_path = os.path.join(output_dir, f"line_{line_id}_type_{s_type}_autoencoder.h5")
        scaler_path = os.path.join(output_dir, f"line_{line_id}_type_{s_type}_scaler.pkl")

        model.save(model_path)
        joblib.dump(scaler, scaler_path)

        logging.info(
            f"Entrenado para línea={line_id}, tipo={s_type}, muestras={len(X)}. "
            f"Guardado en {model_path}"
        )

# -------------------------------------------------------------------------
# 6. Script principal
# -------------------------------------------------------------------------
if __name__ == "__main__":
    logging.info("Iniciando ENTRENAMIENTO: tipo 0 usa time_11, otros usan time_00.")

    # 1) Cargar datos
    sensores_df, counts_df = cargar_datos()
    logging.info(f"Sensores: {len(sensores_df)}. Registros limpios: {len(counts_df)}.")

    # 2) Generar features
    df_features = generar_features(sensores_df, counts_df)
    if df_features.empty:
        logging.error("No se generaron features. Abortando.")
        exit()

    # 3) Entrenar
    entrenar_modelos(df_features, output_dir="models")

    logging.info("Entrenamiento COMPLETADO.")

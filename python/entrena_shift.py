import pymysql
import pandas as pd
import numpy as np
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense
from tensorflow.keras.callbacks import EarlyStopping
import joblib
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

# Abrir la conexión a la base de datos
conn = pymysql.connect(**db_config)
query = """
SELECT production_line_id, created_at, action
FROM shift_history
WHERE type = 'shift'
ORDER BY production_line_id, created_at
"""

df = pd.read_sql(query, conn)
conn.close()

# Construir sesiones de turnos válidas
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
                'duration': duration
            })
            shift_start = None

# Convertir a DataFrame
df_shift = pd.DataFrame(shift_sessions)

# Normalizar datos
scaler = MinMaxScaler()
X_scaled = scaler.fit_transform(df_shift[['shift_start_hour', 'shift_end_hour', 'duration']])

# Modelo autoencoder
input_dim = X_scaled.shape[1]
model = Sequential([
    Dense(8, activation='relu', input_shape=(input_dim,)),
    Dense(4, activation='relu'),
    Dense(8, activation='relu'),
    Dense(input_dim, activation='linear')
])
model.compile(optimizer='adam', loss='mse')

# Entrenar
early_stop = EarlyStopping(monitor='loss', patience=10, restore_best_weights=True)
model.fit(X_scaled, X_scaled, epochs=100, batch_size=16, verbose=1, callbacks=[early_stop])

# Guardar modelo y scaler
os.makedirs("models", exist_ok=True)
model.save("models/shift_autoencoder.h5")
joblib.dump(scaler, "models/shift_scaler.save")

print("Modelo entrenado y guardado correctamente.")

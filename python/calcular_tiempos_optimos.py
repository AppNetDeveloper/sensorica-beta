import pymysql
import pandas as pd
from collections import Counter
import time
from datetime import datetime

def analizar_optimos():
    conn = pymysql.connect(
        host='localhost',
        user='root',
        password='Cvlss2101281613',
        database='boisolo',
        port=3306
    )
    cursor = conn.cursor()

    cursor.execute("""
    CREATE TABLE IF NOT EXISTS optimal_sensor_times (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        sensor_id BIGINT NOT NULL,
        sensor_type INT NOT NULL,
        production_line_id BIGINT,
        model_product VARCHAR(255) NOT NULL,
        optimal_time FLOAT NOT NULL,
        muestras_validas INT NOT NULL,
        repeticiones INT NOT NULL,
        tipo_analisis VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(sensor_id, model_product)
    )
    """)
    conn.commit()

    sensores = pd.read_sql("""
        SELECT id AS sensor_id, sensor_type, production_line_id
        FROM sensors
    """, conn)

    min_real = 1
    max_real = 10000
    min_porcentaje = 0.04

    for _, s in sensores.iterrows():
        sensor_id = s['sensor_id']
        sensor_type = s['sensor_type']
        production_line_id = s['production_line_id']
        campo = 'time_11' if sensor_type == 0 else 'time_00'

        df = pd.read_sql(f"""
            SELECT model_product, {campo}
            FROM sensor_counts
            WHERE sensor_id = {sensor_id}
              AND model_product IS NOT NULL
              AND {campo} >= {min_real}
              AND {campo} <= {max_real}
        """, conn)

        if df.empty:
            continue

        df[campo] = pd.to_numeric(df[campo], errors='coerce')

        for model_product, group in df.groupby('model_product'):
            tiempos = group[campo].dropna()
            if len(tiempos) < 10:
                continue

            redondeados = tiempos.apply(lambda x: round(x / 100.0) * 100)
            redondeados = redondeados[redondeados >= 100]
            if redondeados.empty:
                continue

            conteo = Counter(redondeados)
            valor_moda_ms, repeticiones = conteo.most_common(1)[0]
            frecuencia = repeticiones / len(tiempos)

            if frecuencia < min_porcentaje:
                continue

            valor_moda_s = valor_moda_ms / 1000  # ✅ Convertir a segundos

            cursor.execute("""
                SELECT id, optimal_time FROM optimal_sensor_times
                WHERE sensor_id = %s AND model_product = %s
            """, (sensor_id, model_product))
            existente = cursor.fetchone()

            if existente:
                id_existente, actual = existente
                if valor_moda_s < actual:
                    cursor.execute("""
                        UPDATE optimal_sensor_times
                        SET optimal_time = %s, muestras_validas = %s, repeticiones = %s,
                            tipo_analisis = %s, production_line_id = %s, created_at = NOW()
                        WHERE id = %s
                    """, (
                        valor_moda_s, len(tiempos), repeticiones, campo,
                        production_line_id, id_existente
                    ))
                    print(f"[{datetime.now()}] ✏️ Actualizado sensor {sensor_id} / modelo {model_product} a {valor_moda_s:.2f} s")
                else:
                    print(f"[{datetime.now()}] ⚠️ No actualizado sensor {sensor_id} / modelo {model_product} (óptimo actual mejor: {actual} s)")
            else:
                cursor.execute("""
                    INSERT INTO optimal_sensor_times
                    (sensor_id, sensor_type, production_line_id, model_product, optimal_time,
                     muestras_validas, repeticiones, tipo_analisis)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                """, (
                    sensor_id, sensor_type, production_line_id, model_product,
                    valor_moda_s, len(tiempos), repeticiones, campo
                ))
                print(f"[{datetime.now()}] ✅ Guardado sensor {sensor_id} / modelo {model_product} con óptimo: {valor_moda_s:.2f} s")

    conn.commit()
    conn.close()
    print(f"[{datetime.now()}] ✅ Análisis completo.\n")

# ⏱️ Ejecutar cada 10 minutos
while True:
    analizar_optimos()
    time.sleep(600)


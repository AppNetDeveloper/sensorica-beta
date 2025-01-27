@extends('layouts.admin')

@section('title', 'Configuración de Upload Stats')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Configuración de Upload Stats') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Card principal con sombra --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Configuración de Upload Stats</h4>
                </div>
                <div class="card-body">
                    {{-- Formulario de edición --}}
                    <form id="upload-stats-form">
                        <div class="form-group">
                            <label for="mysql_server">Servidor MySQL</label>
                            <input type="text" id="mysql_server" name="mysql_server" class="form-control" value="{{ env('MYSQL_SERVER') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_port">Puerto MySQL</label>
                            <input type="text" id="mysql_port" name="mysql_port" class="form-control" value="{{ env('MYSQL_PORT') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_db">Base de Datos MySQL</label>
                            <input type="text" id="mysql_db" name="mysql_db" class="form-control" value="{{ env('MYSQL_DB') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_table_line">Tabla de Línea</label>
                            <input type="text" id="mysql_table_line" name="mysql_table_line" class="form-control" value="{{ env('MYSQL_TABLE_LINE') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_table_sensor">Tabla de Sensor</label>
                            <input type="text" id="mysql_table_sensor" name="mysql_table_sensor" class="form-control" value="{{ env('MYSQL_TABLE_SENSOR') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_user">Usuario MySQL</label>
                            <input type="text" id="mysql_user" name="mysql_user" class="form-control" value="{{ env('MYSQL_USER') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_password">Contraseña MySQL</label>
                            <input type="password" id="mysql_password" name="mysql_password" class="form-control" value="{{ env('MYSQL_PASSWORD') }}" required>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" class="btn btn-success">Guardar Configuración</button>
                            <button type="button" id="check-connection" class="btn btn-info">Comprobar Conexión</button>
                            <button type="button" id="verify-sync-db" class="btn btn-warning">Verificar e Integrar</button>
                        </div>
                    </form>

                    {{-- Estado de la conexión --}}
                    <div id="connection-status" class="mt-3 text-center"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Verificar conexión a la base de datos
        document.getElementById('check-connection').addEventListener('click', function () {
            const formData = new FormData(document.getElementById('upload-stats-form'));
            const data = Object.fromEntries(formData.entries());

            fetch('/api/check-db-connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer {{ env('TOKEN_SYSTEM') }}`
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('connection-status');
                if (data.status === 'success') {
                    statusDiv.textContent = data.message;
                    statusDiv.style.color = 'green';
                } else {
                    statusDiv.textContent = data.message;
                    statusDiv.style.color = 'red';
                }
            })
            .catch(error => {
                const statusDiv = document.getElementById('connection-status');
                statusDiv.textContent = 'Error al comprobar la conexión: ' + error.message;
                statusDiv.style.color = 'red';
            });
        });

        // Guardar configuración
        document.getElementById('upload-stats-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            fetch('/api/update-env', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer {{ env('TOKEN_SYSTEM') }}`
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Configuración actualizada con éxito');
                } else {
                    alert('Error al actualizar configuración: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al actualizar configuración: ' + error.message);
            });
        });

        // Verificar e integrar base de datos
        document.getElementById('verify-sync-db').addEventListener('click', function () {
            fetch('/api/verify-and-sync-database', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer {{ env('TOKEN_SYSTEM') }}`
                }
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('connection-status');
                if (data.status === 'success') {
                    statusDiv.textContent = data.message;
                    statusDiv.style.color = 'green';
                } else {
                    statusDiv.textContent = data.message;
                    statusDiv.style.color = 'red';
                }
            })
            .catch(error => {
                const statusDiv = document.getElementById('connection-status');
                statusDiv.textContent = 'Error al verificar e integrar la base de datos: ' + error.message;
                statusDiv.style.color = 'red';
            });
        });
    </script>
@endsection

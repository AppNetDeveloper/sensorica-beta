@extends('layouts.admin')

@section('title', __('Server Status'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Server Action') }}</li>
    </ul>
@endsection
@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Card principal con acciones del servidor --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">{{ __('Server Action') }}</h4>
                </div>
                <div class="card-body">
                    <button id="reboot" class="btn btn-danger mb-2">Reiniciar Servidor</button>
                    <button id="poweroff" class="btn btn-warning mb-2">Apagar Servidor</button>
                    <button id="restart-supervisor" class="btn btn-info mb-2">Reiniciar Supervisor</button>
                    <button id="start-supervisor" class="btn btn-success mb-2">Iniciar Supervisor</button>
                    <button id="stop-supervisor" class="btn btn-secondary mb-2">Detener Supervisor</button>
                    <button id="restart-485" class="btn btn-primary mb-2">Reiniciar 485</button>
                    <button id="update-app" class="btn btn-dark mb-2">Actualizar Aplicación</button>
                </div>
            </div>

            {{-- Card con estadísticas del servidor --}}
            <div class="card border-0 shadow mt-4">
                <div class="card-header border-0">
                    <h4 class="card-title">Estadísticas del Servidor</h4>
                </div>
                <div class="card-body">
                    <p id="cpu-usage">CPU: Cargando...</p>
                    <p id="ram-usage">RAM: Cargando...</p>
                </div>
            </div>

            {{-- Card con estado del Supervisor --}}
            <div class="card border-0 shadow mt-4">
                <div class="card-header border-0">
                    <h4 class="card-title">Estado de Supervisor</h4>
                </div>
                <div class="card-body">
                    <ul id="supervisor-status">
                        <li>Cargando...</li>
                    </ul>
                </div>
            </div>

            {{-- Card con estado del servicio Swift 485 --}}
            <div class="card border-0 shadow mt-4">
                <div class="card-header border-0">
                    <h4 class="card-title">Estado del Servicio Swift 485</h4>
                </div>
                <div class="card-body">
                    <p id="service-485-status">Cargando...</p>
                    <button id="install-485-service" class="btn btn-primary" style="display: none;">Instalar y arrancar servicio</button>
                </div>
            </div>

            {{-- Card con direcciones IP del servidor --}}
            <div class="card border-0 shadow mt-4">
                <div class="card-header border-0">
                    <h4 class="card-title">Direcciones IP del Servidor</h4>
                </div>
                <div class="card-body">
                    <ul id="server-ips">
                        <li>Cargando...</li>
                    </ul>
                    <button id="get-ips" class="btn btn-info mt-2">Actualizar IPs</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const token = '{{ env('TOKEN_SYSTEM') }}';

        // Función genérica para llamadas a la API
        const apiCall = async (url, method = 'POST') => {
            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error desconocido');
                }
                return await response.json();
            } catch (error) {
                //alert(`Error: ${error.message}`);
                //lo ponemos en console log
                console.log(`Error: ${error.message}`);
            }
        };

        // Obtener estadísticas del servidor
        const getServerStats = async () => {
            try {
                const data = await apiCall('/api/server-stats', 'GET');
                document.getElementById('cpu-usage').textContent = `CPU: ${data.cpu_usage}`;
                document.getElementById('ram-usage').textContent = `RAM: ${data.ram_usage}`;
            } catch {
                document.getElementById('cpu-usage').textContent = 'Error al cargar datos';
                document.getElementById('ram-usage').textContent = 'Error al cargar datos';
            }
        };

        // Actualizar estado del Supervisor
        const updateSupervisorStatus = async () => {
            try {
                const data = await apiCall('/api/supervisor-status', 'GET');
                const statusList = document.getElementById('supervisor-status');
                statusList.innerHTML = '';
                data.supervisor_status.forEach(status => {
                    const listItem = document.createElement('li');
                    listItem.textContent = status;
                    statusList.appendChild(listItem);
                });
            } catch {
                document.getElementById('supervisor-status').innerHTML = '<li>Error al cargar el estado</li>';
            }
        };

        // Verificar estado del servicio Swift 485
        const check485Service = async () => {
            try {
                const data = await apiCall('/api/check-485-service', 'GET');
                const statusElement = document.getElementById('service-485-status');
                const installButton = document.getElementById('install-485-service');
                const status = data.service_status.toLowerCase();

                if (status.includes('not_installed')) {
                    statusElement.textContent = 'Swift 485: No instalado';
                    installButton.style.display = 'inline-block';
                } else if (status.includes('inactive')) {
                    statusElement.textContent = 'Swift 485: Inactivo';
                    installButton.style.display = 'none';
                } else if (status.includes('active')) {
                    statusElement.textContent = 'Swift 485: Activo';
                    installButton.style.display = 'none';
                } else {
                    statusElement.textContent = `Swift 485: ${data.service_status}`;
                    installButton.style.display = 'none';
                }
            } catch {
                document.getElementById('service-485-status').textContent = 'Error al cargar estado';
            }
        };

        // Obtener direcciones IP
        const getServerIps = async () => {
            try {
                const data = await apiCall('/api/server-ips', 'GET');
                const ipsList = document.getElementById('server-ips');
                ipsList.innerHTML = '';
                data.ips.forEach(ip => {
                    const listItem = document.createElement('li');
                    listItem.textContent = ip;
                    ipsList.appendChild(listItem);
                });
            } catch {
                document.getElementById('server-ips').innerHTML = '<li>Error al cargar direcciones IP</li>';
            }
        };

            // Asociar eventos a los botones
        document.getElementById('reboot').addEventListener('click', async () => {
            await apiCall('/api/reboot');
            alert('El sistema se está reiniciando.');
        });

        document.getElementById('poweroff').addEventListener('click', async () => {
            await apiCall('/api/poweroff');
            alert('El sistema se está apagando.');
        });

        document.getElementById('restart-supervisor').addEventListener('click', async () => {
            await apiCall('/api/restart-supervisor');
            alert('Supervisor reiniciado con éxito.');
        });

        document.getElementById('start-supervisor').addEventListener('click', async () => {
            await apiCall('/api/start-supervisor');
            alert('Supervisor iniciado con éxito.');
        });

        document.getElementById('stop-supervisor').addEventListener('click', async () => {
            await apiCall('/api/stop-supervisor');
            alert('Supervisor detenido con éxito.');
        });

        document.getElementById('restart-485').addEventListener('click', async () => {
            await apiCall('/api/restart-485-Swift');
            alert('El servicio 485 se ha reiniciado con éxito.');
        });

        document.getElementById('update-app').addEventListener('click', async () => {
            await apiCall('/api/app-update');
            alert('Aplicación actualizada con éxito.');
        });

        // Eventos para botones
        document.getElementById('install-485-service').addEventListener('click', () => {
            apiCall('/api/install-485-service').then(check485Service);
        });

        //document.getElementById('update-app').addEventListener('click', () => {
         //   apiCall('/api/app-update');
        //});

        document.getElementById('get-ips').addEventListener('click', getServerIps);

        // Inicializar funciones al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            getServerStats();
            updateSupervisorStatus();
            check485Service();
            getServerIps();
        });
        

        // Actualizar periódicamente
        setInterval(getServerStats, 10000);
        setInterval(updateSupervisorStatus, 15000);
    </script>
    @endpush
@endsection

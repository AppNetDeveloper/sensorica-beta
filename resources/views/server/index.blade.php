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
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-gradient-primary text-white border-0 py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-server mr-2"></i>{{ __('Server Action') }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <button id="reboot" class="btn btn-danger btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-sync-alt mr-2"></i> {{ __('Restart Server') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="poweroff" class="btn btn-warning btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-power-off mr-2"></i> {{ __('Power Off') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="restart-supervisor" class="btn btn-info btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-redo mr-2"></i> {{ __('Restart Supervisor') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="restart-mysql" class="btn btn-primary btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-database mr-2"></i> {{ __('Reiniciar MySQL') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="start-supervisor" class="btn btn-success btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-play mr-2"></i> {{ __('Start Supervisor') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="stop-supervisor" class="btn btn-secondary btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-stop mr-2"></i> {{ __('Stop Supervisor') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="restart-485" class="btn btn-primary btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-sync mr-2"></i> {{ __('Reiniciar 485') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="update-app" class="btn btn-dark btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-cloud-download-alt mr-2"></i> {{ __('Actualizar Software') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="verne-app" class="btn btn-danger btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-download mr-2"></i> {{ __('Instalar Verne') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="phpmyadmin" class="btn btn-warning btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-database mr-2"></i> {{ __('Acceder a PHPMyAdmin') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="fix-logs" class="btn btn-success btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-file-alt mr-2"></i> {{ __('Fix Logs') }}
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button id="logs" class="btn btn-info btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-file-alt mr-2"></i> {{ __('Ver Log') }}
                            </button>
                        </div>
                        @if(env('RFID_MONITOR_URL'))
                            <div class="col-md-6 col-lg-4">
                                <a href="{{ env('RFID_MONITOR_URL') }}" target="_blank" class="btn btn-primary btn-lg w-100 h-100 d-flex align-items-center justify-content-center py-3">
                                    <i class="fas fa-rss mr-2"></i> {{ __('Monitor Antena RFID') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tarjetas de información en fila --}}
            <div class="row mt-4">
                {{-- Card con estadísticas del servidor --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-lg h-100">
                        <div class="card-header bg-gradient-success text-white border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line mr-2"></i>{{__('Estadísticas del Servidor')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="font-weight-bold">{{ __('CPU') }}</span>
                                    <span id="cpu-usage" class="badge badge-primary">{{__('Cargando...')}}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div id="cpu-progress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="font-weight-bold">{{ __('RAM') }}</span>
                                    <span id="ram-usage" class="badge badge-info">{{__('Cargando...')}}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div id="ram-progress" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card con estado del Supervisor --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-lg h-100">
                        <div class="card-header bg-gradient-info text-white border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tasks mr-2"></i>{{__('Estado del Supervisor')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="supervisor-status" class="small">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="spinner-border spinner-border-sm mr-2" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <span>{{ __('Estado de servidor') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card con estado del servicio Swift 485 --}}
                <div class="col-lg-4 col-md-12">
                    <div class="card border-0 shadow-lg h-100">
                        <div class="card-header bg-gradient-warning text-white border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-plug mr-2"></i>{{__('Estado del servicio Swift 485')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span id="service-485-status" class="font-weight-bold">{{__('Cargando...')}}</span>
                                <div id="service-485-indicator" class="spinner-border spinner-border-sm" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                            <button id="install-485-service" class="btn btn-primary btn-sm w-100" style="display: none;">
                                <i class="fas fa-download mr-1"></i> {{__('Instalar y arrancar servicio')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card con direcciones IP del servidor --}}
            <div class="card border-0 shadow-lg mt-4">
                <div class="card-header bg-gradient-secondary text-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-network-wired mr-2"></i>{{__('Direcciones IP del Servidor')}}
                        </h5>
                        <button id="get-ips" class="btn btn-light btn-sm">
                            <i class="fas fa-sync-alt mr-1"></i> {{__('Actualizar IPs')}}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="server-ips" class="row g-2">
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm mr-2" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <span>{{__('Cargando...')}}</span>
                            </div>
                        </div>
                    </div>
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

                // Procesar CPU
                let cpuValue = 0;
                let cpuText = data.cpu_usage;

                if (cpuText && cpuText !== 'Cargando...') {
                    // Extraer valor numérico del texto
                    const cpuMatch = cpuText.match(/(\d+\.?\d*)/);
                    if (cpuMatch) {
                        cpuValue = parseFloat(cpuMatch[1]);
                        // Redondear a un decimal
                        cpuText = cpuValue.toFixed(1) + '%';
                    }
                }

                // Procesar RAM
                let ramValue = 0;
                let ramText = data.ram_usage;

                if (ramText && ramText !== 'Cargando...') {
                    // Si viene en formato "1024MB/4096MB", calcular porcentaje
                    if (ramText.includes('/')) {
                        const ramParts = ramText.split('/');
                        if (ramParts.length === 2) {
                            const used = parseFloat(ramParts[0].replace('MB', ''));
                            const total = parseFloat(ramParts[1].replace('MB', ''));
                            if (!isNaN(used) && !isNaN(total) && total > 0) {
                                ramValue = (used / total) * 100;
                                ramText = ramValue.toFixed(1) + '% (' + ramText + ')';
                            }
                        }
                    } else {
                        // Si ya viene como porcentaje
                        const ramMatch = ramText.match(/(\d+\.?\d*)/);
                        if (ramMatch) {
                            ramValue = parseFloat(ramMatch[1]);
                            ramText = ramValue.toFixed(1) + '%';
                        }
                    }
                }

                // Actualizar texto en los badges
                document.getElementById('cpu-usage').textContent = cpuText;
                document.getElementById('ram-usage').textContent = ramText;

                // Actualizar barras de progreso con animación
                if (cpuValue > 0) {
                    const cpuBar = document.getElementById('cpu-progress');
                    const currentWidth = parseFloat(cpuBar.style.width) || 0;

                    // Animar transición suave
                    animateProgressBar(cpuBar, currentWidth, cpuValue, 'cpu');
                }

                if (ramValue > 0) {
                    const ramBar = document.getElementById('ram-progress');
                    const currentWidth = parseFloat(ramBar.style.width) || 0;

                    // Animar transición suave
                    animateProgressBar(ramBar, currentWidth, ramValue, 'ram');
                }

            } catch (error) {
                console.error('Error al obtener estadísticas:', error);
                document.getElementById('cpu-usage').textContent = 'Error';
                document.getElementById('ram-usage').textContent = 'Error';
                document.getElementById('cpu-progress').style.width = '0%';
                document.getElementById('ram-progress').style.width = '0%';
            }
        };

        // Función para animar barras de progreso
        const animateProgressBar = (bar, startWidth, endWidth, type) => {
            const duration = 1000; // 1 segundo
            const steps = 20;
            const stepDuration = duration / steps;
            const increment = (endWidth - startWidth) / steps;

            let currentStep = 0;
            const interval = setInterval(() => {
                currentStep++;
                const currentWidth = startWidth + (increment * currentStep);

                bar.style.width = Math.min(currentWidth, endWidth) + '%';

                // Determinar color según el nivel de uso
                bar.className = 'progress-bar';
                if (currentWidth > 80) {
                    bar.classList.add('bg-danger');
                } else if (currentWidth > 60) {
                    bar.classList.add('bg-warning');
                } else if (type === 'cpu') {
                    bar.classList.add('bg-success');
                } else {
                    bar.classList.add('bg-info');
                }

                if (currentStep >= steps) {
                    clearInterval(interval);
                }
            }, stepDuration);
        };

        // Actualizar estado del Supervisor
        const updateSupervisorStatus = async () => {
            try {
                const data = await apiCall('/api/supervisor-status', 'GET');
                const statusList = document.getElementById('supervisor-status');
                statusList.innerHTML = '';
                data.supervisor_status.forEach(status => {
                    const statusItem = document.createElement('div');
                    statusItem.className = 'd-flex align-items-center mb-2 p-2 rounded';

                    // Determinar el estado y aplicar estilos correspondientes
                    if (status.toLowerCase().includes('running') || status.toLowerCase().includes('active')) {
                        statusItem.classList.add('bg-success', 'text-white');
                    } else if (status.toLowerCase().includes('stopped') || status.toLowerCase().includes('failed')) {
                        statusItem.classList.add('bg-danger', 'text-white');
                    } else {
                        statusItem.classList.add('bg-light');
                    }

                    // Crear indicador visual
                    const indicator = document.createElement('i');
                    indicator.className = 'fas fa-circle mr-2';
                    if (status.toLowerCase().includes('running') || status.toLowerCase().includes('active')) {
                        indicator.style.color = '#28a745';
                    } else if (status.toLowerCase().includes('stopped') || status.toLowerCase().includes('failed')) {
                        indicator.style.color = '#dc3545';
                    } else {
                        indicator.style.color = '#ffc107';
                    }

                    const statusText = document.createElement('span');
                    statusText.textContent = status;

                    statusItem.appendChild(indicator);
                    statusItem.appendChild(statusText);
                    statusList.appendChild(statusItem);
                });
            } catch {
                document.getElementById('supervisor-status').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Error al cargar el estado</div>';
            }
        };

        // Verificar estado del servicio Swift 485
        const check485Service = async () => {
            try {
                const data = await apiCall('/api/check-485-service', 'GET');
                const statusElement = document.getElementById('service-485-status');
                const indicatorElement = document.getElementById('service-485-indicator');
                const installButton = document.getElementById('install-485-service');
                const status = data.service_status.toLowerCase();

                // Ocultar spinner de carga
                indicatorElement.style.display = 'none';

                if (status.includes('not_installed')) {
                    statusElement.innerHTML = '<i class="fas fa-times-circle text-danger mr-1"></i> Swift 485: No instalado';
                    statusElement.className = 'font-weight-bold text-danger';
                    installButton.style.display = 'inline-block';
                } else if (status.includes('inactive')) {
                    statusElement.innerHTML = '<i class="fas fa-exclamation-triangle text-warning mr-1"></i> Swift 485: Inactivo';
                    statusElement.className = 'font-weight-bold text-warning';
                    installButton.style.display = 'none';
                } else if (status.includes('active')) {
                    statusElement.innerHTML = '<i class="fas fa-check-circle text-success mr-1"></i> Swift 485: Activo';
                    statusElement.className = 'font-weight-bold text-success';
                    installButton.style.display = 'none';
                } else {
                    statusElement.innerHTML = `<i class="fas fa-question-circle text-info mr-1"></i> Swift 485: ${data.service_status}`;
                    statusElement.className = 'font-weight-bold text-info';
                    installButton.style.display = 'none';
                }
            } catch {
                document.getElementById('service-485-status').innerHTML = '<i class="fas fa-times-circle text-danger mr-1"></i> Error al cargar estado';
                document.getElementById('service-485-status').className = 'font-weight-bold text-danger';
                document.getElementById('service-485-indicator').style.display = 'none';
            }
        };

        // Obtener direcciones IP
        const getServerIps = async () => {
            try {
                const data = await apiCall('/api/server-ips', 'GET');
                const ipsList = document.getElementById('server-ips');
                ipsList.innerHTML = '';
                data.ips.forEach(ip => {
                    const ipCard = document.createElement('div');
                    ipCard.className = 'col-md-6 col-lg-4';

                    const ipContent = document.createElement('div');
                    ipContent.className = 'alert alert-info mb-2 d-flex align-items-center';

                    const ipIcon = document.createElement('i');
                    ipIcon.className = 'fas fa-network-wired mr-2';

                    const ipText = document.createElement('span');
                    ipText.textContent = ip;
                    ipText.className = 'font-monospace font-weight-bold';

                    // Copiar al portapapeles al hacer clic
                    ipContent.style.cursor = 'pointer';
                    ipContent.title = 'Copiar IP';
                    ipContent.addEventListener('click', () => {
                        navigator.clipboard.writeText(ip).then(() => {
                            // Mostrar notificación temporal
                            const originalText = ipText.textContent;
                            ipText.textContent = '¡Copiado!';
                            setTimeout(() => {
                                ipText.textContent = originalText;
                            }, 1000);
                        });
                    });

                    ipContent.appendChild(ipIcon);
                    ipContent.appendChild(ipText);
                    ipCard.appendChild(ipContent);
                    ipsList.appendChild(ipCard);
                });
            } catch {
                document.getElementById('server-ips').innerHTML = '<div class="col-12"><div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Error al cargar direcciones IP</div></div>';
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

        document.getElementById('fix-logs').addEventListener('click', async () => {
            try {
                await apiCall('/api/fix-logs');
                alert('Permisos de logs corregidos con éxito.');
            } catch (error) {
                alert('Error al corregir permisos de logs: ' + error);
            }
        });

        document.getElementById('stop-supervisor').addEventListener('click', async () => {
            await apiCall('/api/stop-supervisor');
            alert('Supervisor detenido con éxito.');
        });

        document.getElementById('restart-485').addEventListener('click', async () => {
            await apiCall('/api/restart-485-Swift');
            alert('El servicio 485 se ha reiniciado con éxito.');
        });
        
        document.getElementById('restart-mysql').addEventListener('click', async () => {
            if (confirm('¿Está seguro de que desea reiniciar MySQL? Esto puede interrumpir temporalmente la conexión a la base de datos.')) {
                await apiCall('/api/restart-mysql');
                alert('MySQL se está reiniciando. Por favor, espere unos segundos.');
            }
        });

        document.getElementById('update-app').addEventListener('click', async () => {
            await apiCall('/api/app-update');
            alert('Aplicación actualizada con éxito.');
        });
        document.getElementById('verne-app').addEventListener('click', async () => {
            await apiCall('/api/verne-update');
            alert('Aplicación actualizada con éxito.');
        });

        // Eventos para botones
        document.getElementById('install-485-service').addEventListener('click', () => {
            apiCall('/api/install-485-service').then(check485Service);
        });

        document.getElementById('logs').addEventListener('click', () => {
            window.location.href = '/log-viewer?file=laravel.log';
        });

        document.getElementById('phpmyadmin').addEventListener('click', () => {
            window.location.href = '/phpmyadmin';
        });
        document.getElementById('get-ips').addEventListener('click', getServerIps);

        // Inicializar funciones al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            getServerStats();
            updateSupervisorStatus();
            check485Service();
            getServerIps();
        });
        

        // Inicializar funciones al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            getServerStats();
            updateSupervisorStatus();
            check485Service();
            getServerIps();
        });

        // Actualizar periódicamente con tiempos más cortos para mayor "real-time"
        setInterval(getServerStats, 5000);  // Cada 5 segundos para CPU/RAM
        setInterval(updateSupervisorStatus, 10000);  // Cada 10 segundos para Supervisor
        setInterval(check485Service, 30000);  // Cada 30 segundos para servicio 485
        setInterval(getServerIps, 60000);  // Cada minuto para IPs
    </script>
    @endpush
@endsection

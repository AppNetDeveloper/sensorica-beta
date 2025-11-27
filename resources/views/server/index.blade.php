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

            {{-- Card de Copias de Seguridad de Base de Datos --}}
            <div class="card border-0 shadow-lg mt-4">
                <div class="card-header bg-gradient-dark text-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-database mr-2"></i>{{__('Database Backups')}}
                        </h5>
                        <div>
                            <button id="refresh-backups" class="btn btn-light btn-sm mr-2">
                                <i class="fas fa-sync-alt mr-1"></i> {{__('Refresh')}}
                            </button>
                            <button id="upload-backup-btn" class="btn btn-info btn-sm mr-2" data-toggle="modal" data-target="#uploadBackupModal">
                                <i class="fas fa-upload mr-1"></i> {{__('Upload Backup')}}
                            </button>
                            <button id="create-backup" class="btn btn-success btn-sm">
                                <i class="fas fa-plus mr-1"></i> {{__('Create Backup')}}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="backup-status" class="alert alert-info d-none">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        <span id="backup-status-text">{{__('Creating backup...')}}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="backups-table">
                            <thead class="thead-light">
                                <tr>
                                    <th><i class="fas fa-file-alt mr-1"></i> {{__('Filename')}}</th>
                                    <th><i class="fas fa-hdd mr-1"></i> {{__('Size')}}</th>
                                    <th><i class="fas fa-calendar mr-1"></i> {{__('Created at')}}</th>
                                    <th class="text-center"><i class="fas fa-cogs mr-1"></i> {{__('Actions')}}</th>
                                </tr>
                            </thead>
                            <tbody id="backups-list">
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <div class="spinner-border spinner-border-sm mr-2" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        {{__('Loading backups...')}}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Modal para subir backup --}}
            <div class="modal fade" id="uploadBackupModal" tabindex="-1" role="dialog" aria-labelledby="uploadBackupModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="uploadBackupModalLabel">
                                <i class="fas fa-upload mr-2"></i>{{__('Upload Backup')}}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="upload-backup-form" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="backup_file">{{__('Select SQL file')}}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="backup_file" name="backup_file" accept=".sql">
                                        <label class="custom-file-label" for="backup_file" id="backup_file_label">{{__('Choose file...')}}</label>
                                    </div>
                                    <small class="form-text text-muted">{{__('Only .sql files are allowed. Max size: 500MB')}}</small>
                                </div>
                                <div id="upload-progress" class="progress d-none mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-info" id="submit-upload">
                                <i class="fas fa-upload mr-1"></i> {{__('Upload')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal de confirmación para restaurar - Paso 1 --}}
            <div class="modal fade" id="restoreBackupModal1" tabindex="-1" role="dialog" aria-labelledby="restoreBackupModal1Label" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="restoreBackupModal1Label">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{__('Restore Backup')}} - {{__('Step')}} 1/2
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>{{__('Attention!')}}</strong> {{__('You are about to restore a backup.')}}
                            </div>
                            <p>{{__('Are you sure you want to restore the backup')}} <strong id="restore-filename-1"></strong>?</p>
                            <p class="text-muted">{{__('This will open a second confirmation window.')}}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-warning" id="confirm-restore-step1-btn">
                                <i class="fas fa-arrow-right mr-1"></i> {{__('Continue')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal de confirmación para restaurar - Paso 2 --}}
            <div class="modal fade" id="restoreBackupModal2" tabindex="-1" role="dialog" aria-labelledby="restoreBackupModal2Label" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="restoreBackupModal2Label">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{__('Final Confirmation')}} - {{__('Step')}} 2/2
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <strong>{{__('Warning!')}}</strong> {{__('This action will overwrite all current data in the database. This cannot be undone.')}}
                            </div>
                            <p>{{__('Restoring backup')}}: <strong id="restore-filename-2"></strong></p>
                            <div class="form-group">
                                <label for="confirm-restore">{{__('Type RESTORE to confirm')}}:</label>
                                <input type="text" class="form-control" id="confirm-restore" placeholder="RESTORE" autocomplete="off">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-danger" id="confirm-restore-btn" disabled>
                                <i class="fas fa-undo mr-1"></i> {{__('Restore')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal de confirmación para eliminar - Paso 1 --}}
            <div class="modal fade" id="deleteBackupModal1" tabindex="-1" role="dialog" aria-labelledby="deleteBackupModal1Label" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="deleteBackupModal1Label">
                                <i class="fas fa-trash mr-2"></i>{{__('Delete Backup')}} - {{__('Step')}} 1/2
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>{{__('Attention!')}}</strong> {{__('You are about to delete a backup.')}}
                            </div>
                            <p>{{__('Are you sure you want to delete the backup')}} <strong id="delete-filename-1"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-warning" id="confirm-delete-step1-btn">
                                <i class="fas fa-arrow-right mr-1"></i> {{__('Continue')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal de confirmación para eliminar - Paso 2 --}}
            <div class="modal fade" id="deleteBackupModal2" tabindex="-1" role="dialog" aria-labelledby="deleteBackupModal2Label" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteBackupModal2Label">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{__('Final Confirmation')}} - {{__('Step')}} 2/2
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <strong>{{__('Warning!')}}</strong> {{__('This backup will be permanently deleted.')}}
                            </div>
                            <p>{{__('Deleting backup')}}: <strong id="delete-filename-2"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                                <i class="fas fa-trash mr-1"></i> {{__('Delete Permanently')}}
                            </button>
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

        // ==================== BACKUP FUNCTIONS ====================

        // Listar backups
        const loadBackups = async () => {
            try {
                const response = await fetch('/server/backups', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();
                const backupsList = document.getElementById('backups-list');

                if (data.success && data.backups.length > 0) {
                    backupsList.innerHTML = '';
                    data.backups.forEach(backup => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <i class="fas fa-file-archive text-primary mr-2"></i>
                                <span class="font-weight-bold">${backup.filename}</span>
                            </td>
                            <td>
                                <span class="badge badge-info">${backup.size}</span>
                            </td>
                            <td>${backup.created_at}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning restore-backup mr-1" data-filename="${backup.filename}" title="{{ __('Restore') }}">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <a href="/server/backup/download/${backup.filename}" class="btn btn-sm btn-primary mr-1" title="{{ __('Download') }}">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button class="btn btn-sm btn-danger delete-backup" data-filename="${backup.filename}" title="{{ __('Delete') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        backupsList.appendChild(row);
                    });

                    // Agregar event listeners para eliminar
                    document.querySelectorAll('.delete-backup').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            const filename = e.currentTarget.dataset.filename;
                            openDeleteModal(filename);
                        });
                    });

                    // Agregar event listeners para restaurar
                    document.querySelectorAll('.restore-backup').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            const filename = e.currentTarget.dataset.filename;
                            openRestoreModal(filename);
                        });
                    });
                } else {
                    backupsList.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                {{ __('No backups available') }}
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading backups:', error);
                document.getElementById('backups-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            {{ __('Error loading backups') }}
                        </td>
                    </tr>
                `;
            }
        };

        // Crear backup
        const createBackup = async () => {
            const statusDiv = document.getElementById('backup-status');
            const statusText = document.getElementById('backup-status-text');
            const createBtn = document.getElementById('create-backup');

            try {
                // Mostrar estado
                statusDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
                statusDiv.classList.add('alert-info');
                statusText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>{{ __("Creating backup...") }}';
                createBtn.disabled = true;

                const response = await fetch('/server/backup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();

                if (data.success) {
                    statusDiv.classList.remove('alert-info');
                    statusDiv.classList.add('alert-success');
                    statusText.innerHTML = `<i class="fas fa-check-circle mr-2"></i>{{ __("Backup created successfully") }}: ${data.backup.filename} (${data.backup.size})`;
                    loadBackups();
                } else {
                    statusDiv.classList.remove('alert-info');
                    statusDiv.classList.add('alert-danger');
                    statusText.innerHTML = `<i class="fas fa-times-circle mr-2"></i>${data.message}`;
                }

                // Ocultar mensaje después de 5 segundos
                setTimeout(() => {
                    statusDiv.classList.add('d-none');
                }, 5000);

            } catch (error) {
                console.error('Error creating backup:', error);
                statusDiv.classList.remove('alert-info');
                statusDiv.classList.add('alert-danger');
                statusText.innerHTML = `<i class="fas fa-times-circle mr-2"></i>{{ __("Error creating backup") }}`;
            } finally {
                createBtn.disabled = false;
            }
        };

        // Eliminar backup
        const deleteBackup = async (filename) => {
            try {
                const response = await fetch(`/server/backup/${filename}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();

                if (data.success) {
                    loadBackups();
                    alert('{{ __("Backup deleted successfully") }}');
                } else {
                    alert(data.message || '{{ __("Error deleting backup") }}');
                }
            } catch (error) {
                console.error('Error deleting backup:', error);
                alert('{{ __("Error deleting backup") }}');
            }
        };

        // ==================== UPLOAD BACKUP ====================

        // Mostrar nombre del archivo seleccionado
        document.getElementById('backup_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '{{ __("Choose file...") }}';
            document.getElementById('backup_file_label').textContent = fileName;
        });

        // Subir backup
        document.getElementById('submit-upload').addEventListener('click', async () => {
            const fileInput = document.getElementById('backup_file');
            const file = fileInput.files[0];

            if (!file) {
                alert('{{ __("Please select a file") }}');
                return;
            }

            if (!file.name.endsWith('.sql')) {
                alert('{{ __("Only .sql files are allowed") }}');
                return;
            }

            const formData = new FormData();
            formData.append('backup_file', file);

            const progressDiv = document.getElementById('upload-progress');
            const progressBar = progressDiv.querySelector('.progress-bar');
            const submitBtn = document.getElementById('submit-upload');

            try {
                progressDiv.classList.remove('d-none');
                submitBtn.disabled = true;

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/server/backup/upload', true);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressBar.style.width = percent + '%';
                    }
                };

                xhr.onload = () => {
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && data.success) {
                        alert('{{ __("Backup uploaded successfully") }}');
                        $('#uploadBackupModal').modal('hide');
                        loadBackups();
                        // Reset form
                        fileInput.value = '';
                        document.getElementById('backup_file_label').textContent = '{{ __("Choose file...") }}';
                    } else {
                        alert(data.message || '{{ __("Error uploading backup") }}');
                    }
                    progressDiv.classList.add('d-none');
                    progressBar.style.width = '0%';
                    submitBtn.disabled = false;
                };

                xhr.onerror = () => {
                    alert('{{ __("Error uploading backup") }}');
                    progressDiv.classList.add('d-none');
                    progressBar.style.width = '0%';
                    submitBtn.disabled = false;
                };

                xhr.send(formData);

            } catch (error) {
                console.error('Error uploading backup:', error);
                alert('{{ __("Error uploading backup") }}');
                progressDiv.classList.add('d-none');
                submitBtn.disabled = false;
            }
        });

        // ==================== RESTORE BACKUP (2 STEPS) ====================

        let currentRestoreFilename = null;

        // Abrir modal de restauración - Paso 1
        const openRestoreModal = (filename) => {
            currentRestoreFilename = filename;
            document.getElementById('restore-filename-1').textContent = filename;
            $('#restoreBackupModal1').modal('show');
        };

        // Paso 1 -> Paso 2
        document.getElementById('confirm-restore-step1-btn').addEventListener('click', () => {
            $('#restoreBackupModal1').modal('hide');
            document.getElementById('restore-filename-2').textContent = currentRestoreFilename;
            document.getElementById('confirm-restore').value = '';
            document.getElementById('confirm-restore-btn').disabled = true;
            $('#restoreBackupModal2').modal('show');
        });

        // Validar confirmación de texto
        document.getElementById('confirm-restore').addEventListener('input', (e) => {
            const confirmBtn = document.getElementById('confirm-restore-btn');
            confirmBtn.disabled = e.target.value !== 'RESTORE';
        });

        // Ejecutar restauración - Paso 2 final
        document.getElementById('confirm-restore-btn').addEventListener('click', async () => {
            if (!currentRestoreFilename) return;

            const statusDiv = document.getElementById('backup-status');
            const statusText = document.getElementById('backup-status-text');
            const confirmBtn = document.getElementById('confirm-restore-btn');

            try {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> {{ __("Restoring...") }}';

                const response = await fetch(`/server/backup/restore/${currentRestoreFilename}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();

                $('#restoreBackupModal2').modal('hide');

                if (data.success) {
                    statusDiv.classList.remove('d-none', 'alert-info', 'alert-danger');
                    statusDiv.classList.add('alert-success');
                    statusText.innerHTML = `<i class="fas fa-check-circle mr-2"></i>{{ __("Backup restored successfully") }}`;
                    setTimeout(() => {
                        statusDiv.classList.add('d-none');
                    }, 5000);
                } else {
                    statusDiv.classList.remove('d-none', 'alert-info', 'alert-success');
                    statusDiv.classList.add('alert-danger');
                    let errorMsg = data.message || '{{ __("Error restoring backup") }}';
                    if (data.error) {
                        errorMsg += `<br><small class="text-white-50"><strong>{{ __("Details") }}:</strong> ${data.error}</small>`;
                    }
                    statusText.innerHTML = `<i class="fas fa-times-circle mr-2"></i>${errorMsg}`;
                    // No ocultar automáticamente si hay error, para que el usuario pueda leerlo
                }

            } catch (error) {
                console.error('Error restoring backup:', error);
                $('#restoreBackupModal2').modal('hide');
                statusDiv.classList.remove('d-none', 'alert-info', 'alert-success');
                statusDiv.classList.add('alert-danger');
                statusText.innerHTML = `<i class="fas fa-times-circle mr-2"></i>{{ __("Error restoring backup") }}<br><small class="text-white-50">${error.message}</small>`;
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-undo mr-1"></i> {{ __("Restore") }}';
                currentRestoreFilename = null;
            }
        });

        // ==================== DELETE BACKUP (2 STEPS) ====================

        let currentDeleteFilename = null;

        // Abrir modal de eliminación - Paso 1
        const openDeleteModal = (filename) => {
            currentDeleteFilename = filename;
            document.getElementById('delete-filename-1').textContent = filename;
            $('#deleteBackupModal1').modal('show');
        };

        // Paso 1 -> Paso 2
        document.getElementById('confirm-delete-step1-btn').addEventListener('click', () => {
            $('#deleteBackupModal1').modal('hide');
            document.getElementById('delete-filename-2').textContent = currentDeleteFilename;
            $('#deleteBackupModal2').modal('show');
        });

        // Ejecutar eliminación - Paso 2 final
        document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
            if (!currentDeleteFilename) return;

            const confirmBtn = document.getElementById('confirm-delete-btn');

            try {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> {{ __("Deleting...") }}';

                const response = await fetch(`/server/backup/${currentDeleteFilename}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();

                $('#deleteBackupModal2').modal('hide');

                if (data.success) {
                    loadBackups();
                    alert('{{ __("Backup deleted successfully") }}');
                } else {
                    alert(data.message || '{{ __("Error deleting backup") }}');
                }
            } catch (error) {
                console.error('Error deleting backup:', error);
                $('#deleteBackupModal2').modal('hide');
                alert('{{ __("Error deleting backup") }}');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-trash mr-1"></i> {{ __("Delete Permanently") }}';
                currentDeleteFilename = null;
            }
        });

        // Event listeners para backups
        document.getElementById('create-backup').addEventListener('click', createBackup);
        document.getElementById('refresh-backups').addEventListener('click', loadBackups);

        // Cargar backups al iniciar
        document.addEventListener('DOMContentLoaded', loadBackups);
    </script>
    @endpush
@endsection

@extends('layouts.admin')
@section('title', __('Work Calendar') . ' - ' . $customer->name)

@section('styles')
<style>
    /* Estilos para días laborables y no laborables */
    .calendar-day.working-day {
        background-color: rgba(40, 167, 69, 0.1); /* Verde claro para días laborables */
        border-left: 3px solid #28a745;
    }
    
    .calendar-day.non-working-day {
        background-color: rgba(220, 53, 69, 0.1); /* Rojo claro para días no laborables */
        border-left: 3px solid #dc3545;
    }
    
    /* Indicador de estado del día */
    .day-status {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 0.7rem;
    }
    
    .calendar-day {
        position: relative;
        min-height: 100px;
    }
</style>
@endsection

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item">{{ $customer->name }}</li>
        <li class="breadcrumb-item">{{ __('Work Calendar') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="mb-3 d-flex justify-content-between">
                <div>
                    {{-- Botones de navegación de meses --}}
                    <a href="{{ route('customers.work-calendars.index', ['customer' => $customer->id, 'month' => $previousMonth->month, 'year' => $previousMonth->year]) }}" class="btn btn-secondary me-2">
                        <i class="fas fa-chevron-left me-1"></i> {{ $previousMonth->format('F Y') }}
                    </a>
                    <a href="{{ route('customers.work-calendars.index', ['customer' => $customer->id, 'month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" class="btn btn-secondary">
                        {{ $nextMonth->format('F Y') }} <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </div>
                <div>
                    {{-- Botón para configuración masiva --}}
                    @can('workcalendar-create')
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                            <i class="fas fa-calendar-alt me-1"></i> {{ __('Mass Configuration') }}
                        </button>
                    @endcan
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-transparent text-dark border-bottom">
                    <h4 class="mb-0">{{ __('Work Calendar') }} - {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h4>
                </div>
                <div class="card-body">
                    {{-- Calendario mensual --}}
                    <div class="table-responsive">
                        <table class="table table-bordered calendar-table">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center">{{ __('Monday') }}</th>
                                    <th class="text-center">{{ __('Tuesday') }}</th>
                                    <th class="text-center">{{ __('Wednesday') }}</th>
                                    <th class="text-center">{{ __('Thursday') }}</th>
                                    <th class="text-center">{{ __('Friday') }}</th>
                                    <th class="text-center">{{ __('Saturday') }}</th>
                                    <th class="text-center">{{ __('Sunday') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Obtener el primer día del mes
                                    $firstDay = \Carbon\Carbon::createFromDate($year, $month, 1);
                                    
                                    // Ajustar al lunes anterior si el mes no comienza en lunes
                                    $startDay = clone $firstDay;
                                    if ($startDay->dayOfWeek !== 1) {
                                        $startDay->subDays($startDay->dayOfWeek === 0 ? 6 : $startDay->dayOfWeek - 1);
                                    }
                                    
                                    // Obtener el último día del mes
                                    $lastDay = clone $firstDay;
                                    $lastDay->endOfMonth();
                                    
                                    // Ajustar al domingo siguiente si el mes no termina en domingo
                                    $endDay = clone $lastDay;
                                    if ($endDay->dayOfWeek !== 0) {
                                        $endDay->addDays(7 - $endDay->dayOfWeek);
                                    }
                                    
                                    $currentDay = clone $startDay;
                                @endphp
                                
                                @while ($currentDay <= $endDay)
                                    @if ($currentDay->dayOfWeek === 1)
                                        <tr>
                                    @endif
                                    
                                    @php
                                        $dateString = $currentDay->format('Y-m-d');
                                        $isCurrentMonth = $currentDay->month === $month;
                                        $isToday = $currentDay->isToday();
                                        $dayData = $daysInMonth[$dateString] ?? null;
                                        $hasData = !is_null($dayData);
                                        $isWorkingDay = $hasData ? ($dayData['is_working_day'] ?? false) : null;
                                        $dayType = $hasData ? ($dayData['type'] ?? null) : null;
                                        $dayName = $hasData ? ($dayData['name'] ?? null) : null;
                                        $dayDescription = $hasData ? ($dayData['description'] ?? null) : null;
                                        $existsInDb = $hasData;
                                        $dayId = $hasData ? ($dayData->id ?? null) : null;
                                    @endphp
                                    
                                    <td class="calendar-day {{ !$isCurrentMonth ? 'text-muted bg-light' : '' }} 
                                              {{ $isToday ? 'today' : '' }}
                                              {{ $hasData && $isWorkingDay ? 'working-day' : '' }}
                                              {{ $hasData && $isWorkingDay === false ? 'non-working-day' : '' }}
                                              {{ $hasData && $dayType ? $dayType : '' }}"
                                        data-date="{{ $dateString }}">
                                        
                                        <div class="day-header d-flex justify-content-between align-items-center mb-2">
                                            <span class="day-number {{ $isToday ? 'badge bg-primary rounded-pill' : '' }}">
                                                {{ $currentDay->day }}
                                            </span>
                                            
                                            <!-- Indicador de día laborable/no laborable (se muestra solo en contenido para evitar duplicados) -->
                                            
                                            @if ($isCurrentMonth && $existsInDb && $dayId)
                                                <div class="day-actions">
                                                    @can('workcalendar-edit')
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-secondary btn-edit-day"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Edit') }}"
                                                                data-id="{{ $dayId }}"
                                                                data-date="{{ $dateString }}"
                                                                data-name="{{ $dayName }}"
                                                                data-type="{{ $dayType }}"
                                                                data-is-working-day="{{ $isWorkingDay ? 1 : 0 }}"
                                                                data-description="{{ $dayDescription }}"
                                                                data-update-url="{{ route('customers.work-calendars.update', ['customer' => $customer->id, 'calendar' => $dayId]) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endcan

                                                    @can('workcalendar-delete')
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger btn-delete-day"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Delete') }}"
                                                                data-id="{{ $dayId }}"
                                                                data-date="{{ $dateString }}"
                                                                data-destroy-url="{{ route('customers.work-calendars.destroy', ['customer' => $customer->id, 'calendar' => $dayId]) }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            @elseif ($isCurrentMonth)
                                                <div class="day-actions">
                                                    @can('workcalendar-create')
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary btn-add-day" 
                                                                data-bs-toggle="tooltip" 
                                                                title="{{ __('Add') }}"
                                                                data-date="{{ $dateString }}">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            @endif
                                        </div>
                                        
                                        @if ($isCurrentMonth && $hasData)
                                            <div class="day-content">
                                                <div class="mb-1">
                                                    <span class="badge {{ $isWorkingDay ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $isWorkingDay ? __('Laborable') : __('No laborable') }}
                                                    </span>
                                                    @if ($dayType)
                                                        @switch($dayType)
                                                            @case('holiday')
                                                                <span class="badge bg-danger">{{ __('Festivo') }}</span>
                                                                @break
                                                            @case('maintenance')
                                                                <span class="badge bg-warning text-dark">{{ __('Mantenimiento') }}</span>
                                                                @break
                                                            @case('vacation')
                                                                <span class="badge bg-info">{{ __('Vacaciones') }}</span>
                                                                @break
                                                            @case('weekend')
                                                                <span class="badge bg-secondary">{{ __('Fin de semana') }}</span>
                                                                @break
                                                        @endswitch
                                                    @endif
                                                </div>
                                                @php
                                                    $statusText = $dayName ?: ($isWorkingDay ? __('Se Trabaja') : __('No se trabaja'));
                                                @endphp
                                                <div class="fw-semibold">{{ $statusText }}</div>
                                                @if ($dayDescription)
                                                    <div class="day-description small text-muted mt-1">
                                                        {{ \Illuminate\Support\Str::limit($dayDescription, 80) }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    
                                    @if ($currentDay->dayOfWeek === 0)
                                        </tr>
                                    @endif
                                    
                                    @php
                                        $currentDay->addDay();
                                    @endphp
                                @endwhile
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Leyenda --}}
                    <div class="calendar-legend mt-4">
                        <h5>{{ __('Leyenda') }}</h5>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="legend-item d-flex align-items-center gap-2">
                                <span class="badge bg-success">{{ __('Laborable') }}</span>
                                <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="{{ __('Día de trabajo regular') }}"></i>
                            </div>
                            <div class="legend-item d-flex align-items-center gap-2">
                                <span class="badge bg-danger">{{ __('Festivo') }}</span>
                                <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="{{ __('Feriados y días oficiales no laborables') }}"></i>
                            </div>
                            <div class="legend-item d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark">{{ __('Mantenimiento') }}</span>
                                <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="{{ __('Paradas por mantenimiento') }}"></i>
                            </div>
                            <div class="legend-item d-flex align-items-center gap-2">
                                <span class="badge bg-info">{{ __('Vacaciones') }}</span>
                                <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="{{ __('Vacaciones programadas') }}"></i>
                            </div>
                            <div class="legend-item d-flex align-items-center gap-2">
                                <span class="badge bg-secondary">{{ __('Fin de semana') }}</span>
                                <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="{{ __('Sábado y Domingo') }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    .calendar-table {
        table-layout: fixed;
    }
    
    .calendar-day {
        height: 120px;
        vertical-align: top;
        padding: 8px;
        position: relative;
    }
    
    .day-number {
        font-weight: bold;
        font-size: 1.1em;
    }
    
    .today {
        background-color: rgba(0, 123, 255, 0.1);
        border: 2px solid #007bff;
    }
    
    .working-day {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .non-working-day {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .day-actions {
        opacity: 0.2;
        transition: opacity 0.2s;
    }
    
    .calendar-day:hover .day-actions {
        opacity: 1;
    }
    
    .day-content {
        margin-top: 8px;
    }
    
    .day-name {
        font-weight: bold;
        margin-bottom: 4px;
    }
    
    .calendar-legend {
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
    }
</style>
@endpush

{{-- Modal para configuración masiva --}}
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('customers.work-calendars.bulk-update', $customer->id) }}" method="POST" id="bulkUpdateForm">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                
                <div class="modal-header bg-transparent text-dark border-bottom">
                    <h5 class="modal-title" id="bulkUpdateModalLabel">{{ __('Mass Calendar Configuration') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('This will update all selected days in the current month. Any existing configuration for these days will be overwritten.') }}
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('Select days to configure') }}</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="day_type" id="dayTypeWeekdays" value="weekdays" checked>
                            <label class="form-check-label" for="dayTypeWeekdays">
                                {{ __('Weekdays') }} ({{ __('Monday') }} - {{ __('Friday') }})
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="day_type" id="dayTypeWeekends" value="weekends">
                            <label class="form-check-label" for="dayTypeWeekends">
                                {{ __('Weekends') }} ({{ __('Saturday') }} - {{ __('Sunday') }})
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="day_type" id="dayTypeAll" value="all">
                            <label class="form-check-label" for="dayTypeAll">
                                {{ __('All days') }}
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('Period') }}</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="period" id="periodMonth" value="month" checked>
                            <label class="form-check-label" for="periodMonth">
                                {{ __('Current month') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="period" id="periodYear" value="year">
                            <label class="form-check-label" for="periodYear">
                                {{ __('Entire year') }} ({{ __('of') }} {{ $year }})
                            </label>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="type" class="form-label fw-bold">{{ __('Day type') }}</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="workday">{{ __('Working Day') }}</option>
                                <option value="holiday">{{ __('Holiday') }}</option>
                                <option value="maintenance">{{ __('Maintenance') }}</option>
                                <option value="vacation">{{ __('Vacation') }}</option>
                                <option value="weekend">{{ __('Weekend') }}</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-bold">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">{{ __('Example: Regular Workday, National Holiday, etc.') }}</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_working_day" name="is_working_day" value="1">
                            <label class="form-check-label fw-bold" for="is_working_day">{{ __('Is Working Day?') }}</label>
                        </div>
                        <div class="form-text">{{ __('Toggle on if these are working days, off if they are non-working days') }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">{{ __('Description') }} ({{ __('optional') }})</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> {{ __('Save Configuration') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@push('scripts')
<script>
    $(function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Actualizar automáticamente el estado de is_working_day según el tipo seleccionado
        $('#type').change(function() {
            var type = $(this).val();
            var workingDayTypes = ['workday'];
            var isWorkingDay = workingDayTypes.includes(type);
            
            $('#is_working_day').prop('checked', isWorkingDay);
        });
        
        // Inicializar con valores predeterminados
        $('#type').trigger('change');
        
        // Procesar el formulario de configuración masiva
        $('#bulkUpdateForm').submit(function(e) {
            e.preventDefault();
            
            var year = parseInt($('input[name="year"]').val());
            var month = parseInt($('input[name="month"]').val());
            var period = $('input[name="period"]:checked').val();
            var dayType = $('input[name="day_type"]:checked').val();
            var type = $('#type').val();
            var name = $('#name').val();
            var isWorkingDay = $('#is_working_day').is(':checked') ? 1 : 0;
            var description = $('#description').val();
            
            var days = [];
            
            function pushDaysForMonth(y, m) {
                var dim = new Date(y, m, 0).getDate(); // m is 1-12
                for (var d = 1; d <= dim; d++) {
                    var jsDate = new Date(y, m - 1, d);
                    var dow = jsDate.getDay(); // 0=Sun, 6=Sat
                    var include = false;
                    if (dayType === 'all') {
                        include = true;
                    } else if (dayType === 'weekdays' && dow >= 1 && dow <= 5) {
                        include = true;
                    } else if (dayType === 'weekends' && (dow === 0 || dow === 6)) {
                        include = true;
                    }
                    if (include) {
                        days.push({
                            date: y + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d),
                            type: type,
                            name: name,
                            is_working_day: isWorkingDay,
                            description: description
                        });
                    }
                }
            }

            if (period === 'year') {
                for (var m = 1; m <= 12; m++) {
                    pushDaysForMonth(year, m);
                }
            } else {
                pushDaysForMonth(year, month);
            }
            
            // Cerrar el modal antes de enviar la solicitud
            $('#bulkUpdateModal').modal('hide');
            
            // Enviar la solicitud AJAX con el array de días
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('Success') }}',
                            text: response.message,
                            confirmButtonText: '{{ __('OK') }}'
                        }).then(() => {
                            // Recargar la página para ver los cambios
                            window.location.reload();
                        });
                    } else {
                        // Mostrar mensaje de error
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: response.message,
                            confirmButtonText: '{{ __('OK') }}'
                        });
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors || {};
                    var errorMessage = xhr.responseJSON?.message || '{{ __('An error occurred') }}';
                    
                    // Construir mensaje de error
                    var errorHtml = '<ul>';
                    for (var field in errors) {
                        errors[field].forEach(function(message) {
                            errorHtml += '<li>' + message + '</li>';
                        });
                    }
                    errorHtml += '</ul>';
                    
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        html: errorMessage + errorHtml,
                        confirmButtonText: '{{ __('OK') }}'
                    });
                }
            });
        });

        // --- Modales Add / Edit / Delete ---
        var dayModalEl = document.getElementById('dayModal');
        var dayModal = new bootstrap.Modal(dayModalEl);
        var deleteModalEl = document.getElementById('deleteDayModal');
        var deleteModal = new bootstrap.Modal(deleteModalEl);

        function fillDayForm(data) {
            $('#dayForm')[0].reset();
            $('#dayForm [name="calendar_date"]').val(data.date || '');
            $('#dayForm [name="type"]').val(data.type || 'workday');
            $('#dayForm [name="name"]').val(data.name || '');
            $('#dayForm [name="is_working_day"]').prop('checked', (data.is_working_day ?? 1) == 1);
            $('#dayForm [name="description"]').val(data.description || '');
        }

        // Add
        $(document).on('click', '.btn-add-day', function() {
            var date = $(this).data('date');
            $('#dayModalLabel').text('{{ __('Añadir día') }}');
            $('#dayForm').attr('action', $(this).closest('form').length ? $(this).closest('form').attr('action') : '{{ route('customers.work-calendars.store', $customer->id) }}');
            $('#dayForm').data('method', 'POST');
            fillDayForm({ date: date, is_working_day: 1 });
            dayModal.show();
        });

        // Edit
        $(document).on('click', '.btn-edit-day', function() {
            $('#dayModalLabel').text('{{ __('Editar día') }}');
            var updateUrl = $(this).data('update-url');
            $('#dayForm').attr('action', updateUrl);
            $('#dayForm').data('method', 'PUT');
            fillDayForm({
                date: $(this).data('date'),
                type: $(this).data('type'),
                name: $(this).data('name'),
                is_working_day: $(this).data('is-working-day'),
                description: $(this).data('description')
            });
            dayModal.show();
        });

        // Submit Add/Edit via AJAX
        $('#dayForm').on('submit', function(e) {
            e.preventDefault();
            var method = $('#dayForm').data('method') || 'POST';
            var action = $(this).attr('action');
            var payload = $(this).serializeArray();
            if (method === 'PUT') {
                payload.push({ name: '_method', value: 'PUT' });
            }
            $.ajax({
                url: action,
                method: 'POST',
                data: payload,
                success: function(resp){
                    dayModal.hide();
                    Swal.fire({ icon: 'success', title: '{{ __('Éxito') }}', text: resp.message || '{{ __('Guardado correctamente') }}' })
                        .then(() => window.location.reload());
                },
                error: function(xhr){
                    var msg = xhr.responseJSON?.message || '{{ __('Ha ocurrido un error') }}';
                    Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: msg });
                }
            });
        });

        // Delete
        $(document).on('click', '.btn-delete-day', function(){
            $('#deleteDateText').text($(this).data('date'));
            $('#deleteForm').attr('action', $(this).data('destroy-url'));
            deleteModal.show();
        });

        $('#deleteForm').on('submit', function(e){
            e.preventDefault();
            var action = $(this).attr('action');
            $.ajax({
                url: action,
                method: 'POST',
                data: { _token: $('input[name="_token"]').val(), _method: 'DELETE' },
                success: function(resp){
                    deleteModal.hide();
                    Swal.fire({ icon: 'success', title: '{{ __('Éxito') }}', text: resp.message || '{{ __('Eliminado correctamente') }}' })
                        .then(() => window.location.reload());
                },
                error: function(xhr){
                    var msg = xhr.responseJSON?.message || '{{ __('Ha ocurrido un error') }}';
                    Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: msg });
                }
            });
        });
    });
</script>
@endpush

{{-- Modal Add/Edit Day --}}
<div class="modal fade" id="dayModal" tabindex="-1" aria-labelledby="dayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="dayForm" action="{{ route('customers.work-calendars.store', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="dayModalLabel">{{ __('Añadir día') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Fecha') }}</label>
                        <input type="date" class="form-control" name="calendar_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tipo') }}</label>
                        <select class="form-select" name="type" required>
                            <option value="workday">{{ __('Working Day') }}</option>
                            <option value="holiday">{{ __('Holiday') }}</option>
                            <option value="maintenance">{{ __('Maintenance') }}</option>
                            <option value="vacation">{{ __('Vacation') }}</option>
                            <option value="weekend">{{ __('Weekend') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nombre') }}</label>
                        <input type="text" class="form-control" name="name">
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_working_day" value="0">
                        <input class="form-check-input" type="checkbox" id="modalIsWorking" name="is_working_day" value="1">
                        <label class="form-check-label" for="modalIsWorking">{{ __('¿Es laborable?') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Descripción') }}</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Guardar') }}</button>
                </div>
            </form>
        </div>
    </div>
    </div>

{{-- Modal Delete Confirm --}}
<div class="modal fade" id="deleteDayModal" tabindex="-1" aria-labelledby="deleteDayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="POST" action="#">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDayModalLabel">{{ __('Eliminar día') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ __('¿Seguro que deseas eliminar el día') }} <strong id="deleteDateText"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Eliminar') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

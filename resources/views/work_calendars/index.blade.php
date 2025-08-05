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
                    {{-- Botón para añadir día especial --}}
                    @can('workcalendar-create')
                        <a href="{{ route('customers.work-calendars.create', ['customer' => $customer->id]) }}" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-1"></i> {{ __('Add Special Day') }}
                        </a>
                        
                        {{-- Botón para configuración masiva --}}
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                            <i class="fas fa-calendar-alt me-1"></i> {{ __('Mass Configuration') }}
                        </button>
                    @endcan
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
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
                                        $isWorkingDay = $dayData ? $dayData['is_working_day'] : !$currentDay->isWeekend();
                                        $dayType = $dayData ? $dayData['type'] : ($currentDay->isWeekend() ? 'weekend' : 'workday');
                                        $dayName = $dayData ? $dayData['name'] : null;
                                        $dayDescription = $dayData ? $dayData['description'] : null;
                                        $existsInDb = $dayData ? ($dayData['exists_in_db'] ?? true) : false;
                                        $dayId = $dayData ? $dayData->id ?? null : null;
                                    @endphp
                                    
                                    <td class="calendar-day {{ !$isCurrentMonth ? 'text-muted bg-light' : '' }} 
                                              {{ $isToday ? 'today' : '' }}
                                              {{ $isWorkingDay ? 'working-day' : 'non-working-day' }}
                                              {{ $dayType }}"
                                        data-date="{{ $dateString }}">
                                        
                                        <div class="day-header d-flex justify-content-between align-items-center mb-2">
                                            <span class="day-number {{ $isToday ? 'badge bg-primary rounded-pill' : '' }}">
                                                {{ $currentDay->day }}
                                            </span>
                                            
                                            <!-- Indicador de día laborable/no laborable -->
                                            @if ($isCurrentMonth)
                                                <span class="day-status badge {{ $isWorkingDay ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $isWorkingDay ? __('Working') : __('Non-Working') }}
                                                </span>
                                            @endif
                                            
                                            @if ($isCurrentMonth && $existsInDb && $dayId)
                                                <div class="day-actions">
                                                    @can('workcalendar-edit')
                                                        <a href="{{ route('customers.work-calendars.edit', ['customer' => $customer->id, 'calendar' => $dayId]) }}" 
                                                           class="btn btn-sm btn-outline-secondary" 
                                                           data-bs-toggle="tooltip" 
                                                           title="{{ __('Edit') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    
                                                    @can('workcalendar-delete')
                                                        <form action="{{ route('customers.work-calendars.destroy', ['customer' => $customer->id, 'calendar' => $dayId]) }}" 
                                                              method="POST" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-sm btn-outline-danger" 
                                                                    data-bs-toggle="tooltip" 
                                                                    title="{{ __('Delete') }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            @elseif ($isCurrentMonth)
                                                <div class="day-actions">
                                                    @can('workcalendar-create')
                                                        <a href="{{ route('customers.work-calendars.create', ['customer' => $customer->id, 'date' => $dateString]) }}" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           data-bs-toggle="tooltip" 
                                                           title="{{ __('Add') }}">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    @endcan
                                                </div>
                                            @endif
                                        </div>
                                        
                                        @if ($isCurrentMonth && ($dayName || $dayType !== 'workday'))
                                            <div class="day-content">
                                                @if ($dayName)
                                                    <div class="day-name">{{ $dayName }}</div>
                                                @endif
                                                
                                                <div class="day-type">
                                                    @switch($dayType)
                                                        @case('holiday')
                                                            <span class="badge bg-danger">{{ __('Holiday') }}</span>
                                                            @break
                                                        @case('maintenance')
                                                            <span class="badge bg-warning text-dark">{{ __('Maintenance') }}</span>
                                                            @break
                                                        @case('vacation')
                                                            <span class="badge bg-info">{{ __('Vacation') }}</span>
                                                            @break
                                                        @case('weekend')
                                                            <span class="badge bg-secondary">{{ __('Weekend') }}</span>
                                                            @break
                                                        @case('special')
                                                            <span class="badge bg-primary">{{ __('Special') }}</span>
                                                            @break
                                                    @endswitch
                                                </div>
                                                
                                                @if ($dayDescription)
                                                    <div class="day-description small text-muted mt-1">
                                                        {{ \Illuminate\Support\Str::limit($dayDescription, 50) }}
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
                        <h5>{{ __('Legend') }}</h5>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="legend-item">
                                <span class="badge bg-success">{{ __('Working Day') }}</span>
                            </div>
                            <div class="legend-item">
                                <span class="badge bg-danger">{{ __('Holiday') }}</span>
                            </div>
                            <div class="legend-item">
                                <span class="badge bg-warning text-dark">{{ __('Maintenance') }}</span>
                            </div>
                            <div class="legend-item">
                                <span class="badge bg-info">{{ __('Vacation') }}</span>
                            </div>
                            <div class="legend-item">
                                <span class="badge bg-secondary">{{ __('Weekend') }}</span>
                            </div>
                            <div class="legend-item">
                                <span class="badge bg-primary">{{ __('Special') }}</span>
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
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bulkUpdateModalLabel">{{ __('Mass Calendar Configuration') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('This will update all selected days in the current month. Any existing special configuration for these days will be overwritten.') }}
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
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="type" class="form-label fw-bold">{{ __('Day type') }}</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="workday">{{ __('Working Day') }}</option>
                                <option value="holiday">{{ __('Holiday') }}</option>
                                <option value="maintenance">{{ __('Maintenance') }}</option>
                                <option value="vacation">{{ __('Vacation') }}</option>
                                <option value="weekend">{{ __('Weekend') }}</option>
                                <option value="special">{{ __('Special') }}</option>
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
            var workingDayTypes = ['workday', 'special'];
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
            var dayType = $('input[name="day_type"]:checked').val();
            var type = $('#type').val();
            var name = $('#name').val();
            var isWorkingDay = $('#is_working_day').is(':checked') ? 1 : 0;
            var description = $('#description').val();
            
            // Obtener todos los días del mes
            var daysInMonth = new Date(year, month, 0).getDate();
            var days = [];
            
            // Crear array de días según la selección
            for (var day = 1; day <= daysInMonth; day++) {
                var date = new Date(year, month - 1, day);
                var dayOfWeek = date.getDay(); // 0 = domingo, 6 = sábado
                
                // Determinar si este día debe ser incluido según la selección
                var include = false;
                
                if (dayType === 'all') {
                    include = true;
                } else if (dayType === 'weekdays' && dayOfWeek >= 1 && dayOfWeek <= 5) {
                    include = true;
                } else if (dayType === 'weekends' && (dayOfWeek === 0 || dayOfWeek === 6)) {
                    include = true;
                }
                
                if (include) {
                    days.push({
                        date: year + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day),
                        type: type,
                        name: name,
                        is_working_day: isWorkingDay,
                        description: description
                    });
                }
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
    });
</script>
@endpush

@extends('layouts.admin')
@section('title', __('Roles Management'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Roles') }}</li>
    </ul>
@endsection

@section('content')
<div class="roles-container">
    {{-- Header --}}
    <div class="roles-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h4 class="roles-title mb-0">
                    <i class="ti ti-shield-lock me-2"></i>{{ __('Roles Management') }}
                    <span class="badge bg-white text-primary ms-2">{{ $roles->count() }}</span>
                </h4>
                <p class="text-white-50 mb-0 mt-1">{{ __('Manage user roles and their permissions') }}</p>
            </div>
            <div class="col-md-6 text-end">
                @can('create-role')
                <a href="{{ route('roles.create') }}" class="btn btn-light">
                    <i class="ti ti-plus me-1"></i> {{ __('Create Role') }}
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card stats-primary">
                <div class="stats-icon">
                    <i class="ti ti-shield"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $roles->count() }}</h3>
                    <span>{{ __('Total Roles') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card stats-success">
                <div class="stats-icon">
                    <i class="ti ti-key"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $totalPermissions }}</h3>
                    <span>{{ __('Total Permissions') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card stats-info">
                <div class="stats-icon">
                    <i class="ti ti-users"></i>
                </div>
                <div class="stats-info">
                    <h3>{{ $roles->sum('users_count') }}</h3>
                    <span>{{ __('Users with Roles') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Roles Grid --}}
    <div class="row" id="rolesGrid">
        @forelse($roles as $role)
        <div class="col-xl-4 col-lg-6 col-md-6 mb-4 role-card-wrapper" data-name="{{ strtolower($role->name) }}">
            <div class="card role-card h-100">
                {{-- Card Header --}}
                <div class="role-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="role-info">
                            <div class="role-icon">
                                <i class="ti ti-shield-check"></i>
                            </div>
                            <div>
                                <h5 class="role-name mb-0">{{ ucfirst($role->name) }}</h5>
                                <small class="text-white-50">
                                    <i class="ti ti-calendar me-1"></i>{{ $role->created_at->format('d/m/Y') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats Row --}}
                <div class="role-stats">
                    <div class="role-stat">
                        <div class="role-stat-icon bg-primary-light">
                            <i class="ti ti-key text-primary"></i>
                        </div>
                        <div class="role-stat-data">
                            <span class="role-stat-value">{{ $role->permissions_count }}</span>
                            <span class="role-stat-label">{{ __('Permissions') }}</span>
                        </div>
                    </div>
                    <div class="role-stat">
                        <div class="role-stat-icon bg-success-light">
                            <i class="ti ti-users text-success"></i>
                        </div>
                        <div class="role-stat-data">
                            <span class="role-stat-value">{{ $role->users_count }}</span>
                            <span class="role-stat-label">{{ __('Users') }}</span>
                        </div>
                    </div>
                    <div class="role-stat">
                        <div class="role-stat-icon bg-info-light">
                            <i class="ti ti-percentage text-info"></i>
                        </div>
                        <div class="role-stat-data">
                            <span class="role-stat-value">{{ $totalPermissions > 0 ? round(($role->permissions_count / $totalPermissions) * 100) : 0 }}%</span>
                            <span class="role-stat-label">{{ __('Access') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Permissions Preview --}}
                <div class="role-card-body">
                    <div class="permissions-section">
                        <div class="permissions-label">
                            <i class="ti ti-lock me-1"></i> {{ __('Permissions') }}
                            @if($role->permissions_count > 10)
                            <span class="badge bg-light text-secondary ms-1">+{{ $role->permissions_count - 10 }} {{ __('more') }}</span>
                            @endif
                        </div>
                        <div class="permissions-tags">
                            @forelse($role->permissions->take(10) as $permission)
                            <span class="permission-tag">{{ $permission->name }}</span>
                            @empty
                            <span class="text-muted fst-italic">{{ __('No permissions assigned') }}</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="permissions-progress mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">{{ __('Permissions coverage') }}</small>
                            <small class="text-muted">{{ $role->permissions_count }}/{{ $totalPermissions }}</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar"
                                 style="width: {{ $totalPermissions > 0 ? ($role->permissions_count / $totalPermissions) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="role-card-footer">
                    @can('show-role')
                    <a href="{{ route('roles.show', $role->id) }}" class="role-btn role-btn-info" title="{{ __('View Permissions') }}">
                        <i class="ti ti-eye"></i>
                        <span>{{ __('View') }}</span>
                    </a>
                    @endcan
                    @can('edit-role')
                    <a href="{{ route('roles.edit', $role->id) }}" class="role-btn role-btn-primary" title="{{ __('Edit Role') }}">
                        <i class="ti ti-edit"></i>
                        <span>{{ __('Edit') }}</span>
                    </a>
                    @endcan
                    @can('delete-role')
                    <button type="button" class="role-btn role-btn-danger btn-delete-role"
                            data-id="{{ $role->id }}"
                            data-name="{{ $role->name }}"
                            data-users="{{ $role->users_count }}"
                            title="{{ __('Delete Role') }}">
                        <i class="ti ti-trash"></i>
                        <span>{{ __('Delete') }}</span>
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card role-empty-state">
                <div class="card-body text-center py-5">
                    <i class="ti ti-shield-off display-1 text-muted mb-3"></i>
                    <h4>{{ __('No roles found') }}</h4>
                    <p class="text-muted">{{ __('Start by creating your first role') }}</p>
                    @can('create-role')
                    <a href="{{ route('roles.create') }}" class="btn btn-primary mt-3">
                        <i class="ti ti-plus me-1"></i> {{ __('Create Role') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.roles-container {
    padding: 0;
}

/* Header */
.roles-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.roles-title {
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
}

/* Stats Cards */
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stats-primary .stats-icon {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
}

.stats-success .stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.stats-info .stats-icon {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.stats-info h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* Role Card */
.role-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.role-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* Card Header */
.role-card-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: 20px;
    color: white;
}

.role-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.role-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.role-name {
    font-size: 1.15rem;
    font-weight: 600;
    color: white;
}

/* Stats Row */
.role-stats {
    display: flex;
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    gap: 12px;
}

.role-stat {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.role-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.role-stat-data {
    display: flex;
    flex-direction: column;
}

.role-stat-value {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
    color: #1e293b;
}

.role-stat-label {
    font-size: 0.65rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Background colors */
.bg-primary-light { background: rgba(99,102,241,0.15); }
.bg-success-light { background: rgba(34,197,94,0.15); }
.bg-info-light { background: rgba(14,165,233,0.15); }

/* Card Body */
.role-card-body {
    padding: 16px 20px;
}

.permissions-section {
    margin-bottom: 8px;
}

.permissions-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.permissions-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 60px;
}

.permission-tag {
    display: inline-block;
    padding: 4px 10px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Card Footer */
.role-card-footer {
    padding: 16px 20px;
    background: #f8fafc;
    display: flex;
    gap: 8px;
    border-top: 1px solid #e2e8f0;
}

.role-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    flex: 1;
}

.role-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.role-btn-primary {
    background: #6366f1;
    color: white;
}
.role-btn-primary:hover {
    background: #4f46e5;
    color: white;
}

.role-btn-info {
    background: #0ea5e9;
    color: white;
}
.role-btn-info:hover {
    background: #0284c7;
    color: white;
}

.role-btn-danger {
    background: transparent;
    color: #ef4444;
    border: 1px solid #fecaca;
}
.role-btn-danger:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #ef4444;
}

/* Empty State */
.role-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
}

/* Animations */
.role-card-wrapper {
    animation: fadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.role-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.role-card-wrapper:nth-child(2) { animation-delay: 0.15s; }
.role-card-wrapper:nth-child(3) { animation-delay: 0.2s; }
.role-card-wrapper:nth-child(4) { animation-delay: 0.25s; }
.role-card-wrapper:nth-child(5) { animation-delay: 0.3s; }
.role-card-wrapper:nth-child(6) { animation-delay: 0.35s; }

/* Responsive */
@media (max-width: 991.98px) {
    .roles-header .row {
        gap: 16px;
    }
    .roles-header .col-md-6 {
        text-align: center !important;
    }
    .roles-title {
        justify-content: center;
    }
}

@media (max-width: 767.98px) {
    .role-stats {
        flex-direction: column;
    }
    .stats-card {
        margin-bottom: 12px;
    }
}

/* Dark mode */
[data-theme="dark"] .role-card {
    background: #1e293b;
}

[data-theme="dark"] .role-stats {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .role-stat {
    background: #1e293b;
}

[data-theme="dark"] .role-stat-value {
    color: #f1f5f9;
}

[data-theme="dark"] .role-card-footer {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .permission-tag {
    background: #334155;
    color: #cbd5e1;
}

[data-theme="dark"] .stats-card {
    background: #1e293b;
}

[data-theme="dark"] .stats-info h3 {
    color: #f1f5f9;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Eliminar rol - DOBLE CONFIRMACIÓN
    $(document).on('click', '.btn-delete-role', function() {
        var roleId = $(this).data('id');
        var roleName = $(this).data('name');
        var usersCount = $(this).data('users');
        var $card = $(this).closest('.role-card-wrapper');

        // Verificar si hay usuarios asignados
        var warningHtml = '{!! __("You are about to delete the role") !!} <strong>' + roleName + '</strong>.';

        if (usersCount > 0) {
            warningHtml += '<br><br><div class="alert alert-danger py-2 px-3 mb-0">' +
                '<i class="ti ti-alert-triangle me-1"></i>' +
                '<strong>' + usersCount + '</strong> {!! __("user(s) have this role assigned.") !!}<br>' +
                '<small>{!! __("They will lose all permissions associated with this role.") !!}</small>' +
                '</div>';
        }

        // PRIMERA CONFIRMACIÓN
        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            html: warningHtml,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            confirmButtonText: '{{ __("Continue") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // SEGUNDA CONFIRMACIÓN - Escribir nombre para confirmar
                Swal.fire({
                    title: '{{ __("Final confirmation") }}',
                    html: '<p class="mb-3">{!! __("To confirm deletion, type the name of the role:") !!}</p>' +
                          '<p class="fw-bold text-danger mb-3">' + roleName + '</p>',
                    icon: 'error',
                    input: 'text',
                    inputPlaceholder: '{{ __("Type the role name here...") }}',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocomplete: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '{{ __("Delete permanently") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    reverseButtons: true,
                    preConfirm: (inputValue) => {
                        if (inputValue.toLowerCase() !== roleName.toLowerCase()) {
                            Swal.showValidationMessage('{{ __("The name does not match. Please type it exactly.") }}');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: '{{ __("Deleting...") }}',
                            html: '{{ __("Please wait while we delete the role.") }}',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '{{ url("manage-role") }}/' + roleId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                $card.fadeOut(400, function() {
                                    $(this).remove();
                                    // Actualizar contador
                                    var count = $('.role-card-wrapper:visible').length;
                                    $('.roles-title .badge').text(count);
                                });

                                Swal.fire({
                                    title: '{{ __("Deleted!") }}',
                                    text: '{{ __("The role has been deleted.") }}',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function(xhr) {
                                var message = xhr.responseJSON?.message || '{{ __("Something went wrong!") }}';
                                Swal.fire({
                                    title: '{{ __("Error!") }}',
                                    text: message,
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush

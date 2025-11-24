@extends('layouts.admin')
@section('title', __('Roles Management'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Roles') }}</li>
    </ul>
@endsection

@section('content')
<div class="roles-management-container">
    {{-- Header --}}
    <div class="rm-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h4 class="rm-title mb-0">
                    <i class="ti ti-shield-lock me-2"></i>{{ __('Roles Management') }}
                    <span class="badge bg-white text-primary ms-2" id="rolesCount">0</span>
                </h4>
                <p class="text-white-50 mb-0 mt-1">{{ __('Manage user roles and their permissions') }}</p>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-light" id="btnAddRole">
                    <i class="ti ti-plus me-1"></i> {{ __('Create Role') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="rm-stats-card rm-stats-primary">
                <div class="rm-stats-icon">
                    <i class="ti ti-shield"></i>
                </div>
                <div class="rm-stats-info">
                    <h3 id="statTotalRoles">0</h3>
                    <span>{{ __('Total Roles') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rm-stats-card rm-stats-success">
                <div class="rm-stats-icon">
                    <i class="ti ti-key"></i>
                </div>
                <div class="rm-stats-info">
                    <h3 id="statTotalPermissions">0</h3>
                    <span>{{ __('Total Permissions') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rm-stats-card rm-stats-info">
                <div class="rm-stats-icon">
                    <i class="ti ti-users"></i>
                </div>
                <div class="rm-stats-info">
                    <h3 id="statTotalUsers">0</h3>
                    <span>{{ __('Users with Roles') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Search Box --}}
    <div class="rm-search-container mb-4">
        <div class="rm-search-box">
            <i class="ti ti-search"></i>
            <input type="text" id="searchRoles" class="form-control" placeholder="{{ __('Search roles...') }}">
        </div>
    </div>

    {{-- Roles Grid --}}
    <div class="row" id="rolesGrid">
        {{-- Cards se cargan dinámicamente --}}
    </div>

    {{-- Loading State --}}
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Loading...') }}</span>
        </div>
        <p class="mt-3 text-muted">{{ __('Loading roles...') }}</p>
    </div>

    {{-- Empty State --}}
    <div id="emptyState" class="col-12 d-none">
        <div class="card rm-empty-state">
            <div class="card-body text-center py-5">
                <i class="ti ti-shield-off display-1 text-muted mb-3"></i>
                <h4>{{ __('No roles found') }}</h4>
                <p class="text-muted">{{ __('Start by creating your first role') }}</p>
                <button type="button" class="btn btn-primary mt-3" id="btnAddRoleEmpty">
                    <i class="ti ti-plus me-1"></i> {{ __('Create Role') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.roles-management-container {
    padding: 0;
}

/* Header */
.rm-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.rm-title {
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
}

/* Stats Cards */
.rm-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.rm-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.rm-stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.rm-stats-primary .rm-stats-icon {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
}

.rm-stats-success .rm-stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.rm-stats-info .rm-stats-icon {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.rm-stats-info h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.rm-stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* Search Box */
.rm-search-container {
    max-width: 400px;
}

.rm-search-box {
    position: relative;
}

.rm-search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #6366f1;
    font-size: 1.1rem;
}

.rm-search-box input {
    padding-left: 48px;
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.rm-search-box input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

/* Role Card */
.rm-role-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.rm-role-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* Card Header */
.rm-card-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: 20px;
    color: white;
}

.rm-role-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rm-role-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.rm-role-name {
    font-size: 1.15rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

/* Stats Row */
.rm-role-stats {
    display: flex;
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    gap: 12px;
}

.rm-role-stat {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.rm-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.rm-stat-data {
    display: flex;
    flex-direction: column;
}

.rm-stat-value {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
    color: #1e293b;
}

.rm-stat-label {
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
.rm-card-body {
    padding: 16px 20px;
}

.rm-permissions-section {
    margin-bottom: 8px;
}

.rm-permissions-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.rm-permissions-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 60px;
    max-height: 80px;
    overflow: hidden;
}

.rm-permission-tag {
    display: inline-block;
    padding: 4px 10px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Card Footer */
.rm-card-footer {
    padding: 16px 20px;
    background: #f8fafc;
    display: flex;
    gap: 8px;
    border-top: 1px solid #e2e8f0;
}

.rm-btn {
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

.rm-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.rm-btn-primary {
    background: #6366f1;
    color: white;
}
.rm-btn-primary:hover {
    background: #4f46e5;
    color: white;
}

.rm-btn-info {
    background: #0ea5e9;
    color: white;
}
.rm-btn-info:hover {
    background: #0284c7;
    color: white;
}

.rm-btn-danger {
    background: transparent;
    color: #ef4444;
    border: 1px solid #fecaca;
}
.rm-btn-danger:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #ef4444;
}

/* Empty State */
.rm-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
    border-radius: 16px;
}

/* Animations */
.rm-card-wrapper {
    animation: rmFadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes rmFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.rm-card-wrapper:nth-child(1) { animation-delay: 0.05s; }
.rm-card-wrapper:nth-child(2) { animation-delay: 0.1s; }
.rm-card-wrapper:nth-child(3) { animation-delay: 0.15s; }
.rm-card-wrapper:nth-child(4) { animation-delay: 0.2s; }
.rm-card-wrapper:nth-child(5) { animation-delay: 0.25s; }
.rm-card-wrapper:nth-child(6) { animation-delay: 0.3s; }

/* Progress bar */
.rm-progress {
    height: 6px;
    border-radius: 3px;
    background: #e2e8f0;
    overflow: hidden;
}

.rm-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

/* Responsive */
@media (max-width: 991.98px) {
    .rm-header .row {
        gap: 16px;
    }
    .rm-header .col-md-6 {
        text-align: center !important;
    }
    .rm-title {
        justify-content: center;
    }
    .rm-search-container {
        max-width: 100%;
    }
}

@media (max-width: 767.98px) {
    .rm-role-stats {
        flex-direction: column;
    }
    .rm-stats-card {
        margin-bottom: 12px;
    }
}

/* Dark mode */
[data-theme="dark"] .rm-role-card {
    background: #1e293b;
}

[data-theme="dark"] .rm-role-stats {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .rm-role-stat {
    background: #1e293b;
}

[data-theme="dark"] .rm-stat-value {
    color: #f1f5f9;
}

[data-theme="dark"] .rm-card-footer {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .rm-permission-tag {
    background: #334155;
    color: #cbd5e1;
}

[data-theme="dark"] .rm-stats-card {
    background: #1e293b;
}

[data-theme="dark"] .rm-stats-info h3 {
    color: #f1f5f9;
}

/* SweetAlert custom styles */
.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    text-align: left;
}

.permission-checkbox {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.permission-checkbox:hover {
    background: #e2e8f0;
}

.permission-checkbox input {
    margin-right: 10px;
}

.permission-checkbox label {
    font-size: 0.85rem;
    color: #475569;
    cursor: pointer;
    margin: 0;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let allRoles = [];
    let allPermissions = [];
    let totalPermissions = 0;

    // Cargar datos iniciales
    loadRoles();
    loadPermissions();

    // Cargar permisos
    function loadPermissions() {
        $.ajax({
            url: '{{ url("manage-permission/list-all") }}',
            method: 'GET',
            success: function(data) {
                allPermissions = data;
                totalPermissions = data.length;
                $('#statTotalPermissions').text(totalPermissions);
            }
        });
    }

    // Cargar roles
    function loadRoles() {
        $('#loadingState').show();
        $('#rolesGrid').html('');
        $('#emptyState').addClass('d-none');

        $.ajax({
            url: '{{ url("manage-role/list-all") }}',
            method: 'GET',
            success: function(data) {
                allRoles = data;
                $('#loadingState').hide();

                if (data.length === 0) {
                    $('#emptyState').removeClass('d-none');
                    updateStats(0, 0);
                    return;
                }

                renderRoles(data);
                updateStats(data.length, calculateTotalUsers(data));
            },
            error: function() {
                $('#loadingState').hide();
                Swal.fire('Error', '{{ __("Could not load roles") }}', 'error');
            }
        });
    }

    // Calcular total de usuarios (simulado - se necesitaría endpoint)
    function calculateTotalUsers(roles) {
        // Por ahora retornamos 0, idealmente el backend debería enviar este dato
        return roles.reduce((sum, role) => sum + (role.users_count || 0), 0);
    }

    // Actualizar estadísticas
    function updateStats(rolesCount, usersCount) {
        $('#statTotalRoles').text(rolesCount);
        $('#rolesCount').text(rolesCount);
        $('#statTotalUsers').text(usersCount);
    }

    // Renderizar roles
    function renderRoles(roles) {
        let html = '';

        roles.forEach((role, index) => {
            const permissions = role.permissions || [];
            const permissionsCount = permissions.length;
            const percentage = totalPermissions > 0 ? Math.round((permissionsCount / totalPermissions) * 100) : 0;

            // Obtener nombres de permisos
            const permissionNames = permissions.map(p => typeof p === 'string' ? p : p.name);
            const displayPermissions = permissionNames.slice(0, 8);
            const moreCount = permissionNames.length - 8;

            html += `
                <div class="col-xl-4 col-lg-6 col-md-6 mb-4 rm-card-wrapper" data-name="${role.name.toLowerCase()}" style="animation-delay: ${index * 0.05}s">
                    <div class="card rm-role-card h-100">
                        <div class="rm-card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rm-role-info">
                                    <div class="rm-role-icon">
                                        <i class="ti ti-shield-check"></i>
                                    </div>
                                    <div>
                                        <h5 class="rm-role-name">${capitalizeFirst(role.name)}</h5>
                                        <small class="text-white-50">
                                            <i class="ti ti-hash me-1"></i>ID: ${role.id}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rm-role-stats">
                            <div class="rm-role-stat">
                                <div class="rm-stat-icon bg-primary-light">
                                    <i class="ti ti-key text-primary"></i>
                                </div>
                                <div class="rm-stat-data">
                                    <span class="rm-stat-value">${permissionsCount}</span>
                                    <span class="rm-stat-label">{{ __('Permissions') }}</span>
                                </div>
                            </div>
                            <div class="rm-role-stat">
                                <div class="rm-stat-icon bg-info-light">
                                    <i class="ti ti-percentage text-info"></i>
                                </div>
                                <div class="rm-stat-data">
                                    <span class="rm-stat-value">${percentage}%</span>
                                    <span class="rm-stat-label">{{ __('Access') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rm-card-body">
                            <div class="rm-permissions-section">
                                <div class="rm-permissions-label">
                                    <i class="ti ti-lock me-1"></i> {{ __('Permissions') }}
                                    ${moreCount > 0 ? `<span class="badge bg-light text-secondary ms-1">+${moreCount} {{ __('more') }}</span>` : ''}
                                </div>
                                <div class="rm-permissions-tags">
                                    ${displayPermissions.length > 0
                                        ? displayPermissions.map(p => `<span class="rm-permission-tag">${p}</span>`).join('')
                                        : '<span class="text-muted fst-italic">{{ __("No permissions assigned") }}</span>'
                                    }
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">{{ __('Permissions coverage') }}</small>
                                    <small class="text-muted">${permissionsCount}/${totalPermissions}</small>
                                </div>
                                <div class="rm-progress">
                                    <div class="rm-progress-bar" style="width: ${percentage}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="rm-card-footer">
                            <button type="button" class="rm-btn rm-btn-info btn-permissions"
                                    data-id="${role.id}"
                                    data-name="${role.name}"
                                    title="{{ __('Manage Permissions') }}">
                                <i class="ti ti-key"></i>
                                <span>{{ __('Permissions') }}</span>
                            </button>
                            <button type="button" class="rm-btn rm-btn-primary btn-edit"
                                    data-id="${role.id}"
                                    data-name="${role.name}"
                                    title="{{ __('Edit Role') }}">
                                <i class="ti ti-edit"></i>
                                <span>{{ __('Edit') }}</span>
                            </button>
                            <button type="button" class="rm-btn rm-btn-danger btn-delete"
                                    data-id="${role.id}"
                                    data-name="${role.name}"
                                    title="{{ __('Delete Role') }}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#rolesGrid').html(html);
    }

    // Capitalizar primera letra
    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Buscador
    $('#searchRoles').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('.rm-card-wrapper').each(function() {
            const name = $(this).data('name');
            if (name.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Añadir rol
    $('#btnAddRole, #btnAddRoleEmpty').on('click', function() {
        Swal.fire({
            title: '{{ __("Create Role") }}',
            html: `
                <div class="mb-3">
                    <input id="roleName" class="form-control" placeholder="{{ __('Role name') }}" style="border-radius: 10px; padding: 12px;">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '{{ __("Create") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#6366f1',
            preConfirm: () => {
                const name = $('#roleName').val().trim();
                if (!name) {
                    Swal.showValidationMessage('{{ __("Role name is required") }}');
                    return false;
                }
                return { name };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("manage-role/store-or-update") }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(result.value),
                    success: function() {
                        Swal.fire({
                            title: '{{ __("Success!") }}',
                            text: '{{ __("Role created successfully") }}',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadRoles();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || '{{ __("Could not create role") }}', 'error');
                    }
                });
            }
        });
    });

    // Editar rol
    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: '{{ __("Edit Role") }}',
            html: `
                <div class="mb-3">
                    <input id="roleName" class="form-control" value="${name}" style="border-radius: 10px; padding: 12px;">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '{{ __("Update") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#6366f1',
            preConfirm: () => {
                const newName = $('#roleName').val().trim();
                if (!newName) {
                    Swal.showValidationMessage('{{ __("Role name is required") }}');
                    return false;
                }
                return { id, name: newName };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("manage-role/store-or-update") }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(result.value),
                    success: function() {
                        Swal.fire({
                            title: '{{ __("Success!") }}',
                            text: '{{ __("Role updated successfully") }}',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadRoles();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || '{{ __("Could not update role") }}', 'error');
                    }
                });
            }
        });
    });

    // Gestionar permisos
    $(document).on('click', '.btn-permissions', function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');
        const role = allRoles.find(r => r.id === roleId);
        const currentPermissions = role?.permissions?.map(p => typeof p === 'string' ? p : p.name) || [];

        let permissionsHtml = '<div class="permissions-grid">';
        allPermissions.forEach(permission => {
            const checked = currentPermissions.includes(permission.name) ? 'checked' : '';
            permissionsHtml += `
                <div class="permission-checkbox">
                    <input type="checkbox" id="perm_${permission.id}" value="${permission.name}" ${checked}>
                    <label for="perm_${permission.id}">${permission.name}</label>
                </div>
            `;
        });
        permissionsHtml += '</div>';

        Swal.fire({
            title: `{{ __('Permissions for') }}: ${capitalizeFirst(roleName)}`,
            html: permissionsHtml,
            width: '700px',
            showCancelButton: true,
            confirmButtonText: '{{ __("Save") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#6366f1',
            preConfirm: () => {
                const selected = [];
                $('.permissions-grid input:checked').each(function() {
                    selected.push($(this).val());
                });
                return selected;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('manage-role/update-permissions') }}/${roleId}`,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ permissions: result.value }),
                    success: function() {
                        Swal.fire({
                            title: '{{ __("Success!") }}',
                            text: '{{ __("Permissions updated successfully") }}',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadRoles();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || '{{ __("Could not update permissions") }}', 'error');
                    }
                });
            }
        });
    });

    // Eliminar rol - DOBLE CONFIRMACIÓN
    $(document).on('click', '.btn-delete', function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');

        // PRIMERA CONFIRMACIÓN
        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            html: '{!! __("You are about to delete the role") !!} <strong>' + roleName + '</strong>.<br><br>' +
                  '<span class="text-danger"><i class="ti ti-alert-triangle me-1"></i>{!! __("Users with this role will lose their permissions.") !!}</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            confirmButtonText: '{{ __("Continue") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // SEGUNDA CONFIRMACIÓN
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
                        Swal.fire({
                            title: '{{ __("Deleting...") }}',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: `{{ url('manage-role/delete') }}/${roleId}`,
                            method: 'DELETE',
                            success: function() {
                                Swal.fire({
                                    title: '{{ __("Deleted!") }}',
                                    text: '{{ __("The role has been deleted.") }}',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadRoles();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || '{{ __("Could not delete role") }}', 'error');
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

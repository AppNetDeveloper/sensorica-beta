@extends('layouts.admin')

@section('title', __('Permission Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Permission Management') }}</li>
    </ul>
@endsection

@section('content')
<div class="permissions-container">
    {{-- Header --}}
    <div class="permissions-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <div class="d-flex align-items-center">
                    <div class="permissions-header-icon me-3">
                        <i class="ti ti-key"></i>
                    </div>
                    <div>
                        <h4 class="permissions-title mb-1">{{ __('Permission Management') }}</h4>
                        <p class="permissions-subtitle mb-0">{{ __('Manage system access permissions') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 mt-3 mt-lg-0 flex-wrap">
                    {{-- Buscador --}}
                    <div class="permissions-search-box">
                        <i class="ti ti-search"></i>
                        <input type="text" id="searchPermissions" class="form-control" placeholder="{{ __('Search permissions...') }}">
                    </div>
                    {{-- Botones de acción --}}
                    <button type="button" class="btn btn-light permissions-btn-action" onclick="openAddPermissionModal()">
                        <i class="ti ti-plus me-1"></i> {{ __('Add') }}
                    </button>
                    <button type="button" class="btn btn-light permissions-btn-action" onclick="exportToExcel()">
                        <i class="ti ti-file-export me-1"></i> {{ __('Export') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
            <div class="permissions-stats-card permissions-stats-primary">
                <div class="permissions-stats-icon">
                    <i class="ti ti-key"></i>
                </div>
                <div class="permissions-stats-info">
                    <h3 id="statsTotalPermissions">-</h3>
                    <span>{{ __('Total Permissions') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
            <div class="permissions-stats-card permissions-stats-success">
                <div class="permissions-stats-icon">
                    <i class="ti ti-category"></i>
                </div>
                <div class="permissions-stats-info">
                    <h3 id="statsCategories">-</h3>
                    <span>{{ __('Categories') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="permissions-stats-card permissions-stats-info">
                <div class="permissions-stats-icon">
                    <i class="ti ti-shield-check"></i>
                </div>
                <div class="permissions-stats-info">
                    <h3 id="statsGuardName">web</h3>
                    <span>{{ __('Guard') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="permissions-info-card mb-4">
        <div class="d-flex align-items-start">
            <div class="permissions-info-icon me-3">
                <i class="ti ti-info-circle"></i>
            </div>
            <div class="permissions-info-content">
                <strong>{{ __('Information') }}:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    <li>{{ __('Permissions define what actions users can perform in the system.') }}</li>
                    <li>{{ __('Use the naming convention') }}: <code>action-resource</code> ({{ __('e.g.') }}: <code>edit-users</code>, <code>delete-orders</code>)</li>
                    <li>{{ __('Assign permissions to roles in the Role Management section.') }}</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Permissions Grid --}}
    <div class="row" id="permissionsGrid">
        {{-- Los permisos se cargan por JavaScript --}}
    </div>

    {{-- Loading State --}}
    <div id="permissionsLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Loading') }}...</span>
        </div>
        <p class="mt-3 text-muted">{{ __('Loading permissions') }}...</p>
    </div>

    {{-- Empty State --}}
    <div id="permissionsEmpty" class="row" style="display: none;">
        <div class="col-12">
            <div class="card permissions-empty-state">
                <div class="card-body text-center py-5">
                    <div class="permissions-empty-icon mb-4">
                        <i class="ti ti-key-off"></i>
                    </div>
                    <h4>{{ __('No permissions found') }}</h4>
                    <p class="text-muted mb-4">{{ __('Start by adding your first permission.') }}</p>
                    <button type="button" class="btn btn-primary" onclick="openAddPermissionModal()">
                        <i class="ti ti-plus me-1"></i> {{ __('Add Permission') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Añadir/Editar Permiso --}}
<div class="modal fade" id="permissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header permissions-modal-header">
                <h5 class="modal-title" id="permissionModalTitle">
                    <i class="ti ti-plus me-2"></i>
                    {{ __('Add Permission') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="permissionForm">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Permission Name') }} <span class="text-danger">*</span></label>
                        <div class="permissions-input-group">
                            <div class="permissions-input-icon"><i class="ti ti-key"></i></div>
                            <input type="text" id="permissionName" class="form-control permissions-input" placeholder="e.g. edit-users" required>
                        </div>
                        <small class="text-muted">{{ __('Use lowercase letters and hyphens. Example:') }} edit-users, delete-orders</small>
                    </div>
                    <input type="hidden" id="permissionId" value="">
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="savePermission()">
                    <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.permissions-container {
    padding: 0;
}

/* Header */
.permissions-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.permissions-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.permissions-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.permissions-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Search Box */
.permissions-search-box {
    position: relative;
    min-width: 220px;
}

.permissions-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.7);
    font-size: 1.1rem;
}

.permissions-search-box input {
    padding-left: 42px;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    color: white;
    height: 42px;
    font-size: 0.9rem;
}

.permissions-search-box input::placeholder {
    color: rgba(255,255,255,0.7);
}

.permissions-search-box input:focus {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    box-shadow: none;
    color: white;
}

/* Action Buttons */
.permissions-btn-action {
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    background: white;
    color: #8b5cf6;
    border: none;
}

.permissions-btn-action:hover {
    background: #f8fafc;
    color: #7c3aed;
}

/* Stats Cards */
.permissions-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.permissions-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.permissions-stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.permissions-stats-primary .permissions-stats-icon {
    background: rgba(139, 92, 246, 0.15);
    color: #8b5cf6;
}

.permissions-stats-success .permissions-stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.permissions-stats-info .permissions-stats-icon {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.permissions-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.permissions-stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* Info Card */
.permissions-info-card {
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #8b5cf6;
}

.permissions-info-icon {
    width: 40px;
    height: 40px;
    background: rgba(139, 92, 246, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8b5cf6;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.permissions-info-content {
    color: #5b21b6;
    font-size: 0.9rem;
    line-height: 1.6;
}

.permissions-info-content code {
    background: rgba(139, 92, 246, 0.15);
    color: #7c3aed;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85rem;
}

/* Permission Cards */
.permission-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    animation: fadeInUp 0.4s ease forwards;
    opacity: 0;
    cursor: pointer;
    height: 100%;
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

.permission-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.2);
}

.permission-card-body {
    padding: 20px;
}

.permission-icon-wrap {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.permission-icon-wrap i {
    font-size: 1.5rem;
    color: #8b5cf6;
}

.permission-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
    word-break: break-word;
}

.permission-id {
    font-size: 0.8rem;
    color: #94a3b8;
}

.permission-card-footer {
    padding: 12px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.permission-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    border: none;
    transition: all 0.2s ease;
}

.permission-btn-edit {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.permission-btn-edit:hover {
    background: #3b82f6;
    color: white;
}

.permission-btn-delete {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.permission-btn-delete:hover {
    background: #ef4444;
    color: white;
}

/* Empty State */
.permissions-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
    border-radius: 16px;
}

.permissions-empty-icon {
    width: 80px;
    height: 80px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2.5rem;
    color: #94a3b8;
}

/* Modal Styles */
.permissions-modal-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    border: none;
}

.permissions-modal-header .modal-title {
    font-weight: 600;
}

.permissions-input-group {
    position: relative;
}

.permissions-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    z-index: 1;
}

.permissions-input {
    padding-left: 42px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.permissions-input:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

#permissionModal .modal-content {
    border-radius: 16px;
    border: none;
}

/* Dark Mode */
[data-theme="dark"] .permission-card {
    background: #1e293b;
}

[data-theme="dark"] .permission-card-body {
    background: #1e293b;
}

[data-theme="dark"] .permission-name {
    color: #f1f5f9;
}

[data-theme="dark"] .permission-id {
    color: #94a3b8;
}

[data-theme="dark"] .permission-card-footer {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .permissions-stats-card {
    background: #1e293b;
}

[data-theme="dark"] .permissions-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .permissions-info-card {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    border-left-color: #a78bfa;
}

[data-theme="dark"] .permissions-info-content {
    color: #c4b5fd;
}

[data-theme="dark"] .permissions-info-content code {
    background: rgba(167, 139, 250, 0.2);
    color: #a78bfa;
}

[data-theme="dark"] .permissions-empty-state {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .permissions-input {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

/* Responsive */
@media (max-width: 767.98px) {
    .permissions-search-box {
        width: 100%;
        min-width: auto;
    }

    .permissions-btn-action {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
const permissionsApiUrl = '{{ url("manage-permission") }}';
let allPermissions = [];
let isEditMode = false;

// CSRF Token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    }
});

document.addEventListener('DOMContentLoaded', function() {
    loadPermissions();

    // Buscador
    document.getElementById('searchPermissions')?.addEventListener('keyup', function() {
        filterPermissions(this.value);
    });
});

function loadPermissions() {
    document.getElementById('permissionsLoading').style.display = 'block';
    document.getElementById('permissionsGrid').innerHTML = '';
    document.getElementById('permissionsEmpty').style.display = 'none';

    fetch(`${permissionsApiUrl}/list-all`)
        .then(response => response.json())
        .then(data => {
            allPermissions = data || [];
            updateStats();
            renderPermissions(allPermissions);
            document.getElementById('permissionsLoading').style.display = 'none';
        })
        .catch(error => {
            console.error('Error loading permissions:', error);
            document.getElementById('permissionsLoading').style.display = 'none';
            Swal.fire('Error', '{{ __("Could not load permissions") }}', 'error');
        });
}

function updateStats() {
    document.getElementById('statsTotalPermissions').textContent = allPermissions.length;

    // Contar categorías únicas (basado en el prefijo antes del guión)
    const categories = new Set();
    allPermissions.forEach(p => {
        const parts = p.name.split('-');
        if (parts.length > 1) {
            categories.add(parts[0]);
        }
    });
    document.getElementById('statsCategories').textContent = categories.size || '-';
}

function renderPermissions(permissions) {
    const grid = document.getElementById('permissionsGrid');
    grid.innerHTML = '';

    if (permissions.length === 0) {
        document.getElementById('permissionsEmpty').style.display = 'block';
        return;
    }

    document.getElementById('permissionsEmpty').style.display = 'none';

    permissions.forEach((permission, index) => {
        const card = `
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 permission-card-wrapper"
                 data-name="${(permission.name || '').toLowerCase()}"
                 data-id="${permission.id}"
                 style="animation-delay: ${index * 0.03}s">
                <div class="permission-card h-100">
                    <div class="permission-card-body">
                        <div class="permission-icon-wrap">
                            <i class="ti ti-key"></i>
                        </div>
                        <div class="permission-name">${permission.name || '-'}</div>
                        <div class="permission-id">ID: ${permission.id}</div>
                    </div>
                    <div class="permission-card-footer">
                        <button type="button" class="permission-btn permission-btn-edit" onclick="event.stopPropagation(); openEditPermissionModal(${permission.id}, '${permission.name}')">
                            <i class="ti ti-edit me-1"></i> {{ __('Edit') }}
                        </button>
                        <button type="button" class="permission-btn permission-btn-delete" onclick="event.stopPropagation(); deletePermission(${permission.id})">
                            <i class="ti ti-trash me-1"></i> {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        `;
        grid.innerHTML += card;
    });
}

function filterPermissions(searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    const filtered = allPermissions.filter(p => {
        const name = (p.name || '').toLowerCase();
        const id = String(p.id);
        return name.includes(searchTerm) || id.includes(searchTerm);
    });
    renderPermissions(filtered);
}

function openAddPermissionModal() {
    isEditMode = false;
    document.getElementById('permissionModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>{{ __("Add Permission") }}';
    document.getElementById('permissionId').value = '';
    document.getElementById('permissionName').value = '';

    var modal = new bootstrap.Modal(document.getElementById('permissionModal'));
    modal.show();
}

function openEditPermissionModal(id, name) {
    isEditMode = true;
    document.getElementById('permissionModalTitle').innerHTML = '<i class="ti ti-edit me-2"></i>{{ __("Edit Permission") }}';
    document.getElementById('permissionId').value = id;
    document.getElementById('permissionName').value = name;

    var modal = new bootstrap.Modal(document.getElementById('permissionModal'));
    modal.show();
}

function savePermission() {
    const id = document.getElementById('permissionId').value || null;
    const name = document.getElementById('permissionName').value.trim();

    if (!name) {
        Swal.fire('Error', '{{ __("Permission name is required") }}', 'error');
        return;
    }

    const payload = { name };
    if (id) payload.id = parseInt(id);

    fetch(`${permissionsApiUrl}/store-or-update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) throw new Error('{{ __("Error saving permission") }}');
        return response.json();
    })
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('permissionModal'))?.hide();
        Swal.fire({
            icon: 'success',
            title: isEditMode ? '{{ __("Updated") }}!' : '{{ __("Added") }}!',
            text: isEditMode ? '{{ __("Permission updated successfully") }}' : '{{ __("Permission added successfully") }}',
            timer: 2000,
            showConfirmButton: false
        });
        loadPermissions();
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}

function deletePermission(id) {
    Swal.fire({
        title: '{{ __("Are you sure?") }}',
        text: '{{ __("This action cannot be undone. Roles using this permission will lose access.") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: '{{ __("Yes, delete") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${permissionsApiUrl}/delete/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('{{ __("Error deleting permission") }}');
                return response.json();
            })
            .then(() => {
                Swal.fire('{{ __("Deleted") }}!', '{{ __("Permission deleted successfully") }}', 'success');
                loadPermissions();
            })
            .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    });
}

function exportToExcel() {
    if (allPermissions.length === 0) {
        Swal.fire('{{ __("Warning") }}', '{{ __("No permissions to export") }}', 'warning');
        return;
    }

    const data = allPermissions.map(p => ({
        ID: p.id,
        '{{ __("Name") }}': p.name
    }));

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Permissions');
    XLSX.writeFile(wb, 'permissions.xlsx');
}
</script>
@endpush

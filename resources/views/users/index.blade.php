@extends('layouts.admin')

@section('title', __('User Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('User Management') }}</li>
    </ul>
@endsection

@section('content')
<div class="users-container">
    {{-- Header --}}
    <div class="users-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="d-flex align-items-center">
                    <div class="users-header-icon me-3">
                        <i class="ti ti-users"></i>
                    </div>
                    <div>
                        <h4 class="users-title mb-1">{{ __('User Management') }}</h4>
                        <p class="users-subtitle mb-0">{{ __('Manage system users and their roles') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-lg-end gap-3 mt-3 mt-lg-0">
                    {{-- Buscador --}}
                    <div class="users-search-box d-none d-md-block">
                        <i class="ti ti-search"></i>
                        <input type="text" id="searchUsers" class="form-control" placeholder="{{ __('Search users...') }}">
                    </div>
                    {{-- Botón añadir --}}
                    @can('create-user')
                    <a href="{{ route('users.create') }}" class="btn btn-light users-btn-add">
                        <i class="ti ti-plus me-1"></i> {{ __('Add User') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-check me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
            <div class="users-stats-card users-stats-primary">
                <div class="users-stats-icon">
                    <i class="ti ti-users"></i>
                </div>
                <div class="users-stats-info">
                    <h3>{{ $users->count() }}</h3>
                    <span>{{ __('Total Users') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
            <div class="users-stats-card users-stats-success">
                <div class="users-stats-icon">
                    <i class="ti ti-shield-check"></i>
                </div>
                <div class="users-stats-info">
                    @php
                        $rolesCount = $users->flatMap(function($user) {
                            return $user->getRoleNames();
                        })->unique()->count();
                    @endphp
                    <h3>{{ $rolesCount }}</h3>
                    <span>{{ __('Active Roles') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="users-stats-card users-stats-info">
                <div class="users-stats-icon">
                    <i class="ti ti-mail"></i>
                </div>
                <div class="users-stats-info">
                    @php
                        $withEmail = $users->filter(fn($u) => !empty($u->email))->count();
                    @endphp
                    <h3>{{ $withEmail }}</h3>
                    <span>{{ __('With Email') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Buscador móvil --}}
    <div class="users-search-mobile d-md-none mb-4">
        <div class="users-search-box-mobile">
            <i class="ti ti-search"></i>
            <input type="text" id="searchUsersMobile" class="form-control" placeholder="{{ __('Search users...') }}">
        </div>
    </div>

    {{-- Users Grid --}}
    @if($users->count() > 0)
    <div class="row" id="usersGrid">
        @foreach($users as $index => $user)
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4 user-card-wrapper"
             data-name="{{ strtolower($user->name) }}"
             data-email="{{ strtolower($user->email ?? '') }}"
             style="animation-delay: {{ $index * 0.05 }}s">
            <div class="card users-card h-100">
                {{-- Card Header con avatar --}}
                <div class="users-card-header">
                    <div class="users-avatar">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
                        @else
                            <span class="users-avatar-initials">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div class="users-card-id">#{{ $user->id }}</div>
                </div>

                {{-- Card Body --}}
                <div class="users-card-body">
                    <h5 class="users-name">{{ $user->name }}</h5>

                    <div class="users-info-item">
                        <i class="ti ti-mail"></i>
                        <span class="users-email" title="{{ $user->email }}">{{ $user->email ?? __('No email') }}</span>
                    </div>

                    <div class="users-info-item">
                        <i class="ti ti-phone"></i>
                        <span>{{ $user->phone ?? __('No phone') }}</span>
                    </div>

                    {{-- Roles --}}
                    <div class="users-roles mt-3">
                        @if(!empty($user->getRoleNames()) && $user->getRoleNames()->count() > 0)
                            @foreach($user->getRoleNames() as $role)
                                <span class="users-role-badge">{{ $role }}</span>
                            @endforeach
                        @else
                            <span class="users-role-badge users-role-none">{{ __('No role') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Card Footer con acciones --}}
                <div class="users-card-footer">
                    <div class="users-actions">
                        @can('edit-user')
                        <a href="{{ route('users.edit', $user->id) }}" class="users-action-btn users-btn-edit" title="{{ __('Edit') }}">
                            <i class="ti ti-edit"></i>
                        </a>
                        @endcan

                        @can('show-user')
                        <a href="{{ route('users.show', $user->id) }}" class="users-action-btn users-btn-view" title="{{ __('View') }}">
                            <i class="ti ti-eye"></i>
                        </a>
                        @endcan

                        @can('delete-user')
                        <button type="button" class="users-action-btn users-btn-delete"
                                onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                title="{{ __('Delete') }}">
                            <i class="ti ti-trash"></i>
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    {{-- Empty State --}}
    <div class="row">
        <div class="col-12">
            <div class="card users-empty-state">
                <div class="card-body text-center py-5">
                    <div class="users-empty-icon mb-4">
                        <i class="ti ti-users-minus"></i>
                    </div>
                    <h4>{{ __('No users found') }}</h4>
                    <p class="text-muted mb-4">{{ __('Start by adding your first user.') }}</p>
                    @can('create-user')
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> {{ __('Add User') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Modal de confirmación de eliminación --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="ti ti-alert-triangle text-danger me-2"></i>
                    {{ __('Confirm Delete') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="delete-warning-icon mb-3">
                    <i class="ti ti-user-minus"></i>
                </div>
                <h5 class="mb-3">{{ __('Are you sure you want to delete this user?') }}</h5>
                <p class="text-muted mb-4">
                    {{ __('User') }}: <strong id="deleteUserName"></strong>
                </p>
                <div class="alert alert-warning">
                    <i class="ti ti-info-circle me-2"></i>
                    {{ __('This action cannot be undone.') }}
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="ti ti-trash me-1"></i> {{ __('Delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.users-container {
    padding: 0;
}

/* Header */
.users-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.users-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.users-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.users-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Search Box */
.users-search-box {
    position: relative;
}

.users-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.7);
    font-size: 1.1rem;
}

.users-search-box input {
    padding-left: 42px;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    height: 44px;
    width: 240px;
    color: white;
    font-size: 0.9rem;
}

.users-search-box input::placeholder {
    color: rgba(255,255,255,0.7);
}

.users-search-box input:focus {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    box-shadow: none;
    color: white;
}

/* Mobile Search */
.users-search-box-mobile {
    position: relative;
}

.users-search-box-mobile i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #6366f1;
    font-size: 1.1rem;
}

.users-search-box-mobile input {
    padding-left: 42px;
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    height: 46px;
    width: 100%;
    font-size: 0.95rem;
}

.users-search-box-mobile input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Add Button */
.users-btn-add {
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    background: white;
    color: #6366f1;
    border: none;
}

.users-btn-add:hover {
    background: #f8fafc;
    color: #4f46e5;
}

/* Stats Cards */
.users-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.users-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.users-stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.users-stats-primary .users-stats-icon {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
}

.users-stats-success .users-stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.users-stats-info .users-stats-icon {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.users-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.users-stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* User Cards */
.users-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.users-card:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    transform: translateY(-4px);
}

.user-card-wrapper {
    animation: usersFadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes usersFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Card Header */
.users-card-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    padding: 24px;
    text-align: center;
    position: relative;
}

.users-card-id {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.users-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 3px solid rgba(255,255,255,0.5);
    overflow: hidden;
}

.users-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.users-avatar-initials {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

/* Card Body */
.users-card-body {
    padding: 20px;
    text-align: center;
}

.users-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 12px;
}

.users-info-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 6px;
}

.users-info-item i {
    color: #94a3b8;
    font-size: 1rem;
}

.users-email {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Roles */
.users-roles {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px;
}

.users-role-badge {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.users-role-none {
    background: #e2e8f0;
    color: #64748b;
}

/* Card Footer */
.users-card-footer {
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.users-actions {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.users-action-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.users-btn-edit {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.users-btn-edit:hover {
    background: #3b82f6;
    color: white;
}

.users-btn-view {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.users-btn-view:hover {
    background: #22c55e;
    color: white;
}

.users-btn-delete {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.users-btn-delete:hover {
    background: #ef4444;
    color: white;
}

/* Empty State */
.users-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
    border-radius: 16px;
}

.users-empty-icon {
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

/* Delete Modal */
.delete-warning-icon {
    width: 80px;
    height: 80px;
    background: rgba(239, 68, 68, 0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2.5rem;
    color: #ef4444;
}

#deleteModal .modal-content {
    border-radius: 16px;
    border: none;
}

/* Dark Mode */
[data-theme="dark"] .users-card {
    background: #1e293b;
}

[data-theme="dark"] .users-card-body {
    background: #1e293b;
}

[data-theme="dark"] .users-name {
    color: #f1f5f9;
}

[data-theme="dark"] .users-info-item {
    color: #94a3b8;
}

[data-theme="dark"] .users-card-footer {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .users-stats-card {
    background: #1e293b;
}

[data-theme="dark"] .users-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .users-empty-state {
    background: #0f172a;
    border-color: #334155;
}

/* Responsive */
@media (max-width: 991.98px) {
    .users-header .row {
        gap: 16px;
    }
}

@media (max-width: 767.98px) {
    .users-stats-card {
        margin-bottom: 12px;
    }
}
</style>
@endpush

@push('scripts')
<script>
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = "{{ route('users.index') }}/" + userId;

    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Buscador de usuarios
function filterUsers(searchTerm) {
    searchTerm = searchTerm.toLowerCase();

    document.querySelectorAll('.user-card-wrapper').forEach(function(card) {
        var name = card.getAttribute('data-name') || '';
        var email = card.getAttribute('data-email') || '';

        if (name.indexOf(searchTerm) > -1 || email.indexOf(searchTerm) > -1) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Buscador desktop
    var searchInput = document.getElementById('searchUsers');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            filterUsers(this.value);
        });
    }

    // Buscador mobile
    var searchInputMobile = document.getElementById('searchUsersMobile');
    if (searchInputMobile) {
        searchInputMobile.addEventListener('keyup', function() {
            filterUsers(this.value);
        });
    }
});
</script>
@endpush

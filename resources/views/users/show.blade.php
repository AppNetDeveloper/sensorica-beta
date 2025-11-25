@extends('layouts.admin')

@section('title', __('User Details'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('users.index') }}">{{ __('Users') }}</a>
        </li>
        <li class="breadcrumb-item">{{ $user->name }}</li>
    </ul>
@endsection

@section('content')
<div class="user-show-container">
    {{-- Header --}}
    <div class="user-show-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center">
                    <div class="user-show-avatar me-4">
                        <span class="user-show-initials">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    </div>
                    <div>
                        <h3 class="user-show-name mb-1">{{ $user->name }}</h3>
                        <p class="user-show-email mb-2">
                            <i class="ti ti-mail me-1"></i> {{ $user->email }}
                        </p>
                        <div class="user-show-roles">
                            @if(!empty($user->getRoleNames()))
                                @foreach($user->getRoleNames() as $role)
                                    <span class="user-show-role-badge">
                                        <i class="ti ti-shield me-1"></i>{{ $role }}
                                    </span>
                                @endforeach
                            @else
                                <span class="user-show-role-badge user-show-role-none">
                                    {{ __('No roles assigned') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 mt-3 mt-lg-0">
                    @can('edit-user')
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-light user-show-btn">
                        <i class="ti ti-edit me-1"></i> {{ __('Edit') }}
                    </a>
                    @endcan
                    <a href="{{ route('users.index') }}" class="btn btn-light user-show-btn">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- User Information Card --}}
        <div class="col-lg-6 mb-4">
            <div class="card user-show-card h-100">
                <div class="card-header user-show-card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-user me-2"></i>{{ __('User Information') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="user-show-info-item">
                        <div class="user-show-info-label">
                            <i class="ti ti-id"></i>
                            <span>ID</span>
                        </div>
                        <div class="user-show-info-value">#{{ $user->id }}</div>
                    </div>
                    <div class="user-show-info-item">
                        <div class="user-show-info-label">
                            <i class="ti ti-user"></i>
                            <span>{{ __('Name') }}</span>
                        </div>
                        <div class="user-show-info-value">{{ $user->name }}</div>
                    </div>
                    <div class="user-show-info-item">
                        <div class="user-show-info-label">
                            <i class="ti ti-mail"></i>
                            <span>{{ __('Email') }}</span>
                        </div>
                        <div class="user-show-info-value">
                            <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
                        </div>
                    </div>
                    <div class="user-show-info-item">
                        <div class="user-show-info-label">
                            <i class="ti ti-calendar"></i>
                            <span>{{ __('Created') }}</span>
                        </div>
                        <div class="user-show-info-value">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                    <div class="user-show-info-item">
                        <div class="user-show-info-label">
                            <i class="ti ti-refresh"></i>
                            <span>{{ __('Updated') }}</span>
                        </div>
                        <div class="user-show-info-value">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Roles & Permissions Card --}}
        <div class="col-lg-6 mb-4">
            <div class="card user-show-card h-100">
                <div class="card-header user-show-card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-shield me-2"></i>{{ __('Roles & Permissions') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($user->getRoleNames()) && count($user->getRoleNames()) > 0)
                        <div class="mb-4">
                            <h6 class="user-show-section-title">{{ __('Assigned Roles') }}</h6>
                            <div class="user-show-roles-list">
                                @foreach($user->getRoleNames() as $role)
                                    <div class="user-show-role-item">
                                        <div class="user-show-role-icon">
                                            <i class="ti ti-shield-check"></i>
                                        </div>
                                        <span>{{ $role }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @php
                            $permissions = $user->getAllPermissions();
                        @endphp
                        @if($permissions->count() > 0)
                            <div>
                                <h6 class="user-show-section-title">{{ __('Permissions') }} ({{ $permissions->count() }})</h6>
                                <div class="user-show-permissions-list">
                                    @foreach($permissions->take(10) as $permission)
                                        <span class="user-show-permission-badge">{{ $permission->name }}</span>
                                    @endforeach
                                    @if($permissions->count() > 10)
                                        <span class="user-show-permission-more">+{{ $permissions->count() - 10 }} {{ __('more') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="user-show-empty-roles">
                            <div class="user-show-empty-icon">
                                <i class="ti ti-shield-off"></i>
                            </div>
                            <p class="mb-0">{{ __('No roles assigned to this user.') }}</p>
                            @can('edit-user')
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-primary mt-3">
                                <i class="ti ti-plus me-1"></i> {{ __('Assign Role') }}
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Actions Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card user-show-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        @can('edit-user')
                        <a href="{{ route('users.edit', $user->id) }}" class="btn user-show-action-btn user-show-action-edit">
                            <i class="ti ti-edit me-2"></i> {{ __('Edit User') }}
                        </a>
                        @endcan
                        <a href="{{ route('users.index') }}" class="btn user-show-action-btn user-show-action-back">
                            <i class="ti ti-list me-2"></i> {{ __('All Users') }}
                        </a>
                        @can('delete-user')
                        @if(auth()->id() !== $user->id)
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this user?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn user-show-action-btn user-show-action-delete">
                                <i class="ti ti-trash me-2"></i> {{ __('Delete User') }}
                            </button>
                        </form>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.user-show-container {
    padding: 0;
}

/* Header */
.user-show-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
}

.user-show-avatar {
    width: 90px;
    height: 90px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid rgba(255,255,255,0.4);
    flex-shrink: 0;
}

.user-show-initials {
    font-size: 2rem;
    font-weight: 700;
    color: white;
}

.user-show-name {
    color: white;
    font-weight: 700;
    font-size: 1.75rem;
    margin: 0;
}

.user-show-email {
    color: rgba(255,255,255,0.85);
    font-size: 1rem;
    display: flex;
    align-items: center;
}

.user-show-roles {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.user-show-role-badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
}

.user-show-role-none {
    background: rgba(255,255,255,0.1);
    font-style: italic;
}

.user-show-btn {
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    background: white;
    color: #6366f1;
    border: none;
    text-decoration: none;
}

.user-show-btn:hover {
    background: #f8fafc;
    color: #4f46e5;
}

/* Cards */
.user-show-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.user-show-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
}

.user-show-card-header h5 {
    color: #1e293b;
    font-weight: 600;
    font-size: 1rem;
    display: flex;
    align-items: center;
}

.user-show-card-header i {
    color: #6366f1;
}

/* Info Items */
.user-show-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}

.user-show-info-item:last-child {
    border-bottom: none;
}

.user-show-info-label {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
    font-size: 0.9rem;
}

.user-show-info-label i {
    width: 32px;
    height: 32px;
    background: #f1f5f9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    font-size: 1rem;
}

.user-show-info-value {
    color: #1e293b;
    font-weight: 500;
    font-size: 0.95rem;
}

.user-show-info-value a {
    color: #6366f1;
}

.user-show-info-value a:hover {
    color: #4f46e5;
}

/* Roles & Permissions Section */
.user-show-section-title {
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.user-show-roles-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.user-show-role-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
}

.user-show-role-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.user-show-role-item span {
    color: #1e293b;
    font-weight: 500;
}

.user-show-permissions-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.user-show-permission-badge {
    background: #f1f5f9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.user-show-permission-more {
    color: #6366f1;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
}

.user-show-empty-roles {
    text-align: center;
    padding: 30px 20px;
}

.user-show-empty-icon {
    width: 60px;
    height: 60px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.75rem;
    color: #94a3b8;
}

.user-show-empty-roles p {
    color: #64748b;
    font-size: 0.9rem;
}

/* Action Buttons */
.user-show-action-btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    border: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
}

.user-show-action-edit {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
}

.user-show-action-edit:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}

.user-show-action-back {
    background: #f1f5f9;
    color: #475569;
}

.user-show-action-back:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.user-show-action-delete {
    background: #fef2f2;
    color: #ef4444;
}

.user-show-action-delete:hover {
    background: #ef4444;
    color: white;
}

/* Dark Mode */
[data-theme="dark"] .user-show-card {
    background: #1e293b;
}

[data-theme="dark"] .user-show-card-header {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .user-show-card-header h5 {
    color: #f1f5f9;
}

[data-theme="dark"] .user-show-info-item {
    border-color: #334155;
}

[data-theme="dark"] .user-show-info-label {
    color: #94a3b8;
}

[data-theme="dark"] .user-show-info-label i {
    background: #334155;
}

[data-theme="dark"] .user-show-info-value {
    color: #f1f5f9;
}

[data-theme="dark"] .user-show-role-item {
    background: #0f172a;
}

[data-theme="dark"] .user-show-role-item span {
    color: #f1f5f9;
}

[data-theme="dark"] .user-show-permission-badge {
    background: #334155;
    color: #cbd5e1;
}

[data-theme="dark"] .user-show-empty-icon {
    background: #334155;
}

[data-theme="dark"] .user-show-action-back {
    background: #334155;
    color: #cbd5e1;
}

[data-theme="dark"] .user-show-action-back:hover {
    background: #475569;
    color: #f1f5f9;
}

[data-theme="dark"] .user-show-action-delete {
    background: rgba(239, 68, 68, 0.2);
}

/* Responsive */
@media (max-width: 991.98px) {
    .user-show-header {
        padding: 24px;
    }

    .user-show-avatar {
        width: 70px;
        height: 70px;
    }

    .user-show-initials {
        font-size: 1.5rem;
    }

    .user-show-name {
        font-size: 1.4rem;
    }
}

@media (max-width: 575.98px) {
    .user-show-header .d-flex {
        flex-direction: column;
        text-align: center;
    }

    .user-show-avatar {
        margin: 0 auto 16px !important;
    }

    .user-show-roles {
        justify-content: center;
    }

    .user-show-btn {
        padding: 8px 16px;
        font-size: 0.85rem;
    }

    .user-show-action-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush

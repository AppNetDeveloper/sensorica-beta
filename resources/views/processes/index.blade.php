@extends('layouts.admin')

@section('title', __('Process Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Process Management') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('List of Processes')</h5>
                        @can('process-create')
                        <a href="{{ route('processes.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> @lang('New Process')
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Sequence')</th>
                                    <th>@lang('Description')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($processes as $index => $process)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $process->code }}</td>
                                        <td>{{ $process->name }}</td>
                                        <td>{{ $process->sequence }}</td>
                                        <td>{{ $process->description ?? 'N/A' }}</td>
                                        <td>
                                            @can('process-show')
                                            <a href="{{ route('processes.show', $process) }}" class="btn btn-sm btn-info" title="@lang('View')">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @endcan
                                            
                                            @can('process-edit')
                                            <a href="{{ route('processes.edit', $process) }}" class="btn btn-sm btn-warning" title="@lang('Edit')">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            
                                            @can('process-delete')
                                            <form action="{{ route('processes.destroy', $process) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="@lang('Delete')" onclick="return confirm('@lang('Are you sure you want to delete this process?')')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">@lang('No processes found.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
@endpush

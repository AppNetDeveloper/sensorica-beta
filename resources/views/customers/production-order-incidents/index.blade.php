@extends('layouts.admin')

@section('title', __('Production Order Incidents'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Production Order Incidents') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('Production Order Incidents') - {{ $customer->name }}</h5>
                        <a href="{{ route('customers.order-organizer', $customer->id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-th"></i> @lang('Order Organizer')
                        </a>
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
                    
                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="incidents-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('ORDER ID')</th>
                                    <th class="text-uppercase">@lang('REASON')</th>
                                    <th class="text-uppercase">@lang('STATUS')</th>
                                    <th class="text-uppercase">@lang('CREATED BY')</th>
                                    <th class="text-uppercase">@lang('CREATED AT')</th>
                                    <th class="text-uppercase">@lang('ACTIONS')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incidents as $index => $incident)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            #{{ $incident->productionOrder->order_id }}
                                        </td>
                                        <td>{{ Str::limit($incident->reason, 50) }}</td>
                                        <td>
                                            @if($incident->productionOrder->status == 3)
                                                <span class="badge bg-danger">@lang('Incidencia activa')</span>
                                            @else
                                                <span class="badge bg-secondary">@lang('Incidencia finalizada')</span>
                                            @endif
                                        </td>
                                        <td>{{ $incident->createdBy ? $incident->createdBy->name : 'Sistema' }}</td>
                                        <td>{{ $incident->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('customers.production-order-incidents.show', [$customer->id, $incident->id]) }}" 
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="@lang('View')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('delete', $customer)
                                                <form action="{{ route('customers.production-order-incidents.destroy', [$customer->id, $incident->id]) }}" 
                                                      method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="tooltip" title="@lang('Delete')" 
                                                            onclick="return confirm('@lang('Are you sure you want to delete this incident?')')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#incidents-table').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                },
                order: [[4, 'desc']], // Ordenar por fecha de creación (descendente)
            });
        });
    </script>
@endpush
